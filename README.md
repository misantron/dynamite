# Dynamite - AWS DynamoDB fixtures

[![Build Status](https://img.shields.io/github/workflow/status/misantron/dynamite/build.svg?style=flat-square)](https://github.com/misantron/dynamite/actions)
[![Code Coverage](https://img.shields.io/codacy/coverage/14793b443be444dbb19c02ddca1b0118.svg?style=flat-square)](https://app.codacy.com/gh/misantron/dynamite/files)
[![Code Quality](https://img.shields.io/codacy/grade/14793b443be444dbb19c02ddca1b0118.svg?style=flat-square)](https://app.codacy.com/gh/misantron/dynamite)
[![Packagist](https://img.shields.io/packagist/v/misantron/dynamite.svg?style=flat-square)](https://packagist.org/packages/misantron/dynamite)

Provide a simple way to manage and execute the loading of data fixtures for AWS DynamoDB storage.  
Library code design is heavily inspired by [doctrine/data-fixtures](https://github.com/doctrine/data-fixtures).

## Install

The preferred way to install is through [Composer](https://getcomposer.org).
Run this command to install the latest stable version:

```shell
composer require --dev misantron/dynamite
```

## Loading fixtures

### Create table creation class

This feature is optional.  
Fixture classes must implement `Dynamite\TableInterface` interface to be visible for a loader.

```php
<?php

declare(strict_types=1);

namespace Fixtures;

use Dynamite\AbstractTable;
use Dynamite\TableInterface;

final class UsersTable extends AbstractTable implements TableInterface
{
    protected function configure(): void
    {
        $this
            ->setTableName('Users')
            ->addAttributes([
                ['Id', 'S'],
                ['Email', 'S'],
            ])
            ->addHashKey('Id')
            ->addGlobalSecondaryIndex('Emails', 'KEYS_ONLY', 'Email')
            ->setProvisionedThroughput(1, 1)
        ;
    }
}
```

### Create a fixture loading class

Fixture classes must implement `Dynamite\FixtureInterface` interface to be visible for a loader.

```php
<?php

declare(strict_types=1);

namespace Fixtures;

use Dynamite\AbstractFixture;
use Dynamite\FixtureInterface;

final class UserFixtures extends AbstractFixture implements FixtureInterface
{
    protected function configure(): void
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
        ;
    }
}
```

### Tables and fixtures loading

It's possible to provide fixtures loading path:

```php
<?php

declare(strict_types=1);

use Dynamite\Loader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Serializer\Serializer;

$validator = Validation::createValidator();
$serializer = new Serializer();

$loader = new Loader($validator, $serializer);
$loader->loadFromDirectory('/path/to/YourFixtures');
```

or loading each fixture or table class manually:

```php
<?php

declare(strict_types=1);

$loader->addTable(new \App\Fixtures\UsersTable());
$loader->addFixture(new \App\Fixtures\UserFixtures());
```

### Create tables and executing fixtures

To create database schema and load the fixtures in storage you should do the following:

```php
<?php

declare(strict_types=1);

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Executor;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Serializer\Serializer;

$validator = Validation::createValidator();
$serializer = new Serializer();
$dynamoDbClient = new DynamoDbClient();

$loader = new Loader($validator, $serializer);
$loader->loadFromDirectory('/path/to/YourFixtures');

$executor = new Executor($dynamoDbClient);
$executor->execute($loader->getFixtures(), $loader->getTables());
```

**Important!** Each executor class comes with a purger class which executed before, drop tables and truncate data. 

### Debug logger

Execution process debug logs can be enabled by passing PSR-3 logger into executor:

```php
<?php

declare(strict_types=1);

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Executor;

// PSR-3 compatible implementation of Psr\Log\LoggerInterface
$logger = new Logger();

$executor = new Executor($dynamoDbClient, logger: $logger);
```
