<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Validator\ValidatorAwareInterface;
use Psr\Log\LoggerInterface;

interface FixtureInterface extends ValidatorAwareInterface
{
    public function getTableName(): ?string;

    public function load(ClientInterface $client, LoggerInterface $logger): void;
}
