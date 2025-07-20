<?php

namespace MonkeysLegion\Entity;

use ReflectionClass;

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
     */
    public static function hydrate(string $class, array $row): object
    {
        $obj = new $class();
        $ref = new ReflectionClass($class);

        foreach ($row as $col => $val) {
            if (!$ref->hasProperty($col)) {
                // column has no matching property → skip (or map via metadata)
                continue;
            }
            $prop = $ref->getProperty($col);
            $prop->setAccessible(true);
            $prop->setValue($obj, $val);
        }
        return $obj;
    }
}