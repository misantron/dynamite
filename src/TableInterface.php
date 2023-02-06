<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Validator\ValidatorAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;

interface TableInterface extends ValidatorAwareInterface, NormalizerAwareInterface
{
    public function getTableName(): ?string;

    public function create(ClientInterface $client, LoggerInterface $logger): void;
}
