<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\Mock;

use Dynamite\AbstractMigration;

final class UpdateTableMigration extends AbstractMigration
{
    public function up(): void
    {
        $this
            ->setTableName('Users')
            ->setProvisionedThroughput(5, 5)
            ->update()
        ;
    }
}
