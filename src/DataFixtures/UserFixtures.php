<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory as FakerFactory;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');

        // Admin
        $admin = new User();
        $admin->setEmail('admin@taskflow.com');
        $admin->setPseudo('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);
        $this->addReference('user-admin', $admin);

        // Chef de projet
        $chef = new User();
        $chef->setEmail('chef@taskflow.com');
        $chef->setPseudo('Chef Projet');
        $chef->setRoles(['ROLE_CHEF_PROJET']);
        $chef->setPassword($this->passwordHasher->hashPassword($chef, 'chef123'));
        $manager->persist($chef);
        $this->addReference('user-chef', $chef);

        // 5 utilisateurs réguliers
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail('user' . $i . '@taskflow.com');
            $user->setPseudo($faker->firstName());
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
            $manager->persist($user);
            $this->addReference('user-' . $i, $user);
        }

        $manager->flush();
    }
}
