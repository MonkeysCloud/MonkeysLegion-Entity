<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Observers;

use MonkeysLegion\Entity\Attributes\ObservedBy;
use ReflectionClass;
use ReflectionException;

/**
 * Dispatches lifecycle events to entity observers.
 */
final class LifecycleDispatcher
{
    /**
     * @var array<string, object> List of instantiated observers to avoid re-instantiation.
     */
    private static array $observers = [];

    /**
     * @var array<string, array<string>> Cached observer classes for entity classes.
     */
    private static array $observerClasses = [];

    /**
     * Dispatch a lifecycle event to the entity's observer.
     *
     * @param string $event The name of the event (e.g., 'creating', 'created', etc.)
     * @param object $entity The entity instance.
     * @throws ReflectionException
     */
    public static function dispatch(string $event, object $entity): void
    {
        $observerClasses = self::getObserverClasses(get_class($entity));

        foreach ($observerClasses as $observerClass) {
            $observer = self::getObserverInstance($observerClass);

            if (method_exists($observer, $event)) {
                $observer->$event($entity);
            }
        }
    }

    /**
     * Get the observer classes for an entity.
     *
     * @param string $entityClass The class name of the entity.
     * @return array<string> The class names of the observers.
     * @throws ReflectionException
     */
    public static function getObserverClasses(string $entityClass): array
    {
        if (array_key_exists($entityClass, self::$observerClasses)) {
            return self::$observerClasses[$entityClass];
        }

        $ref = new ReflectionClass($entityClass);
        $attributes = $ref->getAttributes(ObservedBy::class);

        if (empty($attributes)) {
            self::$observerClasses[$entityClass] = [];
            return [];
        }

        $classes = [];
        foreach ($attributes as $attr) {
            /** @var ObservedBy $observedBy */
            $observedBy = $attr->newInstance();
            $obs = $observedBy->observer;

            if (is_array($obs)) {
                foreach ($obs as $o) {
                    $classes[] = (string)$o;
                }
            } else {
                $classes[] = (string)$obs;
            }
        }

        self::$observerClasses[$entityClass] = $classes;
        return $classes;
    }

    /**
     * Get or create an instance of the observer.
     *
     * @param class-string $observerClass The class name of the observer.
     * @return object The instance of the observer.
     */
    private static function getObserverInstance(string $observerClass): object
    {
        if (isset(self::$observers[$observerClass])) {
            return self::$observers[$observerClass];
        }

        // Ideally, we'd use a DI container here, but we'll provide a simple instantiation for now.
        // If a container is available globally (e.g., in a framework), it could be set here.
        $observer = new $observerClass();
        self::$observers[$observerClass] = $observer;

        return $observer;
    }

    /**
     * Set a specific observer instance (e.g., for testing or manual registration).
     *
     * @param class-string $observerClass The class name of the observer.
     * @param object $instance The instance.
     */
    public static function setObserverInstance(string $observerClass, object $instance): void
    {
        self::$observers[$observerClass] = $instance;
    }

    /**
     * Clear the observer instances (primarily for testing).
     */
    public static function clearObservers(): void
    {
        self::$observers = [];
        self::$observerClasses = [];
    }
}
