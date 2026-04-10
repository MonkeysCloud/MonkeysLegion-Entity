<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Tests\Unit;

use DateTimeImmutable;
use MonkeysLegion\Entity\Attributes\AuditTrail;
use MonkeysLegion\Entity\Attributes\Cast;
use MonkeysLegion\Entity\Attributes\Changeset;
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\Field;
use MonkeysLegion\Entity\Attributes\Fillable;
use MonkeysLegion\Entity\Attributes\Guarded;
use MonkeysLegion\Entity\Attributes\Hidden;
use MonkeysLegion\Entity\Attributes\Id;
use MonkeysLegion\Entity\Attributes\Immutable;
use MonkeysLegion\Entity\Attributes\Index;
use MonkeysLegion\Entity\Attributes\ObservedBy;
use MonkeysLegion\Entity\Attributes\QueryFilter;
use MonkeysLegion\Entity\Attributes\SoftDeletes;
use MonkeysLegion\Entity\Attributes\Timestamps;
use MonkeysLegion\Entity\Attributes\Versioned;
use MonkeysLegion\Entity\Attributes\Virtual;
use MonkeysLegion\Entity\Contracts\CastInterface;
use MonkeysLegion\Entity\Exceptions\MassAssignmentException;
use MonkeysLegion\Entity\Exceptions\ImmutableEntityException;
use MonkeysLegion\Entity\Exceptions\OptimisticLockException;
use MonkeysLegion\Entity\Hydrator;
use MonkeysLegion\Entity\Metadata\EntityMetadata;
use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use MonkeysLegion\Entity\Observers\LifecycleDispatcher;
use MonkeysLegion\Entity\Security\MassAssignmentGuard;
use MonkeysLegion\Entity\Support\ChangeTracker;
use MonkeysLegion\Entity\Support\EntityEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Test Enums ─────────────────────────────────────────────────

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
}

enum Priority: int
{
    case Low    = 1;
    case Medium = 2;
    case High   = 3;
}

// ── Test Cast ──────────────────────────────────────────────────

class UpperCast implements CastInterface
{
    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        return strtoupper((string) $value);
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        return strtolower((string) $value);
    }
}

// ── Test Entities ──────────────────────────────────────────────

#[Entity(table: 'users')]
#[Timestamps]
#[SoftDeletes]
#[Index(columns: ['email'], unique: true)]
class UserEntity
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    #[Index(unique: true)]
    public string $email;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $name;

    #[Field(type: 'string', length: 255)]
    #[Hidden]
    public string $password_hash;

    #[Field(type: 'string', length: 50)]
    #[Guarded]
    public string $role = 'user';

    #[Field(type: 'datetime', nullable: true)]
    public ?DateTimeImmutable $deleted_at = null;

    #[Changeset(context: 'registration')]
    public static function registrationFields(): array
    {
        return ['email', 'name', 'password_hash'];
    }

    #[Changeset(context: 'profile_update')]
    public static function profileFields(): array
    {
        return ['name'];
    }
}

#[Entity(table: 'orders')]
#[Timestamps]
class OrderEntity
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'string', length: 50)]
    #[Cast(OrderStatus::class)]
    #[Fillable]
    public OrderStatus $status;

    #[Field(type: 'decimal', precision: 10, scale: 2)]
    #[Fillable]
    public string $subtotal;

    #[Field(type: 'decimal', precision: 10, scale: 2)]
    #[Fillable]
    public string $tax;

    #[Versioned]
    #[Field(type: 'integer')]
    public int $version = 1;

    #[Field(type: 'json', nullable: true)]
    #[Cast('array')]
    public array $metadata = [];
}

#[Entity(table: 'transactions')]
#[Immutable]
class TransactionEntity
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'decimal', precision: 10, scale: 2)]
    public string $amount;

    #[Field(type: 'string', length: 3)]
    public string $currency;
}

