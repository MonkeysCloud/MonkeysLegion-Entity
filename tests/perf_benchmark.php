<?php
declare(strict_types=1);

/**
 * MonkeysLegion Entity v2 — Performance Benchmark
 *
 * Run: php tests/perf_benchmark.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MonkeysLegion\Entity\Attributes\Cast;
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\Field;
use MonkeysLegion\Entity\Attributes\Fillable;
use MonkeysLegion\Entity\Attributes\Hidden;
use MonkeysLegion\Entity\Attributes\Id;
use MonkeysLegion\Entity\Attributes\Timestamps;
use MonkeysLegion\Entity\Hydrator;
use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use MonkeysLegion\Entity\Support\ChangeTracker;

// ── Benchmark Entity ───────────────────────────────────────────

#[Entity(table: 'benchmark_users')]
#[Timestamps]
class BenchmarkUser
{
    #[Id]
    #[Field(type: 'unsignedBigInt', autoIncrement: true)]
    public int $id;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $email;

    #[Field(type: 'string', length: 255)]
    #[Fillable]
    public string $name;

    #[Field(type: 'string', length: 255)]
    #[Hidden]
    public string $password;

    #[Field(type: 'integer')]
    public int $age;

    #[Field(type: 'boolean')]
    public bool $active;
}

// ── Helpers ────────────────────────────────────────────────────

function bench(string $label, int $iterations, callable $fn): void
{
    // Warmup
    for ($i = 0; $i < min(100, $iterations); $i++) {
        $fn($i);
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn($i);
    }
    $elapsed = (hrtime(true) - $start) / 1e9;
    $opsPerSec = $iterations / $elapsed;

    printf(
        "  %-40s %10s ops  %8.3f sec  %12s ops/sec\n",
        $label,
        number_format($iterations),
        $elapsed,
        number_format((int) $opsPerSec),
    );
}

// ── Run Benchmarks ─────────────────────────────────────────────

echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║  MonkeysLegion Entity v2 — Performance Benchmark                             ║\n";
echo "╟────────────────────────────────────────────────────────────────────────────────╢\n";
printf("║  PHP %-74s║\n", phpversion());
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

$row = [
    'id'       => 1,
    'email'    => 'bench@example.com',
    'name'     => 'Benchmark User',
    'password' => 'hashed_password_value',
    'age'      => 30,
    'active'   => true,
];

// 1. MetadataRegistry cold parse
echo "── MetadataRegistry ──────────────────────────────────────────\n";

MetadataRegistry::clear();
$coldStart = hrtime(true);
MetadataRegistry::for(BenchmarkUser::class);
$coldTime = (hrtime(true) - $coldStart) / 1e6;
printf("  Cold parse (first call):                                    %.3f ms\n", $coldTime);

$hotStart = hrtime(true);
for ($i = 0; $i < 100_000; $i++) {
    MetadataRegistry::for(BenchmarkUser::class);
}
$hotTime = (hrtime(true) - $hotStart) / 1e9;
printf("  Hot cache (100K calls):                                     %.3f ms  (%s ops/sec)\n",
    $hotTime * 1000,
    number_format((int) (100_000 / $hotTime)),
);

echo "\n── Hydration ─────────────────────────────────────────────────\n";

bench('Hydrate entity', 100_000, function (int $i) use ($row) {
    $row['id'] = $i;
    Hydrator::hydrate(BenchmarkUser::class, $row);
});

echo "\n── Extraction ────────────────────────────────────────────────\n";

$entity = Hydrator::hydrate(BenchmarkUser::class, $row);

bench('Extract (all fields)', 100_000, function () use ($entity) {
    Hydrator::extract($entity);
});

bench('Extract (no hidden)', 100_000, function () use ($entity) {
    Hydrator::extract($entity, includeHidden: false);
});

echo "\n── Serialization ─────────────────────────────────────────────\n";

bench('toArray()', 100_000, function () use ($entity) {
    Hydrator::toArray($entity);
});

bench('toJson()', 100_000, function () use ($entity) {
    Hydrator::toJson($entity);
});

echo "\n── Change Tracking ───────────────────────────────────────────\n";

$tracker = new ChangeTracker();
bench('Track + isDirty (clean)', 100_000, function () use ($tracker, $entity) {
    $tracker->track($entity);
    $tracker->isDirty($entity);
});

bench('Track + isDirty (dirty)', 100_000, function (int $i) use ($tracker) {
    $e = Hydrator::hydrate(BenchmarkUser::class, [
        'id' => $i, 'email' => 'a@b.com', 'name' => 'A',
        'password' => 'x', 'age' => 20, 'active' => true,
    ]);
    $tracker->track($e);
    $e->name = 'Changed';
    $tracker->isDirty($e);
});

echo "\n══════════════════════════════════════════════════════════════\n";
echo "  All benchmarks complete.\n\n";
