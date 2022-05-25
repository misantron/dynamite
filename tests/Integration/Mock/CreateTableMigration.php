<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\Mock;

use AsyncAws\DynamoDb\Enum\ProjectionType;
use Dynamite\AbstractMigration;

final class CreateTableMigration extends AbstractMigration
{
    public function up(): void
    {
        $this
            ->setTableName('Users')
            ->addAttribute('Id', 'S')
            ->addAttribute('Email', 'S')
            ->addHashKey('Id')
            ->setProvisionedThroughput(1, 1)
            ->addGlobalSecondaryIndex('Emails', ProjectionType::KEYS_ONLY, 'Email')
            ->create()
        ;
    }
}