<?php

declare(strict_types=1);

namespace Dynamite\Command;

use Dynamite\Executor;
use Dynamite\Loader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dynamite:fixtures:load',
    description: 'Load data fixtures (and optionally create tables) to database'
)]
final class FixturesLoadCommand extends Command
{
    public function __construct(
        private readonly Loader $loader,
        private readonly Executor $executor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);

        $path = $input->getOption('path');
        $groups = $input->getOption('group') ?? [];
        $onlyFixtures = (bool) ($input->getOption('only-fixtures') ?? false);
        $onlyTables = (bool) ($input->getOption('only-tables') ?? false);

        $this->loader->loadFromDirectory($path);

        $fixtures = $onlyTables ? [] : $this->loader->getFixtures($groups);
        if (!$onlyTables && \count($fixtures) < 1) {
            $message = 'Could not find any fixture to load';
            if (\count($groups) > 0) {
                $message .= sprintf(' in the groups (%s)', implode(', ', $groups));
            }

            $ui->error($message);

            return Command::FAILURE;
        }

        $tables = $onlyFixtures ? [] : $this->loader->getTables($groups);

        $this->executor->execute($fixtures, $tables);

        $ui->info('Fixtures loaded');

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Fixtures load directory path')
            ->addOption('group', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Only create tables and load fixtures that belong to this group')
            ->addOption('only-fixtures', null, InputOption::VALUE_OPTIONAL, 'Load fixtures only (skip tables creating)')
            ->addOption('only-tables', null, InputOption::VALUE_OPTIONAL, 'Create tables only (skip fixtures loading)')
        ;
    }
}
