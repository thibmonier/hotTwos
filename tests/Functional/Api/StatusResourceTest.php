<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * US-006 (T-006-05) : API Platform en mode DTO strict (ADR-4 / ARC-18).
 * La ressource exposée est un DTO alimenté par un state provider qui délègue
 * au cas d'usage — jamais une entité de persistance.
 */
final class StatusResourceTest extends WebTestCase
{
    public function testStatusResourceReturnsHealthPayload(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/status', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status'] ?? null);
        self::assertSame('HotOnes', $payload['app'] ?? null);
    }
}
