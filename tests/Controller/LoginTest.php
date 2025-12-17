<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginTest extends WebTestCase
{
    public function testLoginPageLoadsAndHasCsrfToken(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/login"]');
        self::assertSelectorExists('input[name="_csrf_token"]');

        $csrf = (string) $crawler->filter('input[name="_csrf_token"]')->attr('value');
        self::assertNotSame('', $csrf);
    }

    public function testInvalidLoginShowsErrorAfterRedirect(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $csrf = (string) $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_username' => 'admin@example.com',
            '_password' => 'wrong-password',
            '_csrf_token' => $csrf,
        ]);

        self::assertResponseStatusCodeSame(302);

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Սխալ մուտքային տվյալներ');
    }

    public function testValidLoginRedirectsToAdmin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $csrf = (string) $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_username' => 'admin@example.com',
            '_password' => 'password',
            '_csrf_token' => $csrf,
        ]);

        self::assertResponseStatusCodeSame(302);
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/admin', $location);
    }
}


