#!/usr/bin/env python3
"""
token-report — Agrège le coût en tokens des sessions Claude Code par branche → US → sprint.

Source de données : transcripts JSONL (~/.claude/projects/<slug>/*.jsonl).
Chaque entrée assistant porte gitBranch, timestamp et usage (in/out/cache/thinking).
Join : .bmad/sprint-status.yaml (sprint → [US]) + convention de branche (…us-NNN…).

Sorties :
  --sprint NNN     rapport markdown d'un sprint (pour workflow:retro / workflow:review)
  --pr BRANCHE     coût d'une branche (pour la description de PR au push)
  --branch BRANCHE alias de --pr
  --all            tableau global par branche (diagnostic)
  --json           sortie JSON au lieu du markdown

Config tarifs : .bmad/token-pricing.yaml (sinon défauts Opus 4.8 ci-dessous).
Usage inclus dans un abonnement → le coût USD est indicatif (paramétrable / désactivable).
"""
import argparse, glob, json, os, re, sys
from collections import defaultdict
from datetime import datetime

HOME = os.path.expanduser("~")


def slug_for(repo):
    """Claude Code nomme le dossier de transcripts d'après le chemin absolu du repo,
    en remplaçant chaque '/' par '-' (ex. /Users/x/Projects/y → -Users-x-Projects-y)."""
    return os.path.abspath(repo).replace(os.sep, "-")

DEFAULT_PRICING = {  # USD par million de tokens (indicatif, Opus 4.8)
    "currency": "USD",
    "show_usd": True,
    "input": 15.0,
    "output": 75.0,
    "cache_write": 18.75,
    "cache_read": 1.50,
}

BRANCH_US_RE = re.compile(r"us[-_]?(\d{2,4})", re.I)
BRANCH_SPRINT_RE = re.compile(r"sprint[-_]?(\d{1,3})", re.I)


def load_pricing(repo):
    path = os.path.join(repo, ".bmad", "token-pricing.yaml")
    pricing = dict(DEFAULT_PRICING)
    if os.path.exists(path):
        # parseur minimal clé: valeur (évite une dépendance yaml)
        with open(path) as fh:
            for line in fh:
                line = line.split("#", 1)[0].strip()
                if ":" not in line:
                    continue
                k, v = line.split(":", 1)
                k, v = k.strip(), v.strip()
                if k in ("currency",):
                    pricing[k] = v.strip('"\'')
                elif k in ("show_usd",):
                    pricing[k] = v.lower() in ("true", "1", "yes", "oui")
                else:
                    try:
                        pricing[k] = float(v)
                    except ValueError:
                        pass
    return pricing


def load_sprint_map(repo):
    """Retourne {us_norm: sprint_num} et {sprint_num: theme}."""
    path = os.path.join(repo, ".bmad", "sprint-status.yaml")
    us2sprint, sprint_theme = {}, {}
    if not os.path.exists(path):
        return us2sprint, sprint_theme
    with open(path) as fh:
        for line in fh:
            m = re.match(r"\s*sprint-(\d+):\s*\{(.*)", line)
            if not m:
                continue
            snum = int(m.group(1))
            body = m.group(2)
            tm = re.search(r'theme:\s*"([^"]*)"', body)
            if tm:
                sprint_theme[snum] = tm.group(1)
            sm = re.search(r"stories:\s*\[([^\]]*)\]", body)
            if sm:
                for us in re.findall(r"US[-_]?(\d+)", sm.group(1), re.I):
                    us2sprint[int(us)] = snum
    return us2sprint, sprint_theme


def branch_to_sprint(branch, us2sprint):
    """Résout une branche vers un numéro de sprint (via US, sinon via sprint-NNN)."""
    m = BRANCH_US_RE.search(branch or "")
    if m and int(m.group(1)) in us2sprint:
        return us2sprint[int(m.group(1))], f"US-{int(m.group(1)):03d}"
    m2 = BRANCH_SPRINT_RE.search(branch or "")
    if m2:
        return int(m2.group(1)), "(sprint direct)"
    return None, None


