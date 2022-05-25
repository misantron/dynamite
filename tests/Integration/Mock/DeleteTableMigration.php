<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\Mock;

use Dynamite\AbstractMigration;

final class DeleteTableMigration extends AbstractMigration
{
    public function up(): void
    {
        $this->setTableName('Users')->delete();
    }
}
