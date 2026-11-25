<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model;

/**
 * Per-request buffer. Shared instance: holds the admin user and every model change
 * made while the current admin action runs, so they are written as one log row.
 */
class RequestCollector
{
    private const MAX_ENTITIES = 200;

    /**
     * @var array|null ['id' => int, 'name' => string]
     */
    private $user;

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var bool
     */
    private $flushed = false;

    /**
     * @var bool
     */
    private $shutdownRegistered = false;

    public function setUser(int $id, string $name): void
    {
        $this->user = ['id' => $id, 'name' => $name];
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function addEntity(array $entity): void
    {
        if (count($this->entities) < self::MAX_ENTITIES) {
            $this->entities[] = $entity;
        }
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function isFlushed(): bool
    {
        return $this->flushed;
    }

    public function markFlushed(): void
    {
        $this->flushed = true;
    }

    /**
     * Returns true only the first time, so the shutdown hook is registered once.
     */
    public function registerShutdownOnce(): bool
    {
        if ($this->shutdownRegistered) {
            return false;
        }
        $this->shutdownRegistered = true;
        return true;
    }
}
