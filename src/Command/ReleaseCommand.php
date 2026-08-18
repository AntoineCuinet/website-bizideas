<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:release',
    description: 'Manage bundle versions (Status, Release, or Rollback)',
)]
class ReleaseCommand extends Command
{
    private string $bundleRoot;

    public function __construct()
    {
        parent::__construct();
        $this->bundleRoot = dirname(__DIR__, 2);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'The version number (e.g., 1.2.0)')
            ->addArgument('note', InputArgument::OPTIONAL, 'The release note')
            ->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Revert to the previous version from history');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $this->bundleRoot.'/version.json';

        if (!file_exists($filePath)) {
            $io->error(sprintf("File 'version.json' not found at expected path: %s", $filePath));

            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($filePath), true);

        // ROLLBACK
        if ($input->getOption('rollback')) {
            if (empty($data['history'])) {
                $io->warning('History is empty. Cannot perform rollback.');

                return Command::FAILURE;
            }

            $previousVersion = array_shift($data['history']);
            $io->note(sprintf(
                'Rolling back from v%s to v%s',
                $data['current']['version'],
                $previousVersion['version']
            ));

            $data['current'] = $previousVersion;
            $this->save($filePath, $data);

            $io->success('Rollback completed successfully.');

            return Command::SUCCESS;
        }

        $newVersion = $input->getArgument('version');
        $newNote = $input->getArgument('note');

        // STATUS CHECK
        if (!$newVersion || !$newNote) {
            $io->title('Portfolio Bundle Status');
            $io->definitionList(
                ['Bundle Location' => $this->bundleRoot],
                ['Current Version' => $data['current']['version']],
                ['Release Date' => $data['current']['date']],
                ['Latest Note' => $data['current']['note']]
            );
            $io->info('To release a new version: php bin/console app:release 1.2.0 "Release note"');

            return Command::SUCCESS;
        }

        // NEW RELEASE
        array_unshift($data['history'], $data['current']);

        $data['current'] = [
            'version' => $newVersion,
            'date' => (new \DateTimeImmutable())->format('Y-m-d'),
            'note' => $newNote,
        ];

        $this->save($filePath, $data);
        $io->success(sprintf('Bundle version %s has been successfully published!', $newVersion));

        return Command::SUCCESS;
    }

    private function save(string $path, array $data): void
    {
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
