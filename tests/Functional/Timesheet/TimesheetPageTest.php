<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\Holiday;
use App\Domain\Project\Project;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;

/**
 * US-050 — l'écran de saisie hebdomadaire présente les projets actifs du tenant et les
 * imputations déjà saisies ; il exige une authentification.
 */
final class TimesheetPageTest extends WebTestCase
{
    public function testWeekPageListsProjectsAndExistingEntries(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AccountingPeriod::class),
            $em->getClassMetadata(AbsenceRequest::class),
            $em->getClassMetadata(ReminderPreference::class),
            $em->getClassMetadata(Holiday::class),
            $em->getClassMetadata(ClosurePeriod::class),
            $em->getClassMetadata(WorkSchedule::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        $user = new User($tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('x'));
        $project = new Project($tenant, 'PRJ-1', 'Refonte SI');
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($user);
        $em->persist($project);
        $em->persist(new TimeEntry($tenant, $user->id(), $project->id(), new DateTimeImmutable('monday this week'), 210));
        $em->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/saisie');

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('Refonte SI', $body);
        // La cellule du lundi porte la valeur déjà saisie, en heures décimales (210 min = 3,5 h).
        self::assertGreaterThan(0, $crawler->filter('input[value="3.5"]')->count());

        // US-059 : panneau « Ma synthèse » rendu (SSR) dans la page de saisie, lecture seule.
        self::assertStringContainsString('Ma synthèse', $body);
        self::assertStringContainsString('Taux d\'occupation', $body);
        self::assertStringContainsString('Planning à venir', $body);
        // CA-5 : le panneau est en lecture seule — aucun champ de formulaire dans le <dialog>.
        self::assertSame(0, $crawler->filter('dialog.summary-dialog input')->count());
        // US-069 (T-069-04) : la dialog signale son rôle modal aux technologies d'assistance (a11y).
        self::assertSame(1, $crawler->filter('dialog.summary-dialog[aria-modal="true"]')->count());

        // US-089 / CA-2 (reco TMP1-01) — totaux rendus CÔTÉ SERVEUR au premier rendu (210 min = 3,5 h).
        $grandTotal = $crawler->filter('[data-timesheet-target="grandTotal"]');
        self::assertSame(1, $grandTotal->count(), 'Cellule grandTotal (SSR) absente.');
        self::assertStringContainsString('3,5', $grandTotal->text(), 'grandTotal SSR incorrect (attendu 3,5 h).');
        $monday = new DateTimeImmutable('monday this week')->format('Y-m-d');
        $dayTotal = $crawler->filter(sprintf('[data-timesheet-target="dayTotal"][data-date="%s"]', $monday));
        self::assertSame(1, $dayTotal->count(), 'Cellule dayTotal du lundi absente.');
        self::assertStringContainsString('3,5', $dayTotal->text(), 'dayTotal lundi SSR incorrect.');
        // Bandeau objectif rendu serveur : 3,5 h / 35,0 h (5 jours ouvrés × 7 h) · 10 %.
        self::assertStringContainsString('3,5 h', $body);
        self::assertStringContainsString('35,0 h', $body);
        self::assertStringContainsString('10 %', $body);

        // US-089 / CA-3 (reco TMP1-05) — conteneur de bannière d'erreur présent dans le DOM (role=alert).
        self::assertSame(
            1,
            $crawler->filter('[data-timesheet-target="errorBanner"][role="alert"]')->count(),
            'Le conteneur errorBanner role="alert" est absent du DOM.',
        );

        // US-089 / CA-4 — lien « Vue jour (mobile) » vers la saisie du jour (semaine courante).
        $dayLink = $crawler->selectLink('Vue jour (mobile)');
        self::assertSame(1, $dayLink->count(), 'Le lien « Vue jour (mobile) » est absent.');
        self::assertStringContainsString($monday, (string) $dayLink->first()->attr('href'));

        $tool->dropSchema($schema);
        $em->close();
    }

    public function testBannerShownWhenOptedOutAndLate(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AccountingPeriod::class),
            $em->getClassMetadata(AbsenceRequest::class),
            $em->getClassMetadata(ReminderPreference::class),
            $em->getClassMetadata(Holiday::class),
            $em->getClassMetadata(ClosurePeriod::class),
            $em->getClassMetadata(WorkSchedule::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        $user = new User($tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('x'));
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($user);
        // Opt-out + aucune imputation : les semaines passées (au-delà de l'échéance) sont en retard.
        $em->persist(new ReminderPreference($tenant, $user->id(), true, new DateTimeImmutable('-1 month')));
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/saisie');

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('désactivé les relances', $body);
        self::assertStringContainsString('en retard', $body);

        $tool->dropSchema($schema);
        $em->close();
    }

    public function testWeekPageRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', '/saisie');

        // US-068 : route web → redirection vers la page de connexion (plus de 401 web).
        self::assertResponseRedirects('/login');
    }
}
