<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicationParameter;
use App\Enum\ApplicationParameter\NimbusApplicationParameterEnum;
use App\Repository\ApplicationParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nimbus:application-parameter',
    description: 'Synchronise les paramètres applicatifs (crée les manquants, supprime les obsolètes).',
    aliases: ['nimbus:ap'],
)]
class ApplicationParameterCommand extends Command
{
    public function __construct(
        private readonly ApplicationParameterRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Mode dry-run — aucun changement ne sera enregistré.');
        }

        $enumCases = NimbusApplicationParameterEnum::cases();
        $enumKeys = array_map(fn ($c): string => $c->getKey(), $enumCases);
        $existing = [];

        foreach ($this->repository->findAll() as $param) {
            $existing[$param->getKey()] = $param;
        }

        $created = $this->createMissing($enumCases, $existing, $io, $dryRun);
        $deleted = $this->deleteObsolete($enumKeys, $existing, $io, $dryRun);

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('%d créé(s), %d supprimé(s).', $created, $deleted));

        return Command::SUCCESS;
    }

    /**
     * @param NimbusApplicationParameterEnum[]    $enumCases
     * @param array<string, ApplicationParameter> $existing
     */
    private function createMissing(array $enumCases, array $existing, SymfonyStyle $io, bool $dryRun): int
    {
        $created = 0;

        foreach ($enumCases as $case) {
            if (isset($existing[$case->getKey()])) {
                $this->syncDescription($case, $existing[$case->getKey()], $io, $dryRun);
                continue;
            }

            $io->writeln(sprintf('  <info>+</info> %s (défaut : %s)', $case->getKey(), $case->getDefaultValue()));
            ++$created;

            if (!$dryRun) {
                $this->entityManager->persist(new ApplicationParameter($case->getKey(), $case->getDefaultValue(), $case->getDescription()));
            }
        }

        return $created;
    }

    private function syncDescription(NimbusApplicationParameterEnum $case, ApplicationParameter $param, SymfonyStyle $io, bool $dryRun): void
    {
        if ($param->getDescription() === $case->getDescription()) {
            return;
        }

        $io->writeln(sprintf('  <comment>~</comment> %s (description mise à jour)', $case->getKey()));

        if (!$dryRun) {
            $param->setDescription($case->getDescription());
        }
    }

    /**
     * @param string[]                            $enumKeys
     * @param array<string, ApplicationParameter> $existing
     */
    private function deleteObsolete(array $enumKeys, array $existing, SymfonyStyle $io, bool $dryRun): int
    {
        $deleted = 0;

        foreach ($existing as $key => $param) {
            if (in_array($key, $enumKeys, true)) {
                continue;
            }

            $io->writeln(sprintf('  <fg=red>-</fg=red> %s (obsolète)', $key));
            ++$deleted;

            if (!$dryRun) {
                $this->entityManager->remove($param);
            }
        }

        return $deleted;
    }
}
