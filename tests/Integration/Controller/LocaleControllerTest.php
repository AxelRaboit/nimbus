<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\IntegrationTestCase;

final class LocaleControllerTest extends IntegrationTestCase
{
    public function testSwitchToValidLocaleReturns200(): void
    {
        $client = static::createClient();
        $client->request('POST', '/locale', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['locale' => 'fr']));

        self::assertResponseIsSuccessful();
        self::assertSame('fr', json_decode($client->getResponse()->getContent(), true)['locale']);
    }

    public function testSwitchBetweenAllSupportedLocales(): void
    {
        $client = static::createClient();

        foreach (['en', 'fr', 'de', 'es'] as $locale) {
            $client->request('POST', '/locale', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['locale' => $locale]));
            self::assertResponseIsSuccessful();
        }
    }

    public function testUnsupportedLocaleReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('POST', '/locale', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['locale' => 'zh']));

        self::assertResponseStatusCodeSame(400);
    }
}
