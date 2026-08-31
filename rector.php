<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * US-009 (T-009-02) / ADR-3, ARC-51/53 — automatise la montée de version et la
 * correction des dépréciations (tolérance zéro). Lancé en `--dry-run` en CI,
 * appliqué à chaque montée mineure de Symfony.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()          // aligne sur la version PHP de composer.json (8.4+)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    );
