<?php

declare(strict_types=1);

namespace Dynamite;

interface MigrationInterface
{
    public function up(): void;
}
