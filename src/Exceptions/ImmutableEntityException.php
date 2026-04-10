<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Exceptions;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Thrown when attempting to UPDATE or DELETE an #[Immutable] entity.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
class ImmutableEntityException extends \RuntimeException {}