def scan(project_dir, start=None, end=None):
    """Agrège l'usage par branche. start/end (ISO) bornent la fenêtre temporelle."""
    agg = defaultdict(lambda: {"in": 0, "out": 0, "cw": 0, "cr": 0, "think": 0,
                               "msgs": 0, "first": None, "last": None})
    for f in glob.glob(os.path.join(project_dir, "*.jsonl")):
        with open(f, errors="replace") as fh:
            for line in fh:
                try:
                    d = json.loads(line)
                except Exception:
                    continue
                if d.get("type") != "assistant":
                    continue
                u = d.get("message", {}).get("usage")
                if not u:
                    continue
                ts = d.get("timestamp")
                if start and (ts is None or ts < start):
                    continue
                if end and (ts is None or ts > end):
                    continue
                br = d.get("gitBranch") or "(sans branche)"
                a = agg[br]
                a["in"] += u.get("input_tokens", 0)
                a["out"] += u.get("output_tokens", 0)
                a["cw"] += u.get("cache_creation_input_tokens", 0)
                a["cr"] += u.get("cache_read_input_tokens", 0)
                a["think"] += (u.get("output_tokens_details") or {}).get("thinking_tokens", 0)
                a["msgs"] += 1
                if ts:
                    if a["first"] is None or ts < a["first"]:
                        a["first"] = ts
                    if a["last"] is None or ts > a["last"]:
                        a["last"] = ts
    return agg


def usd(a, p):
    return (a["in"] * p["input"] + a["out"] * p["output"]
            + a["cw"] * p["cache_write"] + a["cr"] * p["cache_read"]) / 1_000_000


def k(n):
    return f"{n/1e3:.0f}k" if n < 1e6 else f"{n/1e6:.1f}M"


def fmt_row(label, a, p):
    base = f"| {label} | {a['msgs']} | {k(a['out'])} | {k(a['think'])} | {k(a['cw'])} | {k(a['cr'])} |"
    if p.get("show_usd"):
        base += f" ~{usd(a, p):.2f} {p['currency']} |"
    return base


def header(p):
    cols = "| Périmètre | Msgs | Output | Thinking | Cache écrit | Cache lu |"
    sep = "|---|--:|--:|--:|--:|--:|"
    if p.get("show_usd"):
        cols += " Coût est. |"
        sep += "--:|"
    return cols + "\n" + sep


def report_sprint(agg, snum, us2sprint, sprint_theme, p):
    rows, totals = [], {"in": 0, "out": 0, "cw": 0, "cr": 0, "think": 0, "msgs": 0}
    per_us = defaultdict(lambda: {"in": 0, "out": 0, "cw": 0, "cr": 0, "think": 0, "msgs": 0, "branches": []})
    for br, a in agg.items():
        s, label = branch_to_sprint(br, us2sprint)
        if s != snum:
            continue
        for key in totals:
            totals[key] += a[key]
        bucket = per_us[label or "(autre)"]
        for key in ("in", "out", "cw", "cr", "think", "msgs"):
            bucket[key] += a[key]
        bucket["branches"].append(br)
    theme = sprint_theme.get(snum, "")
    out = [f"### 💸 Coût en tokens — Sprint {snum:03d}",
           f"*{theme}*" if theme else "",
           "", header(p)]
    for label in sorted(per_us):
        out.append(fmt_row(label, per_us[label], p))
    out.append("")
    out.append(fmt_row("**TOTAL SPRINT**", totals, p))
    out.append("")
    out.append("> Source : transcripts Claude Code (gitBranch × usage). "
               "Le résidu de travail fait directement sur `main` (exploration, clôture) "
               "n'est pas rattaché ici — voir `token-report --all`.")
    if p.get("show_usd"):
        out.append(f"> Coût USD **indicatif** (tarifs `.bmad/token-pricing.yaml`), "
                   f"non représentatif si usage inclus dans un abonnement.")
    return "\n".join(x for x in out if x is not None)


def report_branch(agg, branch, us2sprint, p):
    a = agg.get(branch)
    if not a:
        return f"_Aucune activité de tokens trouvée pour la branche `{branch}`._"
    s, label = branch_to_sprint(branch, us2sprint)
    ctx = f" — {label}, sprint {s:03d}" if s else ""
    lines = [f"### 💸 Coût en tokens de cette branche{ctx}",
             "", header(p), fmt_row(f"`{branch}`", a, p), ""]
    win = ""
    if a["first"] and a["last"]:
        win = f" ({a['first'][:16]} → {a['last'][:16]})"
    lines.append(f"> {a['msgs']} messages assistant{win}. "
                 f"Snapshot au moment du push — les échanges de clôture de session ne sont pas encore inclus.")
    return "\n".join(lines)


def report_all(agg, p):
    rows = sorted(agg.items(), key=lambda kv: usd(kv[1], p), reverse=True)
    lines = [header(p)]
    for br, a in rows[:40]:
        lines.append(fmt_row(br, a, p))
    return "\n".join(lines)


FIX_BRANCH_RE = re.compile(r"^(fix|hotfix|bugfix)/", re.I)


