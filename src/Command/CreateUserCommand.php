<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user or updates the role of an existing one in the database.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password of the user (required for creation)')
            ->addArgument('role', InputArgument::OPTIONAL, 'The role of the user (e.g. ROLE_USER, ROLE_ADMIN, admin, user)')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Set the user role to ROLE_ADMIN')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Set a specific role for the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $roleArgument = $input->getArgument('role');
        $isAdmin = $input->getOption('admin');
        $roleOption = $input->getOption('role');

        // Resolve role
        $role = 'ROLE_USER';
        if ($isAdmin) {
            $role = 'ROLE_ADMIN';
        } elseif ($roleOption) {
            $role = $this->normalizeRole($roleOption);
        } elseif ($roleArgument) {
            $role = $this->normalizeRole($roleArgument);
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            // Update existing user's role
            $existingUser->setRoles([$role]);
            $this->entityManager->flush();

            $io->success(sprintf('User "%s" already exists. Role has been updated to "%s".', $email, $role));
            return Command::SUCCESS;
        }

        // For new user creation, password is required
        if (!$password) {
            $io->error('Password is required to create a new user.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Prepopulate empty preferences
        $user->setPreferences([]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" has been successfully created with role "%s".', $email, $role));

        return Command::SUCCESS;
    }

    private function normalizeRole(string $role): string
    {
        $role = strtoupper($role);
        if ($role === 'ADMIN') {
            return 'ROLE_ADMIN';
        }
        if ($role === 'USER') {
            return 'ROLE_USER';
        }
        if (!str_starts_with($role, 'ROLE_')) {
            return 'ROLE_' . $role;
        }
        return $role;
    }
}
