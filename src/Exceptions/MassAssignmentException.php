<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Exceptions;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Thrown when a mass-assignment attempt targets a guarded field.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
class MassAssignmentException extends \RuntimeException {}
