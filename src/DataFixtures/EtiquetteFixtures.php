<?php

namespace App\DataFixtures;

use App\Entity\Etiquette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EtiquetteFixtures extends Fixture
{
    public const ETIQUETTES = [
        'Bug' => '#E74C3C',
        'Feature' => '#3498DB',
        'Urgent' => '#E91E63',
        'Documentation' => '#9B59B6',
        'Amélioration' => '#F39C12',
        'Design' => '#16A085',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::ETIQUETTES as $nom => $couleur) {
            $etiquette = new Etiquette();
            $etiquette->setNom($nom);
            $etiquette->setCouleur($couleur);
            $manager->persist($etiquette);
            $this->addReference($nom, $etiquette);
        }

        $manager->flush();
    }
}
