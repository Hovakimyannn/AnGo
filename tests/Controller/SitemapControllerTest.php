<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapControllerTest extends WebTestCase
{
    public function testSitemapXmlLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');

        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<urlset', $content);
        self::assertStringContainsString('</urlset>', $content);
        self::assertStringContainsString('<lastmod>', $content);
        self::assertMatchesRegularExpression('#<loc>[^<]+</loc>#', $content);

        self::assertStringContainsString('/artists', $content);
        self::assertStringNotContainsString('/login', $content);
        self::assertStringNotContainsString('/signup', $content);
        self::assertStringNotContainsString('/admin', $content);
    }
}


