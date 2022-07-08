<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Validator\ValidatorAwareInterface;
use Psr\Log\LoggerInterface;

interface FixtureInterface extends ValidatorAwareInterface
{
    public function getTableName(): string;

    public function load(DynamoDbClient $client, LoggerInterface $logger): void;
}