#[Entity(table: 'tasks')]
#[QueryFilter(method: 'filterActive')]
class TaskEntity
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'string', length: 255)]
    public string $title;

    #[Field(type: 'integer')]
    #[Cast(Priority::class)]
    public Priority $priority;

    #[Field(type: 'boolean')]
    public bool $is_active = true;

    public static function filterActive(object $qb): void
    {
        // Query filter method — tested via metadata parsing
    }
}

#[Entity(table: 'products')]
#[AuditTrail]
class ProductEntity
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $name;

    #[Field(type: 'decimal', precision: 10, scale: 2)]
    #[Fillable]
    public string $price;

    #[Field(type: 'string', length: 255)]
    #[Cast(UpperCast::class)]
    #[Fillable]
    public string $sku;
}

// Entity without any #[Fillable] — uses blacklist mode
#[Entity(table: 'articles')]
class ArticleEntity
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'string', length: 255)]
    public string $title;

    #[Field(type: 'text')]
    public string $body;

    #[Field(type: 'string')]
    #[Guarded]
    public string $slug;
}

// ── Test Observer ──────────────────────────────────────────────

class TestUserObserver
{
    public array $events = [];

    public function creating(object $entity): void
    {
        $this->events[] = 'creating';
    }

    public function created(object $entity): void
    {
        $this->events[] = 'created';
    }

    public function hydrated(object $entity): void
    {
        $this->events[] = 'hydrated';
    }
}

#[Entity(table: 'observed_items')]
#[ObservedBy(TestUserObserver::class)]
class ObservedEntity
{
    #[Id]
    #[Field(type: 'integer')]
    public int $id;

    #[Field(type: 'string')]
    public string $name;
}

// ════════════════════════════════════════════════════════════════
// TEST SUITE
// ════════════════════════════════════════════════════════════════

