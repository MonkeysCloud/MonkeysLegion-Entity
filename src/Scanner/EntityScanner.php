<?php

namespace MonkeysLegion\Entity\Scanner;

use MonkeysLegion\Entity\Attributes\Entity as EntityAttr;
use ReflectionClass;
use ReflectionException;

final class EntityScanner
{

    /**
     * Scans the given directory for PHP files and returns an array of ReflectionClass objects
     * that have the Entity attribute.
     *
     * @param string $dir Directory to scan for PHP files
     *
     * @return ReflectionClass[] A
     * @throws ReflectionException
     */
    public function scanDir(string $dir): array
    {
        $entities = [];
        foreach (new \RecursiveIteratorIterator(
                     new \RecursiveDirectoryIterator($dir)
                 ) as $file) {
            if ($file->getExtension() !== 'php') continue;
            $class = $this->classFromPath($file->getPathname());
            if (!$class) continue;

            $ref = new ReflectionClass($class);
            if ($ref->isAbstract()) continue;
            if ($ref->getAttributes(EntityAttr::class)) {
                $entities[] = $ref;
            }
        }
        return $entities;
    }

    private function classFromPath(string $path): ?string
    {
        $contents = \file_get_contents($path);
        if (!preg_match('/^namespace\s+(.+?);/m', $contents, $ns)) return null;
        if (!preg_match('/^class\s+(\w+)/m', $contents, $cl)) return null;
        return $ns[1] . '\\' . $cl[1];
    }
}