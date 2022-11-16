<?php

declare(strict_types=1);

namespace Dynamite;

trait TableTrait
{
    private bool $isConfigured = false;

    final public function getTableName(): ?string
    {
        $this->initialize();

        return $this->schema->getTableName();
    }

    protected function setTableName(string $tableName): self
    {
        $this->schema->setTableName($tableName);

        return $this;
    }

    private function initialize(): void
    {
        if (!$this->isConfigured) {
            $this->configure();
            $this->isConfigured = true;
        }
    }
}
