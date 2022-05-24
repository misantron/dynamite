<?php

declare(strict_types=1);

namespace Dynamite\Command;

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Helper\DirectoryResolver;
use Dynamite\SeederInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'dynamite:seed',
    description: ''
)]
class SeedCommand extends Command
{
    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED,
            'The path to a seeding classes directory',
            'seeds'
        );
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $resolver = new DirectoryResolver($input->getOption('path'));

        foreach ($resolver->getClasses(SeederInterface::class) as $class) {
            $seeder = new $class($this->dynamoDbClient, $this->validator);
            $seeder->seed();
        }

        return self::SUCCESS;
    }
}
