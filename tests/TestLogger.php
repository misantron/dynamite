<?php

declare(strict_types=1);

namespace Dynamite\Tests;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class TestLogger implements LoggerInterface
{
    use LoggerTrait;

    public array $recordsByLevel = [];

    public function hasRecords(string $level): bool
    {
        return isset($this->recordsByLevel[$level]);
    }

    public function hasRecord(string|array $record, string $level): bool
    {
        if (\is_string($record)) {
            $record = [
                'message' => $record,
            ];
        }

        return $this->hasRecordThatPasses(function ($rec) use ($record) {
            if ($rec['message'] !== $record['message']) {
                return false;
            }
            if (isset($record['context']) && $rec['context'] !== $record['context']) {
                return false;
            }
            return true;
        }, $level);
    }

    public function hasRecordThatPasses(callable $predicate, string $level): bool
    {
        if (!isset($this->recordsByLevel[$level])) {
            return false;
        }
        foreach ($this->recordsByLevel[$level] as $i => $rec) {
            if ($predicate($rec, $i)) {
                return true;
            }
        }
        return false;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $record = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->recordsByLevel[$record['level']][] = $record;
    }

    public function reset(): void
    {
        $this->recordsByLevel = [];
    }
}
