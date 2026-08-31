<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:dev',
    description: 'Starts the Symfony development server and the SCSS compiler in watch mode.',
)]
class DevCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting the local development environment');
        $exeFinder = new ExecutableFinder();
        $symfonyServerPath = $exeFinder->find('symfony');
        if ($symfonyServerPath) {
            $io->comment('Symfony CLI detected. Using "symfony server:start".');
            $serverCmd = [$symfonyServerPath, 'server:start'];
        } else {
            $io->comment('Symfony CLI not detected. Using PHP\'s built-in web server on http://127.0.0.1:8000.');
            $serverCmd = ['php', '-S', '127.0.0.1:8000', '-t', 'public'];
        }

        $sassProcess = new Process([
            PHP_BINARY,
            'bin/console',
            'sass:build',
            '--watch'
        ]);
        $sassProcess->setTimeout(null);

        $serverProcess = new Process($serverCmd);
        $serverProcess->setTimeout(null);

        if (function_exists('pcntl_signal')) {
            declare(ticks=1);
            $terminationHandler = function () use ($sassProcess, $serverProcess, $io) {
                $io->newLine();
                $io->warning('Stop signal received. Cleaning up processes...');
                $sassProcess->stop();
                $serverProcess->stop();
                $io->success('Development processes stopped successfully.');
                exit(0);
            };
            pcntl_signal(SIGINT, $terminationHandler);
            pcntl_signal(SIGTERM, $terminationHandler);
        }

        $io->info('Starting continuous SCSS compilation (watch mode)...');
        $sassProcess->start(function ($type, $buffer) use ($output) {
            $output->write('<info>[SCSS]</info> ' . $buffer);
        });

        $io->info('Starting development server...');
        $serverProcess->start(function ($type, $buffer) use ($output) {
            $output->write('<comment>[Server]</comment> ' . $buffer);
        });

        $io->success('Development environment active. Press Ctrl+C to stop.');

        while ($sassProcess->isRunning() && $serverProcess->isRunning()) {
            usleep(250000); // Sleep for 250ms
        }

        $sassProcess->stop();
        $serverProcess->stop();

        $io->error('One of the processes stopped unexpectedly.');
        return Command::FAILURE;
    }
}

