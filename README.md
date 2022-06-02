# Dynamite

[![Build Status](https://img.shields.io/github/workflow/status/misantron/dynamite/build.svg?style=flat-square)](https://github.com/misantron/dynamite/actions)
[![Code Coverage](https://img.shields.io/codacy/coverage/14793b443be444dbb19c02ddca1b0118.svg?style=flat-square)](https://app.codacy.com/gh/misantron/dynamite/files)
[![Code Quality](https://img.shields.io/codacy/grade/14793b443be444dbb19c02ddca1b0118.svg?style=flat-square)](https://app.codacy.com/gh/misantron/dynamite)

AWS DynamoDB migrations and seeding tool

## Install

The preferred way to install is through [Composer](https://getcomposer.org).
Run this command to install the latest stable version:

```shell
composer require --dev misantron/dynamite
```

## Examples

### Create table

```php
<?php

declare(strict_types=1);

namespace Migrations;

final class CreateUsersTable extends \Dynamite\AbstractMigration
{
    public function up(): void
    {
        $this
            ->setTableName('Users')
            ->addAttribute('Id', 'S')
            ->addAttribute('Email', 'S')
            ->addHashKey('Id')
            ->addGlobalSecondaryIndex('Emails', 'KEYS_ONLY', 'Email')
            ->setProvisionedThroughput(1, 1)
            ->create()
        ;
    }
}
```

### Create seeder

```php
<?php

declare(strict_types=1);

namespace Seeders;

final class UsersTableSeeder extends \Dynamite\AbstractSeeder
{
    public function seed(): void
    {
        $this
            ->setTableName('Users')
            ->addItems([
                [
                    'Id' => ['S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e'],
                    'Email' => ['S' => 'john.doe@example.com'],
                ],
                [
                    'Id' => ['S' => 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63'],
                    'Email' => ['S' => 'robert.smith@example.com'],
                ],  
            ])
            ->save()
        ;
    }
}
```
