<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * US-006 (T-006-07) : une route de démonstration servie par l'application répond 200.
 * Sert de première tranche vérifiable end-to-end du squelette (adaptateur HTTP → cas d'usage).
 */
final class HealthCheckTest extends WebTestCase
{
    public function testHealthEndpointReturnsOkStatus(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status'] ?? null);
        self::assertArrayHasKey('app', $payload);
    }
}
