<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomepageTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('text/html', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('No Agenda', $client->getResponse()->getContent());
    }
}
