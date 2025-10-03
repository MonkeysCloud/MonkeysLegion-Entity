<?php

namespace MonkeysLegion\Entity;

use ReflectionClass;
use ReflectionNamedType;
use DateTimeImmutable;
use DateTimeZone;
use ReflectionProperty;

final class Hydrator
{
    /**
     * Hydrates an object of the given class with data from the provided row.
     *
     * @param class-string $class The class name to hydrate.
     * @param array<string,mixed>|object $row The data to use for hydration.
     * @return object An instance of the specified class populated with the data.
     * @throws \ReflectionException|\DateMalformedStringException If the class does not exist or cannot be reflected.
     */
    public static function hydrate(string $class, array|object $row): object
    {
        $ref = new ReflectionClass($class);
        $obj = $ref->newInstance();

        // Convert object to array if needed (for stdClass from PDO)
        if (is_object($row)) {
            $row = (array) $row;
        }

        foreach ($row as $col => $val) {
            if (! $ref->hasProperty($col)) {
                continue;
            }

            $prop = $ref->getProperty($col);
            $prop->setAccessible(true);

            $value = $val;
            $type  = $prop->getType();

            if ($type instanceof ReflectionNamedType && $val !== null) {
                $rawType = ltrim($type->getName(), '\\');        // e.g. "DateTimeImmutable"
                $lc      = strtolower($rawType);                 // e.g. "datetimeimmutable"

                // --- Date / time ---------------------------------------------------
                if ($rawType === DateTimeImmutable::class || $lc === 'datetime' || $lc === 'datetimeimmutable') {
                    $value = new DateTimeImmutable((string)$val, new DateTimeZone('UTC'));
                } elseif ($lc === 'datetimetz') {
                    $value = new DateTimeImmutable((string)$val); // already tz'ed string
                } elseif ($lc === 'timestamp' || $lc === 'timestamptz') {
                    $ts    = is_numeric($val) ? (int)$val : strtotime((string)$val);
                    $value = new DateTimeImmutable("@$ts")->setTimezone(new DateTimeZone('UTC'));
                } elseif ($lc === 'date') {
                    $value = new DateTimeImmutable($val . ' 00:00:00', new DateTimeZone('UTC'));
                } elseif ($lc === 'time') {
                    $value = new DateTimeImmutable(date('Y-m-d') . ' ' . $val, new DateTimeZone('UTC'));
                }
                // --- integers ------------------------------------------------------
                elseif (in_array($lc, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'unsignedbigint'], true)) {
                    $value = (int)$val;
                }
                // --- floats/decimal ------------------------------------------------
                elseif (in_array($lc, ['float', 'double', 'decimal'], true)) {
                    $value = is_numeric($val) ? (float)$val : $val;
                }
                // --- boolean -------------------------------------------------------
                elseif (in_array($lc, ['bool', 'boolean'], true)) {
                    $value = (bool)$val;
                }
                // --- json ----------------------------------------------------------
                elseif (in_array($lc, ['json', 'simple_json'], true)) {
                    $decoded = json_decode((string)$val, true);
                    $value   = $decoded !== null ? $decoded : null;
                }
                // --- arrays ---------------------------------------------------------
                elseif (in_array($lc, ['array', 'simple_array'], true)) {
                    // First check if it's a JSON string
                    if (is_string($val)) {
                        // Try to decode as JSON first
                        $trimmedVal = trim($val);
                        if ($trimmedVal !== '' && (str_starts_with($trimmedVal, '[') || str_starts_with($trimmedVal, '{'))) {
                            $decoded = json_decode($trimmedVal, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $value = $decoded;
                            } else {
                                // Fallback to comma-separated values
                                $value = array_values(array_filter($val === '' ? [] : explode(',', $val)));
                            }
                        } else {
                            // Treat as comma-separated values
                            $value = array_values(array_filter($val === '' ? [] : explode(',', $val)));
                        }
                    } else {
                        $value = (array)$val;
                    }
                }
                // --- string (explicit handling for potential JSON in string fields) ---
                elseif ($lc === 'string' || $lc === 'text') {
                    // Keep as string, but you might want to check for JSON fields here
                    // based on property name patterns (e.g., fields ending with '_json')
                    $value = (string)$val;
                }
                // others: leave as-is (enum, set, etc.)
            }
            // Handle nullable types with null values
            elseif ($val === null && $type && $type->allowsNull()) {
                $value = null;
            }

            self::safeSetProperty($prop, $obj, $value);
        }

        return $obj;
    }

    /**
     * Extract data from an entity for persistence.
     * This is the reverse of hydrate - converts entity properties to database values.
     *
     * @param object $entity The entity to extract data from
     * @param array<string> $fields Optional list of fields to extract (if empty, extracts all)
     * @return array<string,mixed> The extracted data
     */
    public static function extract(object $entity, array $fields = []): array
    {
        $ref = new ReflectionClass($entity);
        $data = [];

        $properties = empty($fields)
            ? $ref->getProperties()
            : array_map(fn($f) => $ref->getProperty($f), $fields);

        foreach ($properties as $prop) {
            $prop->setAccessible(true);

            if (!$prop->isInitialized($entity)) {
                continue;
            }

            $name = $prop->getName();
            $value = $prop->getValue($entity);
            $type = $prop->getType();

            // Convert PHP values to database-friendly formats
            if ($value === null) {
                $data[$name] = null;
            } elseif ($value instanceof DateTimeImmutable || $value instanceof \DateTime) {
                $data[$name] = $value->format('Y-m-d H:i:s');
            } elseif (is_bool($value)) {
                $data[$name] = $value ? 1 : 0;
            } elseif (is_array($value)) {
                // Check if this should be JSON or comma-separated
                if ($type instanceof ReflectionNamedType) {
                    $typeName = strtolower($type->getName());
                    if (in_array($typeName, ['json', 'simple_json'], true)) {
                        $data[$name] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } elseif (in_array($typeName, ['simple_array'], true)) {
                        $data[$name] = implode(',', $value);
                    } else {
                        // Default to JSON for array types
                        $data[$name] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } else {
                    // Default to JSON
                    $data[$name] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } elseif (is_float($value)) {
                // Preserve precision for DECIMAL columns
                $data[$name] = (string)$value;
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Check if a string is valid JSON
     *
     * @param mixed $string The value to check
     * @return bool True if the string is valid JSON
     */
    private static function isJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        $trimmed = trim($string);
        if ($trimmed === '') {
            return false;
        }

        // Quick check for JSON-like structure
        if (!str_starts_with($trimmed, '[') && !str_starts_with($trimmed, '{')) {
            return false;
        }

        json_decode($trimmed);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if a property type allows null values
     */
    private static function isPropertyNullable(ReflectionProperty $prop): bool
    {
        $type = $prop->getType();
        if (!$type) return true; // No type hint = nullable

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof \ReflectionNamedType && $unionType->getName() === 'null') {
                    return true;
                }
            }
            return false;
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->allowsNull();
        }

        return true; // Default to nullable for safety
    }

    /**
     * Safely set a property value, respecting nullability constraints
     */
    private static function safeSetProperty(ReflectionProperty $prop, object $entity, mixed $value): void
    {
        $prop->setAccessible(true);

        if ($value === null && !self::isPropertyNullable($prop)) {
            // Don't set non-nullable properties to null - leave them uninitialized
            return;
        }

        $prop->setValue($entity, $value);
    }
}
