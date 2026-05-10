<?php

namespace App\DataFixtures;

use App\Entity\Projet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class ProjetFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');
        $statuts = ['planifie', 'en_cours', 'termine', 'annule'];

        $projects = [
            'Système de Gestion de Stock',
            'Plateforme d\'E-commerce',
            'Application Mobile RH',
            'Refonte du Site Web',
            'Logiciel de Comptabilité',
            'Portail Étudiant',
            'Infrastructure Cloud',
            'Système CRM',
        ];

        for ($i = 0; $i < count($projects); $i++) {
            $projet = new Projet();
            $projet->setNom($projects[$i]);
            $projet->setDescription($faker->paragraphs(2, true));
            $projet->setDateCreation(new \DateTimeImmutable());
            $projet->setDateLimite(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('+1 week', '+3 months')));
            $projet->setStatut($statuts[array_rand($statuts)]);
            $userId = 'user-' . rand(1, 5);
            $projet->setCreateur($this->getReference($userId, \App\Entity\User::class));
            $manager->persist($projet);
            $this->addReference('projet-' . $i, $projet);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
