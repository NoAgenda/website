<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AjaxTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/', [], [], [
            'HTTP_X_REQUESTED_WITH' => 'NoAgendaRequest',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('path', $responseData);
        $this->assertArrayHasKey('title', $responseData);
        $this->assertArrayHasKey('contents', $responseData);
        $this->assertArrayHasKey('authenticated', $responseData);
    }
}
