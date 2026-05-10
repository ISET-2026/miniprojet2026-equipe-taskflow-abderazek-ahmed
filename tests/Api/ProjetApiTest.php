<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProjetApiTest extends WebTestCase
{
    public function testGetProjetsCollectionReturnsHydra(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/projets', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'application/ld+json',
            (string) $client->getResponse()->headers->get('Content-Type')
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('@context', $data);
    }

    public function testPostCreatesProjetReturns201(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $createur = $userRepository->findOneBy(['email' => 'chef@taskflow.com']);
        self::assertNotNull($createur);

        // Créateur exclu des groupes projet:write : renseigné côté serveur depuis l’utilisateur connecté
        $client->loginUser($createur);

        $body = json_encode([
            '@context' => '/api/contexts/Projet',
            '@type' => 'Projet',
            'nom' => 'Projet depuis API PHPUnit',
            'description' => 'Créé par test API',
            'dateLimite' => '2030-04-01T00:00:00+00:00',
            'statut' => 'planifie',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $client->request(
            'POST',
            '/api/projets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            $body,
        );

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Projet depuis API PHPUnit', $data['nom'] ?? null);
    }

    public function testPostValidationErrorForEmptyNomReturns422(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $createur = $userRepository->findOneBy(['email' => 'chef@taskflow.com']);
        self::assertNotNull($createur);

        $client->loginUser($createur);

        $body = json_encode([
            '@context' => '/api/contexts/Projet',
            '@type' => 'Projet',
            'nom' => '',
            'description' => 'x',
            'dateLimite' => '2030-04-01T00:00:00+00:00',
            'statut' => 'planifie',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $client->request(
            'POST',
            '/api/projets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            $body,
        );

        self::assertResponseStatusCodeSame(422);
    }
}
