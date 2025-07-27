<?php

namespace MonkeysLegion\Entity;

use ReflectionClass;
use ReflectionProperty;
use MonkeysLegion\Entity\Attributes\Field as FieldAttr;

final class Hydrator
{
    /**
     * Hydrates an object of the given class with the provided row data.
     *
     * @param class-string $class
     *   The fully qualified class name of the object to hydrate.
     * @param array<string,mixed> $row
     *   An associative array where keys are property names and values are the corresponding values to set.
     * @return object
     *   An instance of the specified class with properties set according to the provided row data.
     * @throws \ReflectionException
     * @throws \DateMalformedStringException
     */
    public static function hydrate(string $class, array $row): object
    {
        // Create a new instance without invoking any constructor logic
        $ref   = new ReflectionClass($class);
        $obj   = $ref->newInstanceWithoutConstructor() ?: new $class();

        foreach ($row as $col => $val) {
            if (! $ref->hasProperty($col)) {
                continue;
            }

            $prop = $ref->getProperty($col);
            $prop->setAccessible(true);

            $value = $val;

            // Convert based on #[Field(type: ...)] metadata
            $attrs = $prop->getAttributes(FieldAttr::class);
            if ($attrs && $value !== null) {
                /** @var FieldAttr $fmeta */
                $fmeta = $attrs[0]->newInstance();
                $type  = strtolower($fmeta->type ?? '');

                // Numeric integer types
                if (in_array($type, ['integer','tinyint','smallint','bigint','unsignedbigint','year'], true)) {
                    $value = (int) $value;
                }
                // Floating point and decimals
                elseif (in_array($type, ['float','double','decimal'], true)) {
                    $value = is_numeric($value) ? (float) $value : $value;
                }
                // Boolean types
                elseif (in_array($type, ['boolean','bool'], true)) {
                    $value = (bool) $value;
                }
                // Date/time types → DateTimeImmutable
                elseif (in_array($type, ['date','time','datetime','datetimetz','timestamp','timestamptz'], true)) {
                    $value = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
                }
                // JSON
                elseif (in_array($type, ['json','simple_json'], true)) {
                    $value = json_decode($value, true);
                }
                // Simple array (comma-separated)
                elseif ($type === 'simple_array') {
                    $value = explode(',', $value);
                }
                // Otherwise, leave as raw string or binary
            }

            $prop->setValue($obj, $value);
        }

        return $obj;
    }
}
