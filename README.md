# MonkeysLegion Entity

[![Latest Stable Version](https://img.shields.io/packagist/v/monkeyscloud/monkeyslegion-entity.svg?style=flat-square)](https://packagist.org/packages/monkeyscloud/monkeyslegion-entity)
[![License](https://img.shields.io/packagist/l/monkeyscloud/monkeyslegion-entity.svg?style=flat-square)](https://packagist.org/packages/monkeyscloud/monkeyslegion-entity)

**MonkeysLegion Entity** is a lightweight, attribute-based data-mapper and entity scanner for PHP 8.4+. It provides a clean way to map database rows to PHP objects using attributes, scan directories for entities, and implement class-specific observers for lifecycle events.

## Features

- **Attribute-based Mapping**: Define entities and their fields using PHP 8.4+ attributes.
- **Flexible Hydration**: Map database rows to entities and extract data back for persistence with ease.
- **Entity Scanner**: Automatically discover entity classes in your project.
- **UUID Support**: Integrated UUID v4 generator and attribute for primary keys.
- **Class-Specific Observers**: Hook into entity lifecycle events (creating, updating, saving, deleting, hydrated) with dedicated observer classes.
- **Easy Integration**: Designed to be used with any repository or data-manager implementation.

## Installation

```bash
composer require monkeyscloud/monkeyslegion-entity
```

## Basic Usage

### Defining an Entity

Mark your class with the `#[Entity]` attribute and use `#[Column]` or `#[Field]` for its properties.

```php
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\Column;
use MonkeysLegion\Entity\Attributes\Id;

#[Entity(table: 'users')]
class User
{
    #[Id]
    #[Column]
    private int $id;

    #[Column]
    private string $username;

    #[Column]
    private string $email;

    // Getters and setters...
}
```

### UUID Support

You can mark properties as UUIDs using the `#[Uuid]` attribute. This is particularly useful for primary keys that should be generated as UUIDs.

```php
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\Id;
use MonkeysLegion\Entity\Attributes\Uuid;
use MonkeysLegion\Entity\Utils\Uuid as UuidUtil;

#[Entity(table: 'profiles')]
class Profile
{
    #[Id]
    #[Uuid]
    private string $id;

    public function __construct()
    {
        // You can use the built-in utility to generate a UUID v4
        $this->id = UuidUtil::v4();
    }
}
```

### Using Observers

You can attach an observer to an entity class using the `#[ObservedBy]` attribute.

#### 1. Create an Observer

Extend the `EntityObserver` base class and override the methods you need.

```php
use MonkeysLegion\Entity\Observers\EntityObserver;

class UserObserver extends EntityObserver
{
    public function creating(object $entity): void
    {
        // Set default values or hash passwords
        echo "Creating user: " . $entity->getUsername();
    }

    public function hydrated(object $entity): void
    {
        // Perform actions after the entity is loaded from the database
        echo "User hydrated!";
    }
}
```

#### 2. Register the Observer on the Entity

You can register a single observer or an array of observers:

```php
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\ObservedBy;

#[Entity(table: 'users')]
#[ObservedBy(UserObserver::class)] // Single observer
class User
{
    // ...
}

#[Entity(table: 'posts')]
#[ObservedBy([PostObserver::class, ActivityLogObserver::class])] // Multiple observers
class Post
{
    // ...
}
```

#### Supported Lifecycle Events:

- `creating` / `created`: Before and after an insert operation.
- `updating` / `updated`: Before and after an update operation.
- `saving` / `saved`: Before and after both insert and update operations.
- `deleting` / `deleted`: Before and after a delete operation.
- `hydrated`: After an entity is populated from a database row.

### Scanning for Entities

Use `EntityScanner` to find all classes marked with the `#[Entity]` attribute in a directory.

```php
use MonkeysLegion\Entity\Scanner\EntityScanner;

$scanner = new EntityScanner();
$entities = $scanner->scanDir(__DIR__ . '/src/Entities');

foreach ($entities as $reflectionClass) {
    echo "Found entity: " . $reflectionClass->getName() . "\n";
}
```

### Hydrating Entities

The `Hydrator` class handles the conversion between database rows (arrays/objects) and entities.

```php
use MonkeysLegion\Entity\Hydrator;

// From DB to Entity
$user = Hydrator::hydrate(User::class, $dbRow);

// From Entity to DB data
$data = Hydrator::extract($user);
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
