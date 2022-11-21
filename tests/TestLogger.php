<?php

declare(strict_types=1);

namespace Dynamite\Tests;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class TestLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var array<string, array<int, array{
     *     level: string,
     *     message: string,
     *     context: array<string, mixed>
     * }>>
     */
    public array $recordsByLevel = [];

    /**
     * @param array{
     *     message: string,
     *     context: array<string, mixed>
     * } $record
     */
    public function hasRecord(array $record, string $level): bool
    {
        return $this->hasRecordThatPasses(
            static function (array $actual, array $expected): bool {
                if ($actual['message'] !== $expected['message']) {
                    return false;
                }
                if (isset($expected['context']) && $actual['context'] !== $expected['context']) {
                    return false;
                }
                return true;
            },
            $record,
            $level
        );
    }

    /**
     * @param array{
     *     message: string,
     *     context: array<string, mixed>
     * } $expected
     */
    public function hasRecordThatPasses(callable $predicate, array $expected, string $level): bool
    {
        if (!isset($this->recordsByLevel[$level])) {
            return false;
        }
        foreach ($this->recordsByLevel[$level] as $record) {
            if ($predicate($record, $expected)) {
                return true;
            }
        }
        return false;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $record = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];

        $this->recordsByLevel[$record['level']][] = $record;
    }

    public function reset(): void
    {
        $this->recordsByLevel = [];
    }
}
