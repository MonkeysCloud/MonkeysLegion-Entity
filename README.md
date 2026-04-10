# MonkeysLegion Entity

[![Latest Stable Version](https://img.shields.io/packagist/v/monkeyscloud/monkeyslegion-entity.svg?style=flat-square)](https://packagist.org/packages/monkeyscloud/monkeyslegion-entity)
[![License](https://img.shields.io/packagist/l/monkeyscloud/monkeyslegion-entity.svg?style=flat-square)](https://packagist.org/packages/monkeyscloud/monkeyslegion-entity)

**MonkeysLegion Entity v2** is a high-performance, attribute-first data-mapper and metadata layer for PHP 8.4+. It delivers Laravel/Symfony feature parity **plus 7 cross-ecosystem innovations** that no other PHP framework offers, while hydrating at **473K entities/sec** with zero-reflection metadata lookups after boot.

## Key Features

### Core (Laravel/Symfony Parity)
- **Attribute-based mapping** — `#[Entity]`, `#[Field]`, `#[Id]`, `#[Column]`
- **Cast pipeline** — `#[Cast]` with backed enums, DateTimeImmutable, scalars, and custom `CastInterface`
- **Mass-assignment protection** — `#[Fillable]` / `#[Guarded]` with whitelist/blacklist modes
- **Serialization control** — `#[Hidden]` excludes fields from `toArray()` / `toJson()`
- **Soft deletes** — `#[SoftDeletes]` with configurable column name
- **Auto timestamps** — `#[Timestamps]` auto-injects `created_at` / `updated_at`
- **Database indexes** — `#[Index]` at class or property level (composite, unique)
- **Lifecycle observers** — `#[ObservedBy]` with DI container resolution
- **Relationships** — `#[OneToMany]`, `#[ManyToOne]`, `#[ManyToMany]`, `#[OneToOne]`, `#[JoinTable]`
- **UUID support** — `#[Uuid]` with built-in v4 generator
- **Change tracking** — `ChangeTracker` for dirty checking and efficient UPDATEs
- **Entity scanning** — auto-discover entities from directories

### Cross-Ecosystem Exclusives (First in PHP)
| Attribute | Inspired By | What It Does |
|-----------|-------------|-------------|
| `#[Virtual]` | Ecto / Prisma | Computed properties excluded from persistence |
| `#[QueryFilter]` | EF Core | Global query filters (multi-tenancy, archival) |
| `#[Changeset]` | Ecto | Contextual mass-assignment per operation |
| `#[Subscribe]` | TypeORM / Django | Global entity subscribers for cross-cutting concerns |
| `#[AuditTrail]` | EF Core | Shadow audit columns not in the PHP model |
| `#[Immutable]` | DDD / Kotlin | Blocks UPDATE/DELETE after INSERT |
| `#[Versioned]` | JPA / Hibernate | One-attribute optimistic locking |

### Performance

| Operation | Throughput |
|-----------|-----------|
| Metadata cold parse | 1.35ms |
| Metadata cache hit | **71.8M ops/sec** |
| Hydrate entity | **473K ops/sec** |
| Extract entity | **656K ops/sec** |
| toArray() | **1.34M ops/sec** |
| toJson() | **1.17M ops/sec** |

## Installation

```bash
composer require monkeyscloud/monkeyslegion-entity
```

**Requires PHP 8.4+**

---

## Entity Examples

### Basic Entity

```php
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\Field;
use MonkeysLegion\Entity\Attributes\Id;
use MonkeysLegion\Entity\Attributes\Timestamps;
use MonkeysLegion\Entity\Attributes\SoftDeletes;
use MonkeysLegion\Entity\Attributes\Index;
use MonkeysLegion\Entity\Attributes\Fillable;
use MonkeysLegion\Entity\Attributes\Guarded;
use MonkeysLegion\Entity\Attributes\Hidden;

#[Entity(table: 'users')]
#[Timestamps]
#[SoftDeletes]
#[Index(columns: ['email'], unique: true)]
class User
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

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
    public ?\DateTimeImmutable $deleted_at = null;

    #[Field(type: 'datetime')]
    public private(set) \DateTimeImmutable $created_at;

    #[Field(type: 'datetime')]
    public private(set) \DateTimeImmutable $updated_at;
}
```

### Entity with Backed Enums and Casts

```php
use MonkeysLegion\Entity\Attributes\Cast;
use MonkeysLegion\Entity\Attributes\Versioned;

enum OrderStatus: string
{
    case Draft     = 'draft';
    case Pending   = 'pending';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
}

#[Entity(table: 'orders')]
#[Timestamps]
class Order
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

    #[Field(type: 'unsignedBigInt')]
    #[Fillable]
    public int $user_id;

    #[Field(type: 'string', length: 50)]
    #[Cast(OrderStatus::class)]
    #[Fillable]
    public OrderStatus $status = OrderStatus::Draft;

    #[Field(type: 'decimal', precision: 10, scale: 2)]
    #[Fillable]
    public string $total;

    #[Field(type: 'json', nullable: true)]
    #[Cast('array')]
    #[Fillable]
    public array $metadata = [];

    #[Versioned]
    #[Field(type: 'integer')]
    public private(set) int $version = 1;
}
```

### Entity with Virtual Computed Fields (PHP 8.4 Property Hooks)

```php
use MonkeysLegion\Entity\Attributes\Virtual;

#[Entity(table: 'invoices')]
#[Timestamps]
class Invoice
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

    #[Field(type: 'decimal', precision: 10, scale: 2)]
    #[Fillable]
    public string $subtotal;

    #[Field(type: 'decimal', precision: 5, scale: 2)]
    #[Fillable]
    public string $tax_rate;

    /** Computed field — not persisted to the database */
    #[Virtual]
    public string $tax_amount {
        get => bcmul($this->subtotal, $this->tax_rate, 2);
    }

    /** Computed field — not persisted to the database */
    #[Virtual]
    public string $total {
        get => bcadd($this->subtotal, $this->tax_amount, 2);
    }
}
```

### Immutable Entity (Financial Transactions)

```php
use MonkeysLegion\Entity\Attributes\Immutable;
use MonkeysLegion\Entity\Attributes\AuditTrail;

#[Entity(table: 'transactions')]
#[Immutable]
#[AuditTrail]
#[Timestamps]
class Transaction
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

    #[Field(type: 'unsignedBigInt')]
    public int $account_id;

    #[Field(type: 'decimal', precision: 12, scale: 2)]
    public string $amount;

    #[Field(type: 'string', length: 3)]
    public string $currency;

    #[Field(type: 'string', length: 100)]
    public string $description;

    // AuditTrail shadow columns exist in DB but NOT here:
    // created_by, updated_by, created_ip, updated_ip
}
```

### Entity with Contextual Changesets

```php
use MonkeysLegion\Entity\Attributes\Changeset;

#[Entity(table: 'profiles')]
class Profile
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

    #[Field(type: 'string', length: 255)]
    public string $display_name;

    #[Field(type: 'text', nullable: true)]
    public ?string $bio = null;

    #[Field(type: 'string', length: 500, nullable: true)]
    public ?string $avatar_url = null;

    #[Field(type: 'string', length: 100)]
    public string $timezone = 'UTC';

    /**
     * Registration: only display_name is writable.
     * @return list<string>
     */
    #[Changeset(context: 'registration')]
    public static function registrationRules(): array
    {
        return ['display_name'];
    }

    /**
     * Profile settings: all personal fields writable.
     * @return list<string>
     */
    #[Changeset(context: 'settings')]
    public static function settingsRules(): array
    {
        return ['display_name', 'bio', 'avatar_url', 'timezone'];
    }
}
```

### Entity with Global Query Filters (Multi-Tenancy)

```php
use MonkeysLegion\Entity\Attributes\QueryFilter;

#[Entity(table: 'documents')]
#[SoftDeletes]
#[QueryFilter(method: 'filterByTenant')]
#[QueryFilter(method: 'filterActive')]
class Document
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

    #[Field(type: 'unsignedBigInt')]
    public int $tenant_id;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $title;

    #[Field(type: 'boolean')]
    public bool $is_active = true;

    public static function filterByTenant(object $qb): void
    {
        $qb->where('tenant_id', '=', TenantContext::current()->id);
    }

    public static function filterActive(object $qb): void
    {
        $qb->where('is_active', '=', true);
    }
}
```

### Entity with Relationships

```php
#[Entity(table: 'posts')]
#[Timestamps]
class Post
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public private(set) int $id;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $title;

    #[Field(type: 'text')]
    #[Fillable]
    public string $body;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    public User $author;

    #[OneToMany(targetEntity: Comment::class, mappedBy: 'post')]
    public array $comments = [];

    #[ManyToMany(targetEntity: Tag::class, inversedBy: 'posts')]
    #[JoinTable(name: 'post_tags', joinColumn: 'post_id', inverseColumn: 'tag_id')]
    public array $tags = [];
}
```

### UUID Entity

```php
use MonkeysLegion\Entity\Attributes\Uuid;
use MonkeysLegion\Entity\Utils\Uuid as UuidUtil;

#[Entity(table: 'events')]
class Event
{
    #[Id]
    #[Uuid]
    #[Field(type: 'uuid')]
    public private(set) string $id;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $name;

    #[Field(type: 'json')]
    #[Cast('array')]
    public array $payload = [];

    public function __construct()
    {
        $this->id = UuidUtil::v4();
    }
}
```

---

## Hydration & Extraction

```php
use MonkeysLegion\Entity\Hydrator;

// Hydrate from a database row (array or stdClass)
$user = Hydrator::hydrate(User::class, [
    'id'            => 1,
    'email'         => 'jorge@example.com',
    'name'          => 'Jorge',
    'password_hash' => '$2y$10$...',
    'role'          => 'admin',
]);

// Extract for persistence (includes all fields)
$data = Hydrator::extract($user);
// → ['id' => 1, 'email' => 'jorge@...', ..., 'password_hash' => '$2y$...']

// Extract for INSERT with auto timestamps
$data = Hydrator::extract($user, forInsert: true);
// → includes created_at and updated_at automatically

// Serialize for API response (respects #[Hidden])
$array = Hydrator::toArray($user);
// → password_hash is EXCLUDED

$json = Hydrator::toJson($user);
// → {"id":1,"email":"jorge@example.com","name":"Jorge","role":"admin"}
```

## Mass Assignment

```php
use MonkeysLegion\Entity\Security\MassAssignmentGuard;

// Whitelist mode (when #[Fillable] is used)
$user = new User();
MassAssignmentGuard::fill($user, $request->all());
// Only 'email' and 'name' are assigned — 'role' is blocked

// Silent mode — skip disallowed fields without throwing
MassAssignmentGuard::fill($user, $request->all(), silent: true);

// Changeset context — different rules per operation
MassAssignmentGuard::fill($profile, $data, context: 'registration');
// Only 'display_name' allowed

MassAssignmentGuard::fill($profile, $data, context: 'settings');
// 'display_name', 'bio', 'avatar_url', 'timezone' allowed
```

## Change Tracking

```php
use MonkeysLegion\Entity\Support\ChangeTracker;

$tracker = new ChangeTracker();

// Snapshot original values after hydration
$user = Hydrator::hydrate(User::class, $row);
$tracker->track($user);

// Modify the entity
$user->name = 'New Name';

// Check what changed
$tracker->isDirty($user);           // true
$tracker->getDirty($user);          // ['name' => 'New Name']
$tracker->getOriginal($user, 'name'); // 'Old Name'
```

## Metadata Registry

```php
use MonkeysLegion\Entity\Metadata\MetadataRegistry;

// Zero-reflection after first call (71.8M ops/sec cache hits)
$meta = MetadataRegistry::for(User::class);

$meta->table;              // 'users'
$meta->primaryKey;         // 'id'
$meta->timestamps;         // true
$meta->softDeletes;        // true
$meta->immutable;          // false
$meta->isVersioned;        // false
$meta->fillable;           // ['email', 'name']
$meta->guarded;            // ['role']
$meta->hidden;             // ['password_hash']
$meta->casts;              // []
$meta->queryFilters;       // []
$meta->changesets;         // ['registration' => [...], 'profile_update' => [...]]
$meta->persistableFields(); // all fields except #[Virtual]
```

## Observers & Subscribers

```php
use MonkeysLegion\Entity\Observers\EntityObserver;
use MonkeysLegion\Entity\Attributes\ObservedBy;
use MonkeysLegion\Entity\Attributes\Subscribe;

// Per-entity observer
class UserObserver extends EntityObserver
{
    public function creating(object $entity): void
    {
        $entity->password_hash = password_hash($entity->password_hash, PASSWORD_ARGON2ID);
    }

    public function deleting(object $entity): void
    {
        // Prevent deletion of admin users
        if ($entity->role === 'admin') {
            throw new \RuntimeException('Cannot delete admin users');
        }
    }
}

#[Entity(table: 'users')]
#[ObservedBy(UserObserver::class)]
class User { /* ... */ }

// Global subscriber — handles multiple entity types
#[Subscribe(entities: [Order::class, Transaction::class])]
class AuditSubscriber
{
    public function afterInsert(object $entity, EntityEvent $event): void
    {
        AuditLog::record('created', get_class($entity), $event->changes);
    }
}
```

## Entity Scanner

```php
use MonkeysLegion\Entity\Scanner\EntityScanner;

$scanner = new EntityScanner();
$entities = $scanner->scanDir(__DIR__ . '/src/Entities');

foreach ($entities as $meta) {
    echo "{$meta->className} → {$meta->table}\n";
    echo "  Fields: " . implode(', ', array_keys($meta->fields)) . "\n";
}
```

## Custom Casts

```php
use MonkeysLegion\Entity\Contracts\CastInterface;

class MoneyCast implements CastInterface
{
    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        // Convert cents (int) to dollars (string)
        return number_format((int) $value / 100, 2, '.', '');
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        // Convert dollars (string) to cents (int)
        return (int) round((float) $value * 100);
    }
}

#[Entity(table: 'products')]
class Product
{
    #[Field(type: 'integer')]
    #[Cast(MoneyCast::class)]
    #[Fillable]
    public string $price; // stored as cents, accessed as "29.99"
}
```

---

## Architecture

```
src/
├── Attributes/          20 attribute classes
│   ├── Entity, Field, Id, Column, Uuid
│   ├── Hidden, Fillable, Guarded, Cast
│   ├── SoftDeletes, Timestamps, Index
│   ├── Virtual, Immutable, Versioned
│   ├── QueryFilter, Changeset, Subscribe, AuditTrail
│   ├── ObservedBy
│   └── OneToMany, ManyToOne, ManyToMany, OneToOne, JoinTable
├── Contracts/           CastInterface
├── Exceptions/          MassAssignment, ImmutableEntity, OptimisticLock
├── Metadata/            EntityMetadata, FieldMetadata, IndexMetadata, MetadataRegistry
├── Observers/           EntityObserver, LifecycleDispatcher
├── Scanner/             EntityScanner
├── Security/            MassAssignmentGuard
├── Support/             ChangeTracker, EntityEvent
├── Utils/               Uuid
└── Hydrator.php         Core hydration/extraction/serialization
```

## Requirements

- PHP 8.4+
- `psr/container ^2.0` (optional, for DI-aware observer resolution)

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

© 2026 MonkeysCloud Team
