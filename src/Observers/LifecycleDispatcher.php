<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Observers;

use MonkeysLegion\Entity\Attributes\Subscribe;
use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use MonkeysLegion\Entity\Support\EntityEvent;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Dispatches lifecycle events to entity observers and global subscribers.
 *
 * v2 improvements:
 *  • DI container integration for observer resolution
 *  • Uses MetadataRegistry instead of direct reflection
 *  • Supports restoring/restored events for soft delete
 *  • Supports replicating event
 *  • Dispatches to both per-entity observers and global subscribers
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class LifecycleDispatcher
{
    /** @var array<string, object> Cached observer instances. */
    private static array $observers = [];

    /**
     * Global subscriber instances registered via registerSubscriber().
     *
     * Each entry: ['instance' => object, 'entities' => list<class-string>]
     *
     * @var array<string, array{instance: object, entities: list<class-string>}>
     */
    private static array $subscribers = [];

    /** @var ContainerInterface|null Optional DI container for observer resolution. */
    private static ?ContainerInterface $container = null;

    /**
     * Set the DI container for observer resolution.
     */
    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Register a global subscriber class (decorated with #[Subscribe]).
     *
     * Subscribers receive lifecycle events alongside an EntityEvent value
     * object that carries the full event context, including changed fields.
     * Unlike per-entity observers, a single subscriber can listen to events
     * for multiple entity types — or all entities when #[Subscribe] is used
     * with an empty `entities` list.
     *
     * ```php
     * #[Subscribe(entities: [Order::class])]
     * class AuditSubscriber {
     *     public function created(object $entity, EntityEvent $event): void { ... }
     * }
     *
     * LifecycleDispatcher::registerSubscriber(AuditSubscriber::class);
     * ```
     *
     * @param class-string $subscriberClass
     */
    public static function registerSubscriber(string $subscriberClass): void
    {
        if (isset(self::$subscribers[$subscriberClass])) {
            return;
        }

        $ref      = new ReflectionClass($subscriberClass);
        $attrList = $ref->getAttributes(Subscribe::class);

        if ($attrList === []) {
            throw new \InvalidArgumentException(sprintf(
                'Class "%s" must be decorated with #[Subscribe] to be registered as a subscriber.',
                $subscriberClass,
            ));
        }

        $instance = self::$container !== null && self::$container->has($subscriberClass)
            ? self::$container->get($subscriberClass)
            : new $subscriberClass();

        $entities = $attrList[0]->newInstance()->entities;

        self::$subscribers[$subscriberClass] = [
            'instance' => $instance,
            'entities' => $entities,
        ];
    }

    /**
     * Dispatch a lifecycle event to the entity's observers and global subscribers.
     *
     * @param string               $event   Event name (creating, created, etc.)
     * @param object               $entity  The entity instance.
     * @param array<string, mixed> $changes Changed fields (for update events).
     */
    public static function dispatch(
        string $event,
        object $entity,
        array $changes = [],
    ): void {
        $meta        = MetadataRegistry::for($entity::class);
        $entityEvent = new EntityEvent(
            event: $event,
            entity: $entity,
            changes: $changes,
        );

        // Per-entity observers (#[ObservedBy]) — receive entity only
        foreach ($meta->observers as $observerClass) {
            $observer = self::resolveObserver($observerClass);

            if (method_exists($observer, $event)) {
                $observer->{$event}($entity);
            }
        }

        // Global subscribers (#[Subscribe]) — receive entity + EntityEvent
        foreach (self::$subscribers as $data) {
            $entities = $data['entities'];
            $instance = $data['instance'];

            // Empty entities list means "all entities"
            if ($entities !== [] && !in_array($entity::class, $entities, true)) {
                continue;
            }

            if (method_exists($instance, $event)) {
                $instance->{$event}($entity, $entityEvent);
            }
        }
    }

    /**
     * Set a specific observer instance (for testing or manual registration).
     *
     * @param class-string $observerClass
     */
    public static function setObserverInstance(string $observerClass, object $instance): void
    {
        self::$observers[$observerClass] = $instance;
    }

    /**
     * Inject a pre-built subscriber instance (for testing).
     *
     * The entities filter is read from the class's #[Subscribe] attribute;
     * pass an explicit list to override.
     *
     * @param class-string       $subscriberClass
     * @param list<class-string> $entities Override the entities filter (empty = read from attribute).
     */
    public static function setSubscriberInstance(
        string $subscriberClass,
        object $instance,
        array $entities = [],
    ): void {
        if ($entities === []) {
            $ref = new ReflectionClass($subscriberClass);
            foreach ($ref->getAttributes(Subscribe::class) as $attr) {
                $entities = $attr->newInstance()->entities;
                break;
            }
        }

        self::$subscribers[$subscriberClass] = [
            'instance' => $instance,
            'entities' => $entities,
        ];
    }

    /**
     * Clear all cached observers, subscribers, and container reference (primarily for testing).
     */
    public static function clearObservers(): void
    {
        self::$observers   = [];
        self::$subscribers = [];
        self::$container   = null;
    }

    // ── Internal ───────────────────────────────────────────────

    /**
     * Resolve an observer instance via DI container or direct instantiation.
     */
    private static function resolveObserver(string $observerClass): object
    {
        if (isset(self::$observers[$observerClass])) {
            return self::$observers[$observerClass];
        }

        // Try DI container first
        if (self::$container !== null && self::$container->has($observerClass)) {
            $observer = self::$container->get($observerClass);
            self::$observers[$observerClass] = $observer;
            return $observer;
        }

        // Fallback to direct instantiation
        $observer = new $observerClass();
        self::$observers[$observerClass] = $observer;
        return $observer;
    }
}
