<?php

declare(strict_types=1);

namespace MonkeysLegion\Entity\Observers;

/**
 * Base class for entity observers.
 * All methods are optional, but following this structure will allow
 * the EntityManager/Repository to trigger them.
 * 
 * @template T of object
 */
abstract class EntityObserver
{
    /**
     * Triggered before an entity is created in the database.
     * @param T $entity
     */
    public function creating(object $entity): void {}

    /**
     * Triggered after an entity is created in the database.
     * @param T $entity
     */
    public function created(object $entity): void {}

    /**
     * Triggered before an entity is updated in the database.
     * @param T $entity
     */
    public function updating(object $entity): void {}

    /**
     * Triggered after an entity is updated in the database.
     * @param T $entity
     */
    public function updated(object $entity): void {}

    /**
     * Triggered before an entity is saved (called for both create and update operations).
     * @param T $entity
     */
    public function saving(object $entity): void {}

    /**
     * Triggered after an entity is saved (called for both create and update operations).
     * @param T $entity
     */
    public function saved(object $entity): void {}

    /**
     * Triggered before an entity is deleted from the database.
     * @param T $entity
     */
    public function deleting(object $entity): void {}

    /**
     * Triggered after an entity is deleted from the database.
     * @param T $entity
     */
    public function deleted(object $entity): void {}

    /**
     * Triggered after an entity is hydrated from the database.
     * @param T $entity
     */
    public function hydrated(object $entity): void {}
}