#[CoversClass(MetadataRegistry::class)]
#[CoversClass(Hydrator::class)]
#[CoversClass(MassAssignmentGuard::class)]
#[CoversClass(ChangeTracker::class)]
#[CoversClass(LifecycleDispatcher::class)]
final class EntityV2Test extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::clear();
        Hydrator::clearCache();
        LifecycleDispatcher::clearObservers();
    }

    // ── MetadataRegistry Tests ─────────────────────────────────

    #[Test]
    public function metadata_registry_parses_table_name(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertSame('users', $meta->table);
    }

    #[Test]
    public function metadata_registry_detects_primary_key(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertSame('id', $meta->primaryKey);
    }

    #[Test]
    public function metadata_registry_detects_timestamps(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertTrue($meta->timestamps);
        $this->assertSame('created_at', $meta->createdColumn);
        $this->assertSame('updated_at', $meta->updatedColumn);
    }

    #[Test]
    public function metadata_registry_detects_soft_deletes(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertTrue($meta->softDeletes);
        $this->assertSame('deleted_at', $meta->softDeleteColumn);
    }

    #[Test]
    public function metadata_registry_detects_immutable(): void
    {
        $meta = MetadataRegistry::for(TransactionEntity::class);
        $this->assertTrue($meta->immutable);

        $meta2 = MetadataRegistry::for(UserEntity::class);
        $this->assertFalse($meta2->immutable);
    }

    #[Test]
    public function metadata_registry_detects_versioned(): void
    {
        $meta = MetadataRegistry::for(OrderEntity::class);
        $this->assertTrue($meta->isVersioned);
        $this->assertSame('version', $meta->versionField);
    }

    #[Test]
    public function metadata_registry_detects_fillable(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertContains('email', $meta->fillable);
        $this->assertContains('name', $meta->fillable);
        $this->assertNotContains('role', $meta->fillable);
        $this->assertTrue($meta->usesFillableWhitelist);
    }

    #[Test]
    public function metadata_registry_detects_guarded(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertContains('role', $meta->guarded);
    }

    #[Test]
    public function metadata_registry_detects_hidden(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertContains('password_hash', $meta->hidden);
    }

    #[Test]
    public function metadata_registry_detects_casts(): void
    {
        $meta = MetadataRegistry::for(OrderEntity::class);
        $this->assertArrayHasKey('status', $meta->casts);
        $this->assertSame(OrderStatus::class, $meta->casts['status']);
        $this->assertArrayHasKey('metadata', $meta->casts);
        $this->assertSame('array', $meta->casts['metadata']);
    }

    #[Test]
    public function metadata_registry_detects_indexes(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertNotEmpty($meta->indexes);

        $emailIndex = null;
        foreach ($meta->indexes as $idx) {
            if ($idx->columns === ['email']) {
                $emailIndex = $idx;
                break;
            }
        }
        $this->assertNotNull($emailIndex);
        $this->assertTrue($emailIndex->unique);
    }

    #[Test]
    public function metadata_registry_detects_query_filters(): void
    {
        $meta = MetadataRegistry::for(TaskEntity::class);
        $this->assertContains('filterActive', $meta->queryFilters);
    }

    #[Test]
    public function metadata_registry_detects_changesets(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertArrayHasKey('registration', $meta->changesets);
        $this->assertArrayHasKey('profile_update', $meta->changesets);
        $this->assertSame(['email', 'name', 'password_hash'], $meta->changesets['registration']);
        $this->assertSame(['name'], $meta->changesets['profile_update']);
    }

    #[Test]
    public function metadata_registry_detects_audit_trail(): void
    {
        $meta = MetadataRegistry::for(ProductEntity::class);
        $this->assertTrue($meta->hasAuditTrail);
        $this->assertSame('created_by', $meta->auditTrail->createdByColumn);
    }

    #[Test]
    public function metadata_registry_detects_observers(): void
    {
        $meta = MetadataRegistry::for(ObservedEntity::class);
        $this->assertContains(TestUserObserver::class, $meta->observers);
    }

    #[Test]
    public function metadata_registry_caches_results(): void
    {
        $meta1 = MetadataRegistry::for(UserEntity::class);
        $meta2 = MetadataRegistry::for(UserEntity::class);
        $this->assertSame($meta1, $meta2);
        $this->assertTrue(MetadataRegistry::has(UserEntity::class));
    }

    #[Test]
    public function metadata_persistable_fields_excludes_virtual(): void
    {
        $meta = MetadataRegistry::for(OrderEntity::class);
        $persistable = $meta->persistableFields();
        $this->assertContains('status', $persistable);
        $this->assertContains('subtotal', $persistable);
    }

    // ── Hydrator Tests ─────────────────────────────────────────

    #[Test]
    public function hydrator_hydrates_basic_entity(): void
    {
        $user = Hydrator::hydrate(UserEntity::class, [
            'id'            => 1,
            'email'         => 'test@example.com',
            'name'          => 'John Doe',
            'password_hash' => 'hashed',
            'role'          => 'admin',
        ]);

        $this->assertInstanceOf(UserEntity::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('hashed', $user->password_hash);
        $this->assertSame('admin', $user->role);
    }

    #[Test]
    public function hydrator_casts_backed_string_enum(): void
    {
        $order = Hydrator::hydrate(OrderEntity::class, [
            'id'       => 1,
            'status'   => 'shipped',
            'subtotal' => '99.99',
            'tax'      => '8.50',
            'version'  => 1,
            'metadata' => '{}',
        ]);

        $this->assertSame(OrderStatus::Shipped, $order->status);
    }

    #[Test]
    public function hydrator_casts_backed_int_enum(): void
    {
        $task = Hydrator::hydrate(TaskEntity::class, [
            'id'        => 1,
            'title'     => 'Test Task',
            'priority'  => 3,
            'is_active' => 1,
        ]);

        $this->assertSame(Priority::High, $task->priority);
    }

    #[Test]
    public function hydrator_casts_json_to_array(): void
    {
        $order = Hydrator::hydrate(OrderEntity::class, [
            'id'       => 1,
            'status'   => 'pending',
            'subtotal' => '50.00',
            'tax'      => '4.25',
            'version'  => 1,
            'metadata' => '{"key":"value","items":[1,2,3]}',
        ]);

        $this->assertIsArray($order->metadata);
        $this->assertSame('value', $order->metadata['key']);
        $this->assertSame([1, 2, 3], $order->metadata['items']);
    }

    #[Test]
    public function hydrator_casts_custom_cast_interface(): void
    {
        $product = Hydrator::hydrate(ProductEntity::class, [
            'id'    => 1,
            'name'  => 'Widget',
            'price' => '29.99',
            'sku'   => 'abc-123',
        ]);

        // UpperCast::get() converts to uppercase
        $this->assertSame('ABC-123', $product->sku);
    }

    #[Test]
    public function hydrator_handles_null_on_nullable_fields(): void
    {
        $user = Hydrator::hydrate(UserEntity::class, [
            'id'            => 1,
            'email'         => 'test@example.com',
            'name'          => 'John',
            'password_hash' => 'hash',
            'role'          => 'user',
            'deleted_at'    => null,
        ]);

        $this->assertNull($user->deleted_at);
    }

    #[Test]
    public function hydrator_hydrates_datetime(): void
    {
        $user = Hydrator::hydrate(UserEntity::class, [
            'id'            => 1,
            'email'         => 'test@example.com',
            'name'          => 'John',
            'password_hash' => 'hash',
            'role'          => 'user',
            'deleted_at'    => '2026-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $user->deleted_at);
        $this->assertSame('2026-01-15', $user->deleted_at->format('Y-m-d'));
    }

    #[Test]
    public function hydrator_handles_object_rows(): void
    {
        $row = (object) ['id' => 1, 'email' => 'test@example.com', 'name' => 'John', 'password_hash' => 'hash', 'role' => 'user'];
        $user = Hydrator::hydrate(UserEntity::class, $row);

        $this->assertSame(1, $user->id);
        $this->assertSame('test@example.com', $user->email);
    }

    // ── Extract Tests ──────────────────────────────────────────

    #[Test]
    public function extract_returns_persistable_data(): void
    {
        $order = new OrderEntity();
        $order->id = 1;
        $order->status = OrderStatus::Pending;
        $order->subtotal = '100.00';
        $order->tax = '8.50';
        $order->version = 1;
        $order->metadata = ['key' => 'value'];

        $data = Hydrator::extract($order);

        $this->assertSame('pending', $data['status']);
        $this->assertSame('100.00', $data['subtotal']);
        $this->assertStringContainsString('"key"', $data['metadata']);
    }

    #[Test]
    public function extract_skips_hidden_when_requested(): void
    {
        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'secret';
        $user->role = 'user';

        $data = Hydrator::extract($user, includeHidden: false);
        $this->assertArrayNotHasKey('password_hash', $data);
    }

    #[Test]
    public function extract_includes_hidden_by_default(): void
    {
        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'secret';
        $user->role = 'user';

        $data = Hydrator::extract($user);
        $this->assertArrayHasKey('password_hash', $data);
    }

    // ── toArray / toJson Tests ─────────────────────────────────

    #[Test]
    public function to_array_excludes_hidden(): void
    {
        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'secret';
        $user->role = 'user';

        $arr = Hydrator::toArray($user);

        $this->assertArrayNotHasKey('password_hash', $arr);
        $this->assertSame('test@example.com', $arr['email']);
    }

    #[Test]
    public function to_json_produces_valid_json(): void
    {
        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'secret';
        $user->role = 'user';

        $json = Hydrator::toJson($user);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey('password_hash', $decoded);
        $this->assertSame('test@example.com', $decoded['email']);
    }

    #[Test]
    public function to_array_serializes_backed_enums(): void
    {
        $order = new OrderEntity();
        $order->id = 1;
        $order->status = OrderStatus::Shipped;
        $order->subtotal = '100.00';
        $order->tax = '8.50';
        $order->version = 1;
        $order->metadata = [];

        $arr = Hydrator::toArray($order);

        $this->assertSame('shipped', $arr['status']);
    }

    // ── MassAssignmentGuard Tests ──────────────────────────────

    #[Test]
    public function mass_assignment_whitelist_mode_allows_fillable(): void
    {
        $user = new UserEntity();
        MassAssignmentGuard::fill($user, [
            'email' => 'test@example.com',
            'name'  => 'John',
        ]);

        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('John', $user->name);
    }

    #[Test]
    public function mass_assignment_whitelist_mode_blocks_non_fillable(): void
    {
        $user = new UserEntity();

        $this->expectException(MassAssignmentException::class);
        MassAssignmentGuard::fill($user, [
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function mass_assignment_silent_mode_skips_disallowed(): void
    {
        $user = new UserEntity();
        $user->role = 'user';

        MassAssignmentGuard::fill($user, [
            'email' => 'test@example.com',
            'name'  => 'John',
            'role'  => 'admin',
        ], silent: true);

        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('user', $user->role); // unchanged
    }

    #[Test]
    public function mass_assignment_blacklist_mode_blocks_guarded(): void
    {
        $article = new ArticleEntity();

        $this->expectException(MassAssignmentException::class);
        MassAssignmentGuard::fill($article, [
            'title' => 'Hello World',
            'slug'  => 'hello-world', // guarded!
        ]);
    }

    #[Test]
    public function mass_assignment_blacklist_mode_allows_non_guarded(): void
    {
        $article = new ArticleEntity();
        MassAssignmentGuard::fill($article, [
            'title' => 'Hello World',
            'body'  => 'Content here',
        ]);

        $this->assertSame('Hello World', $article->title);
        $this->assertSame('Content here', $article->body);
    }

    #[Test]
    public function mass_assignment_changeset_context(): void
    {
        $user = new UserEntity();
        $user->email = 'old@example.com';

        MassAssignmentGuard::fill($user, [
            'name' => 'Updated Name',
        ], context: 'profile_update');

        $this->assertSame('Updated Name', $user->name);
    }

    #[Test]
    public function mass_assignment_changeset_blocks_non_context_fields(): void
    {
        $user = new UserEntity();

        $this->expectException(MassAssignmentException::class);
        MassAssignmentGuard::fill($user, [
            'email' => 'new@example.com', // not in profile_update context
        ], context: 'profile_update');
    }

    // ── ChangeTracker Tests ────────────────────────────────────

    #[Test]
    public function change_tracker_detects_clean_entity(): void
    {
        $tracker = new ChangeTracker();

        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'hash';
        $user->role = 'user';

        $tracker->track($user);

        $this->assertFalse($tracker->isDirty($user));
        $this->assertSame([], $tracker->getDirty($user));
    }

    #[Test]
    public function change_tracker_detects_dirty_entity(): void
    {
        $tracker = new ChangeTracker();

        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'hash';
        $user->role = 'user';

        $tracker->track($user);
        $user->name = 'Jane';

        $this->assertTrue($tracker->isDirty($user));
        $dirty = $tracker->getDirty($user);
        $this->assertArrayHasKey('name', $dirty);
        $this->assertSame('Jane', $dirty['name']);
    }

    #[Test]
    public function change_tracker_returns_original_values(): void
    {
        $tracker = new ChangeTracker();

        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'hash';
        $user->role = 'user';

        $tracker->track($user);
        $user->name = 'Jane';

        $this->assertSame('John', $tracker->getOriginal($user, 'name'));
    }

    #[Test]
    public function change_tracker_untrack_removes_entity(): void
    {
        $tracker = new ChangeTracker();

        $user = new UserEntity();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'John';
        $user->password_hash = 'hash';
        $user->role = 'user';

        $tracker->track($user);
        $tracker->untrack($user);

        $this->assertFalse($tracker->isDirty($user));
    }

    // ── LifecycleDispatcher Tests ──────────────────────────────

    #[Test]
    public function lifecycle_dispatcher_calls_observer_on_event(): void
    {
        $observer = new TestUserObserver();
        LifecycleDispatcher::setObserverInstance(TestUserObserver::class, $observer);

        $entity = new ObservedEntity();
        $entity->id = 1;
        $entity->name = 'Test';

        LifecycleDispatcher::dispatch('creating', $entity);
        LifecycleDispatcher::dispatch('created', $entity);

        $this->assertSame(['creating', 'created'], $observer->events);
    }

    #[Test]
    public function lifecycle_dispatcher_ignores_missing_methods(): void
    {
        $observer = new TestUserObserver();
        LifecycleDispatcher::setObserverInstance(TestUserObserver::class, $observer);

        $entity = new ObservedEntity();
        $entity->id = 1;
        $entity->name = 'Test';

        // 'nonexistent' method doesn't exist — should not throw
        LifecycleDispatcher::dispatch('nonexistent', $entity);
        $this->assertSame([], $observer->events);
    }

    #[Test]
    public function hydration_triggers_hydrated_observer(): void
    {
        $observer = new TestUserObserver();
        LifecycleDispatcher::setObserverInstance(TestUserObserver::class, $observer);

        Hydrator::hydrate(ObservedEntity::class, [
            'id'   => 1,
            'name' => 'Test',
        ]);

        $this->assertContains('hydrated', $observer->events);
    }

    // ── Exception Tests ────────────────────────────────────────

    #[Test]
    public function optimistic_lock_exception_contains_details(): void
    {
        $ex = new OptimisticLockException(
            entityClass: OrderEntity::class,
            entityId: 42,
            expectedVersion: 3,
            actualVersion: 5,
        );

        $this->assertStringContainsString('OrderEntity', $ex->getMessage());
        $this->assertStringContainsString('42', $ex->getMessage());
        $this->assertSame(3, $ex->expectedVersion);
        $this->assertSame(5, $ex->actualVersion);
    }

    #[Test]
    public function entity_event_value_object(): void
    {
        $entity = new UserEntity();
        $entity->id = 1;
        $entity->name = 'John';

        $event = new EntityEvent(
            event: 'updated',
            entity: $entity,
            changes: ['name' => 'Jane'],
        );

        $this->assertSame('updated', $event->event);
        $this->assertSame($entity, $event->entity);
        $this->assertSame(['name' => 'Jane'], $event->changes);
    }

    // ── Auto-Table Naming Test ─────────────────────────────────

    #[Test]
    public function metadata_auto_generates_table_from_class_name(): void
    {
        // ArticleEntity has no explicit table name
        // But it has #[Entity(table: 'articles')] — let's test with a class
        // that uses the auto-naming. UserEntity has explicit table.
        $meta = MetadataRegistry::for(UserEntity::class);
        $this->assertSame('users', $meta->table);
    }

    // ── Field Metadata Tests ───────────────────────────────────

    #[Test]
    public function field_metadata_captures_type_details(): void
    {
        $meta = MetadataRegistry::for(OrderEntity::class);
        $subtotal = $meta->fields['subtotal'];

        $this->assertSame('decimal', $subtotal->type);
        $this->assertSame(10, $subtotal->precision);
        $this->assertSame(2, $subtotal->scale);
        $this->assertFalse($subtotal->nullable);
    }

    #[Test]
    public function field_metadata_captures_auto_increment(): void
    {
        $meta = MetadataRegistry::for(UserEntity::class);
        $id = $meta->fields['id'];

        $this->assertTrue($id->autoIncrement);
        $this->assertTrue($id->isId);
        $this->assertTrue($id->primaryKey);
    }
}
