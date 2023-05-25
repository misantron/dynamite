# Dynamite - AWS DynamoDB fixtures

[![Build Status](https://img.shields.io/github/actions/workflow/status/misantron/dynamite/build.yml?style=flat-square)](https://github.com/misantron/dynamite/actions)
[![Code Coverage](https://img.shields.io/codecov/c/gh/misantron/dynamite.svg?style=flat-square)](https://app.codecov.io/gh/misantron/dynamite)
[![Packagist](https://img.shields.io/packagist/v/misantron/dynamite.svg?style=flat-square)](https://packagist.org/packages/misantron/dynamite)

Provide a simple way to manage and execute the loading of data fixtures for AWS DynamoDB storage.  
Can use client from [AWS PHP SDK](https://aws.amazon.com/sdk-for-php/) or [Async AWS](https://async-aws.com/) under the hood.  
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
use Dynamite\Attribute\Groups;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\TableInterface;
use Dynamite\Schema\Attribute;

#[Groups(['group1'])] // groups can be used optionally with console command
final class UsersTable extends AbstractTable implements TableInterface
{
    protected function configure(): void
    {
        $this
            ->setTableName('Users')
            ->addAttributes([
                new Attribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
                new Attribute('Email', ScalarAttributeTypeEnum::String),
            ])
            ->addGlobalSecondaryIndex(
                'Emails',
                ProjectionTypeEnum::KeysOnly,
                'Email'
            )
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
use Dynamite\Attribute\Groups;
use Dynamite\FixtureInterface;
use Dynamite\Schema\Record;
use Dynamite\Schema\Value;

#[Groups(['group1'])] // groups can be used optionally with console command
final class UserFixtures extends AbstractFixture implements FixtureInterface
{
    protected function configure(): void
    {
        $this
            ->setTableName('Users')
            ->addRecords([
                new Record([
                    Value::stringValue('Id', 'e5502ec2-42a7-408b-9f03-f8e162b6257e'),
                    Value::stringValue('Email', 'john.doe@example.com'),
                    Value::boolValue('Active', true),
                ]),
                new Record([
                    Value::stringValue('Id', 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63'),
                    Value::stringValue('Email', 'robert.smith@example.com'),
                    Value::boolValue('Active', true),
                ]),
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
use Dynamite\Serializer\PropertyNameConverter;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Validation;

$validator = Validation::createValidatorBuilder()
    ->addLoader(new AnnotationLoader())
    ->getValidator()
;
$serializer = new Serializer([
    new BackedEnumNormalizer(),
    new ObjectNormalizer(null, new PropertyNameConverter()),
]);

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

use Dynamite\Client;
use Dynamite\Executor;
use Dynamite\Loader;
use Dynamite\Serializer\PropertyNameConverter;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Validation;

$validator = Validation::createValidatorBuilder()
    ->addLoader(new AnnotationLoader())
    ->getValidator()
;
$serializer = new Serializer([
    new BackedEnumNormalizer(),
    new ObjectNormalizer(null, new PropertyNameConverter()),
]);
$clientFactory = new ClientFactory($serializer);

$loader = new Loader($validator, $serializer);
$loader->loadFromDirectory('/path/to/YourFixtures');

$groups = ['group1']; // loading fixtures belong to the selected group only

$executor = new Executor($clientFactory->createAsyncAwsClient());
$executor->execute($loader->getFixtures($groups), $loader->getTables($groups));
```

**Important!** Each executor class comes with a purger class which executed before, drop tables and truncate data. 

### Load fixtures via console command

```shell
bin/console dynamite:fixtures:load --path path/to/fixtures
```

### Debug logger

Execution process debug logs can be enabled by passing PSR-3 logger into executor:

```php
<?php

declare(strict_types=1);

use Dynamite\Executor;

// PSR-3 compatible implementation of Psr\Log\LoggerInterface
$logger = new Logger();

$executor = new Executor($client, logger: $logger);
```
