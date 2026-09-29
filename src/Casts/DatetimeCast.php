<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Casts;

use DateTimeImmutable;
use DateTimeZone;
use MonkeysLegion\Entity\Contracts\CastInterface;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Casts between database datetime strings and DateTimeImmutable objects.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class DatetimeCast implements CastInterface
{
    public function __construct(
        private readonly string $format = 'Y-m-d H:i:s',
        private readonly ?string $timezone = null,
    ) {}

    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return DateTimeImmutable::createFromMutable($value);
        }

        // Try the configured format first, then fall back to common formats
        $formats = [$this->format, 'Y-m-d H:i:s', 'Y-m-d H:i:s.u', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, (string) $value);
            if ($dt !== false) {
                return $this->timezone !== null
                    ? $dt->setTimezone(new DateTimeZone($this->timezone))
                    : $dt;
            }
        }

        // Last resort: let DateTimeImmutable try to parse
        try {
            return new DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->format($this->format);
        }

        if ($value instanceof \DateTime) {
            return $value->format($this->format);
        }

        return (string) $value;
    }
}
