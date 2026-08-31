<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * US-006 (T-006-06) : rendu serveur Twig + Turbo/Stimulus (ADR-5).
 */
final class HomePageTest extends WebTestCase
{
    public function testHomePageRendersServerSide(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'HotOnes');
    }
}
