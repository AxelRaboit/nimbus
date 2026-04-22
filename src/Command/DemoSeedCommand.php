<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Enum\PlanEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'nimbus:demo:seed',
    description: 'Crée ou recrée le compte utilisateur de démonstration.',
)]
class DemoSeedCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email du compte demo', 'demo@nimbus.app')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe du compte demo', 'demo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');

        $existing = $this->userRepository->findDemoUser();

        if ($existing instanceof User) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
            $symfonyStyle->note('Ancien compte demo supprimé.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName('Demo');
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsDemo(true);
        $user->setPlan(PlanEnum::Pro);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $symfonyStyle->success(sprintf('Compte demo créé : %s', $email));

        return Command::SUCCESS;
    }
}
