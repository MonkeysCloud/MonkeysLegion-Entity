<?php

namespace MonkeysLegion\Entity;

use ReflectionClass;
use ReflectionNamedType;
use DateTimeImmutable;
use DateTimeZone;

final class Hydrator
{
    /**
    * Hydrates an object of the given class with data from the provided row.
    *
    * @param class-string $class The class name to hydrate.
    * @param array<string,mixed> $row The data to use for hydration.
    * @return object An instance of the specified class populated with the data.
    * @throws \ReflectionException|\DateMalformedStringException If the class does not exist or cannot be reflected.
    */
    public static function hydrate(string $class, array $row): object
    {
        $ref = new ReflectionClass($class);
        $obj = $ref->newInstance();

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
                elseif (in_array($lc, ['int','integer','bigint','smallint','tinyint','unsignedbigint'], true)) {
                    $value = (int)$val;
                }
                // --- floats/decimal ------------------------------------------------
                elseif (in_array($lc, ['float','double','decimal'], true)) {
                    $value = is_numeric($val) ? (float)$val : $val;
                }
                // --- boolean -------------------------------------------------------
                elseif (in_array($lc, ['bool','boolean'], true)) {
                    $value = (bool)$val;
                }
                // --- json ----------------------------------------------------------
                elseif (in_array($lc, ['json','simple_json'], true)) {
                    $decoded = json_decode((string)$val, true);
                    $value   = $decoded !== null ? $decoded : null;
                }
                // --- arrays ---------------------------------------------------------
                elseif (in_array($lc, ['array','simple_array'], true)) {
                    $value = is_string($val) ? array_values(array_filter($val === '' ? [] : explode(',', $val))) : (array)$val;
                }
                // others: leave as-is (string, text, enum, set, etc.)
            }

            $prop->setValue($obj, $value);
        }

        return $obj;
    }
}