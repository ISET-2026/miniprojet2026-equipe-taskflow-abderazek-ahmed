<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Create a new administrator user with ROLE_ADMIN.',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The admin email address')
            ->addArgument('pseudo', InputArgument::REQUIRED, 'The admin pseudo')
            ->addArgument('password', InputArgument::REQUIRED, 'The admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $pseudo = $input->getArgument('pseudo');
        $plainPassword = $input->getArgument('password');

        $userRepository = $this->entityManager->getRepository(User::class);

        if ($userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('Un utilisateur avec l\'email "%s" existe déjà.', $email));
            return Command::FAILURE;
        }

        if ($userRepository->findOneBy(['pseudo' => $pseudo])) {
            $io->error(sprintf('Un utilisateur avec le pseudo "%s" existe déjà.', $pseudo));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('L\'utilisateur administrateur "%s" a été créé avec succès.', $email));

        return Command::SUCCESS;
    }
}
