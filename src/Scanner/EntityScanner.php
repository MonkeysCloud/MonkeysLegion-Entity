<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Scanner;

use MonkeysLegion\Entity\Attributes\Entity as EntityAttr;
use MonkeysLegion\Entity\Metadata\EntityMetadata;
use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Scans directories for PHP files containing entity classes and
 * returns their parsed metadata via MetadataRegistry.
 *
 * v2 improvements:
 *  • Returns EntityMetadata[] instead of ReflectionClass[]
 *  • Uses MetadataRegistry for caching (zero re-parsing)
 *  • Strict types and v2 headers
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class EntityScanner
{
    /**
     * Scan the given directory for entity classes and return their metadata.
     *
     * @param string $dir Directory to scan for PHP files.
     *
     * @return list<EntityMetadata>
     */
    public function scanDir(string $dir): array
    {
        $entities = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = self::classFromPath($file->getPathname());
            if ($class === null) {
                continue;
            }

            if (!class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);
            if ($ref->isAbstract()) {
                continue;
            }

            if ($ref->getAttributes(EntityAttr::class) === []) {
                continue;
            }

            $entities[] = MetadataRegistry::for($class);
        }

        return $entities;
    }

    /**
     * Extract a fully-qualified class name from a PHP file path.
     */
    private static function classFromPath(string $path): ?string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        if (!preg_match('/^namespace\s+(.+?);/m', $contents, $ns)) {
            return null;
        }
        if (!preg_match('/^class\s+(\w+)/m', $contents, $cl)) {
            return null;
        }

        return $ns[1] . '\\' . $cl[1];
    }
}