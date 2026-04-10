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
     * Extract a fully-qualified class name from a PHP file using the tokenizer.
     *
     * Uses token_get_all() instead of regex so that class declarations inside
     * comments, strings, or heredocs are never mistakenly matched.
     */
    private static function classFromPath(string $path): ?string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $tokens    = token_get_all($contents, TOKEN_PARSE);
        $namespace = '';
        $className = null;
        $count     = count($tokens);
        $i         = 0;

        while ($i < $count) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                $i++;
                continue;
            }

            // Capture namespace (supports multi-segment: A\B\C)
            if ($token[0] === T_NAMESPACE) {
                $ns = '';
                $i++;
                while ($i < $count) {
                    $t = $tokens[$i];
                    if (is_array($t) && in_array($t[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                        $ns .= $t[1];
                    } elseif ($t === '{' || $t === ';') {
                        break;
                    }
                    $i++;
                }
                $namespace = trim($ns, '\\');
                continue;
            }

            // Capture class name — skip anonymous classes (no T_STRING follows T_CLASS)
            if ($token[0] === T_CLASS) {
                $j = $i + 1;
                while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    $j++;
                }
                if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $className = $tokens[$j][1];
                    break;
                }
            }

            $i++;
        }

        if ($namespace === '' || $className === null) {
            return null;
        }

        return $namespace . '\\' . $className;
    }
}