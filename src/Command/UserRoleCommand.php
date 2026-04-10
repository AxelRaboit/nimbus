<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:role',
    description: 'Assign a role to a user',
)]
class UserRoleCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to assign (e.g. ROLE_DEV)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $role = mb_strtoupper((string) $input->getArgument('role'));

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            $io->error(sprintf('User "%s" not found.', $email));

            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
            $user->setRoles(array_values(array_filter($roles, fn ($r): bool => 'ROLE_USER' !== $r)));
            $this->em->flush();
        }

        $io->success(sprintf('Role %s assigned to %s', $role, $email));

        return Command::SUCCESS;
    }
}
