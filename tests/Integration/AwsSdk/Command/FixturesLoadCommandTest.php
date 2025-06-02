<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AwsSdk\Command;

use Aws\DynamoDb\DynamoDbClient;
use Dynamite\Command\FixturesLoadCommand;
use Dynamite\Executor;
use Dynamite\Loader;
use Dynamite\Tests\Constraint\Composite;
use Dynamite\Tests\Constraint\Delegate;
use Dynamite\Tests\Integration\AwsSdkIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\IsIdentical;
use Symfony\Component\Console\Tester\CommandTester;
use Webmozart\Assert\Assert;

#[Group('AsyncAws')]
#[Group('integration')]
class FixturesLoadCommandTest extends IntegrationTestCase
{
    use AwsSdkIntegrationTrait;

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(array $options, Composite $expected): void
    {
        $command = $this->createCommand();

        $path = realpath(dirname(__DIR__) . '/../../Fixtures');

        Assert::notFalse($path, 'Unable to load fixtures - path is invalid');

        $tester = new CommandTester($command);
        $tester->execute(array_merge(
            [
                '--path' => $path,
            ],
            $options
        ));

        $tester->assertCommandIsSuccessful();

        $this->assertThat($this->dynamoDbClient, $expected);
    }

    /**
     * @return iterable<string, array{options: array<string, mixed>, expected: Composite}>
     */
    public static function executeDataProvider(): iterable
    {
        yield 'group1-only' => [
            'options' => [
                '--group' => ['group1'],
            ],
            'expected' => new Composite(
                new Delegate(
                    new IsIdentical(['Table1']),
                    function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->listTables([
                            'Limit' => 10,
                        ]);

                        return $response['TableNames'];
                    },
                    'tables-list'
                ),
                new Delegate(
                    new IsIdentical(1),
                    static function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->scan([
                            'TableName' => 'Table1',
                        ]);

                        return $response['Count'];
                    },
                    'table1-has-records'
                )
            ),
        ];

        yield 'group2-only' => [
            'options' => [
                '--group' => ['group2'],
            ],
            'expected' => new Composite(
                new Delegate(
                    new IsIdentical(['Table2']),
                    function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->listTables([
                            'Limit' => 10,
                        ]);

                        return $response['TableNames'];
                    },
                    'tables-list'
                ),
                new Delegate(
                    new IsIdentical(2),
                    static function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->scan([
                            'TableName' => 'Table2',
                        ]);

                        return $response['Count'];
                    },
                    'table2-has-records'
                )
            ),
        ];

        yield 'only-tables' => [
            'options' => [
                '--only-tables' => true,
            ],
            'expected' => new Composite(
                new Delegate(
                    new IsIdentical(['Table1', 'Table2']),
                    function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->listTables([
                            'Limit' => 10,
                        ]);

                        return $response['TableNames'];
                    },
                    'tables-list'
                ),
                new Delegate(
                    new IsIdentical(0),
                    static function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->scan([
                            'TableName' => 'Table1',
                        ]);

                        return $response['Count'];
                    },
                    'table1-has-no-records'
                ),
                new Delegate(
                    new IsIdentical(0),
                    static function (DynamoDbClient $dynamoDbClient) {
                        $response = $dynamoDbClient->scan([
                            'TableName' => 'Table2',
                        ]);

                        return $response['Count'];
                    },
                    'table2-has-no-records'
                )
            ),
        ];
    }

    private function createCommand(): FixturesLoadCommand
    {
        return new FixturesLoadCommand(
            new Loader($this->createValidator(), $this->createSerializer()),
            new Executor($this->createClient())
        );
    }
}