def report_fixes(agg, us2sprint, sprint_theme, snum, p):
    """Coût des issues corrigées = branches fix/* (roll-up, filtré par sprint si snum)."""
    rows, totals = [], {"in": 0, "out": 0, "cw": 0, "cr": 0, "think": 0, "msgs": 0}
    for br, a in sorted(agg.items(), key=lambda kv: usd(kv[1], p), reverse=True):
        if not FIX_BRANCH_RE.match(br):
            continue
        s, label = branch_to_sprint(br, us2sprint)
        if snum is not None and s != snum:
            continue
        ctx = f" ({label}, S{s:03d})" if s else ""
        rows.append(fmt_row(f"`{br}`{ctx}", a, p))
        for key in totals:
            totals[key] += a[key]
    scope = f" — Sprint {snum:03d}" if snum is not None else " (tous sprints)"
    if not rows:
        return f"### 🐛 Coût des issues corrigées{scope}\n\n_Aucune branche `fix/*` trouvée._"
    out = [f"### 🐛 Coût des issues corrigées{scope}", "", header(p)]
    out += rows
    out += ["", fmt_row("**TOTAL FIXES**", totals, p), "",
            "> Une branche `fix/*` peut couvrir plusieurs findings — attribution grossière assumée."]
    return "\n".join(out)


def report_window(agg, start, end, p, title="fenêtre"):
    """Coût cumulé sur une fenêtre temporelle (ex. run code-review / qa:recette / qa:fix)."""
    rows, totals = [], {"in": 0, "out": 0, "cw": 0, "cr": 0, "think": 0, "msgs": 0}
    for br, a in sorted(agg.items(), key=lambda kv: usd(kv[1], p), reverse=True):
        if a["msgs"] == 0:
            continue
        rows.append(fmt_row(br, a, p))
        for key in totals:
            totals[key] += a[key]
    out = [f"### 🔎 Coût sur la {title} `{start[:16]} → {(end or 'maintenant')[:16]}`",
           "", header(p)]
    out += rows[:15]
    out += ["", fmt_row("**TOTAL FENÊTRE**", totals, p), "",
            "> Somme de l'usage de toutes les sessions actives dans la fenêtre "
            "(remontée d'issues = run de review/recette ; correction = run de fix)."]
    return "\n".join(out)


def rec_to_iso(s):
    """Convertit un id de session QA 'REC-YYYYMMDD-HHMMSS' en timestamp ISO UTC.
    Laisse passer un ISO déjà formé."""
    m = re.match(r"(?:REC-)?(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})$", s or "")
    if m:
        y, mo, d, h, mi, se = m.groups()
        return f"{y}-{mo}-{d}T{h}:{mi}:{se}.000Z"
    return s


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--repo", default=os.getcwd())
    ap.add_argument("--slug", default=None,
                    help="slug du dossier ~/.claude/projects/ (défaut : dérivé de --repo)")
    ap.add_argument("--sprint", type=int)
    ap.add_argument("--pr", "--branch", dest="branch")
    ap.add_argument("--all", action="store_true")
    ap.add_argument("--fixes", action="store_true",
                    help="coût des issues corrigées (branches fix/*), filtrable par --sprint")
    ap.add_argument("--window", nargs="+", metavar="DEBUT [FIN]",
                    help="coût sur une fenêtre : ISO ou id session QA (REC-YYYYMMDD-HHMMSS) ; "
                         "FIN optionnel (défaut : maintenant)")
    ap.add_argument("--json", action="store_true")
    args = ap.parse_args()

    slug = args.slug or slug_for(args.repo)
    project_dir = os.path.join(HOME, ".claude", "projects", slug)
    if not os.path.isdir(project_dir):
        sys.exit(f"Dossier transcripts introuvable : {project_dir}")

    p = load_pricing(args.repo)
    us2sprint, sprint_theme = load_sprint_map(args.repo)

    # --window : scan borné dans le temps (issues remontées/corrigées par fenêtre)
    if args.window:
        start = rec_to_iso(args.window[0])
        end = rec_to_iso(args.window[1]) if len(args.window) > 1 else None
        agg = scan(project_dir, start=start, end=end)
        print(report_window(agg, start, end, p))
        return

    agg = scan(project_dir)

    if args.fixes:
        print(report_fixes(agg, us2sprint, sprint_theme, args.sprint, p))
        return

    if args.branch is None and args.sprint is None and not args.all:
        # défaut : branche git courante
        try:
            import subprocess
            args.branch = subprocess.check_output(
                ["git", "-C", args.repo, "rev-parse", "--abbrev-ref", "HEAD"],
                text=True).strip()
        except Exception:
            args.all = True

    if args.all:
        print(report_all(agg, p))
    elif args.sprint is not None:
        print(report_sprint(agg, args.sprint, us2sprint, sprint_theme, p))
    else:
        print(report_branch(agg, args.branch, us2sprint, p))


if __name__ == "__main__":
    main()
