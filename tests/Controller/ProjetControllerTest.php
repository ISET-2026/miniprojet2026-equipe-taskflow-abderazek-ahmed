<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProjetControllerTest extends WebTestCase
{
    public function testProjectListReturns200AndTable(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/projets');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('table');
        self::assertGreaterThan(0, $crawler->filter('table tbody tr')->count());
    }

    public function testNewProjectDeniedWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/projets/nouveau');

        self::assertResponseRedirects('/login');
    }

    public function testCreateProjectRedirectsAndShowsFlash(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'chef@taskflow.com',
            'password' => 'chef123',
        ]);
        $client->submit($form);
        self::assertResponseRedirects();

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();

        $client->followRedirects(false);

        $crawler = $client->request('GET', '/projets/nouveau');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le projet')->form([
            'projet[nom]' => 'Projet PHPUnit création',
            'projet[description]' => 'Test fonctionnel automatique.',
            'projet[dateLimite]' => '2030-06-15',
            'projet[statut]' => 'planifie',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Le projet a été créé avec succès.', $client->getResponse()->getContent());
        self::assertStringContainsString('Projet PHPUnit création', $client->getResponse()->getContent());
    }
}
