<?php

namespace App\DataFixtures;

use App\Entity\Tache;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class TacheFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');
        $priorites = ['basse', 'moyenne', 'haute', 'urgente'];
        $statuts = ['a_faire', 'en_cours', 'terminee'];
        $etiquettes = ['Bug', 'Feature', 'Urgent', 'Documentation', 'Amélioration', 'Design'];

        $tacheTitres = [
            'Définir les spécifications',
            'Mettre en place l\'authentification',
            'Créer les modèles de données',
            'Développer le dashboard',
            'Configurer la base de données',
            'Tester l\'API',
            'Écrire la documentation',
            'Optimiser les performances',
            'Déployer en production',
            'Corriger les bugs',
        ];

        $compteur = 0;
        for ($pIdx = 0; $pIdx < 8; $pIdx++) {
            for ($t = 0; $t < 5; $t++) {
                $tache = new Tache();
                $tache->setTitre($tacheTitres[$t % count($tacheTitres)] . ' - ' . ($pIdx + 1));
                $tache->setDescription($faker->paragraphs(1, true));
                $tache->setPriorite($priorites[array_rand($priorites)]);
                $tache->setStatut($statuts[array_rand($statuts)]);
                $tache->setDateCreation(new \DateTimeImmutable());
                $tache->setDateEcheance(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('+1 week', '+2 months')));
                $tache->setProjet($this->getReference('projet-' . $pIdx, \App\Entity\Projet::class));
                $tache->setAssigneA($this->getReference('user-' . rand(1, 5), \App\Entity\User::class));

                // Ajouter 1 à 3 étiquettes aléatoires
                $nbEtiquettes = rand(1, 3);
                $etiquettesAleatoires = array_rand($etiquettes, min($nbEtiquettes, count($etiquettes)));
                if (!is_array($etiquettesAleatoires)) {
                    $etiquettesAleatoires = [$etiquettesAleatoires];
                }
                foreach ($etiquettesAleatoires as $idx) {
                    $tache->addEtiquette($this->getReference($etiquettes[$idx], \App\Entity\Etiquette::class));
                }

                $manager->persist($tache);
                $compteur++;
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjetFixtures::class,
            UserFixtures::class,
            EtiquetteFixtures::class,
        ];
    }
}
