<?php
declare(strict_types=1);

namespace Tests\Unit\Casts;

use MonkeysLegion\Entity\Casts\ArrayCast;
use MonkeysLegion\Entity\Casts\BooleanCast;
use MonkeysLegion\Entity\Casts\DatetimeCast;
use MonkeysLegion\Entity\Casts\DecimalCast;
use MonkeysLegion\Entity\Casts\EnumCast;
use MonkeysLegion\Entity\Casts\JsonCast;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BuiltInCastersTest extends TestCase
{
    private object $dummyEntity;

    protected function setUp(): void
    {
        $this->dummyEntity = new \stdClass();
    }

    // ── EnumCast ────────────────────────────────────────────────

    #[Test]
    public function enum_cast_get_converts_scalar_to_enum(): void
    {
        $cast = new EnumCast(TestStatus::class);

        $result = $cast->get('pending', 'status', $this->dummyEntity);

        self::assertInstanceOf(TestStatus::class, $result);
        self::assertSame(TestStatus::Pending, $result);
    }

    #[Test]
    public function enum_cast_set_converts_enum_to_scalar(): void
    {
        $cast = new EnumCast(TestStatus::class);

        $result = $cast->set(TestStatus::Shipped, 'status', $this->dummyEntity);

        self::assertSame('shipped', $result);
    }

    #[Test]
    public function enum_cast_get_returns_null_for_null(): void
    {
        $cast = new EnumCast(TestStatus::class);

        self::assertNull($cast->get(null, 'status', $this->dummyEntity));
    }

    #[Test]
    public function enum_cast_get_returns_null_for_invalid_value(): void
    {
        $cast = new EnumCast(TestStatus::class);

        self::assertNull($cast->get('nonexistent_value', 'status', $this->dummyEntity));
    }

    #[Test]
    public function enum_cast_get_passes_through_enum_instances(): void
    {
        $cast = new EnumCast(TestStatus::class);
        $enum = TestStatus::Pending;

        $result = $cast->get($enum, 'status', $this->dummyEntity);

        self::assertSame($enum, $result);
    }

    // ── JsonCast ────────────────────────────────────────────────

    #[Test]
    public function json_cast_get_decodes_json_string(): void
    {
        $cast = new JsonCast();

        $result = $cast->get('{"key":"value"}', 'metadata', $this->dummyEntity);

        self::assertSame(['key' => 'value'], $result);
    }

    #[Test]
    public function json_cast_set_encodes_array_to_json(): void
    {
        $cast = new JsonCast();

        $result = $cast->set(['key' => 'value'], 'metadata', $this->dummyEntity);

        self::assertJsonStringEqualsJsonString('{"key":"value"}', $result);
    }

    #[Test]
    public function json_cast_get_returns_null_for_null(): void
    {
        $cast = new JsonCast();

        self::assertNull($cast->get(null, 'metadata', $this->dummyEntity));
    }

    #[Test]
    public function json_cast_get_returns_null_for_empty_string(): void
    {
        $cast = new JsonCast();

        self::assertNull($cast->get('', 'metadata', $this->dummyEntity));
    }

    #[Test]
    public function json_cast_get_returns_null_for_invalid_json(): void
    {
        $cast = new JsonCast();

        self::assertNull($cast->get('{invalid}', 'metadata', $this->dummyEntity));
    }

    #[Test]
    public function json_cast_set_passes_through_valid_json_string(): void
    {
        $cast = new JsonCast();

        $result = $cast->set('{"already":"json"}', 'metadata', $this->dummyEntity);

        self::assertSame('{"already":"json"}', $result);
    }

    // ── ArrayCast ───────────────────────────────────────────────

    #[Test]
    public function array_cast_get_decodes_json_array(): void
    {
        $cast = new ArrayCast();

        $result = $cast->get('["a","b","c"]', 'tags', $this->dummyEntity);

        self::assertSame(['a', 'b', 'c'], $result);
    }

    #[Test]
    public function array_cast_get_splits_comma_separated_string(): void
    {
        $cast = new ArrayCast();

        $result = $cast->get('a, b, c', 'tags', $this->dummyEntity);

        self::assertSame(['a', 'b', 'c'], $result);
    }

    #[Test]
    public function array_cast_get_returns_empty_array_for_null(): void
    {
        $cast = new ArrayCast();

        self::assertSame([], $cast->get(null, 'tags', $this->dummyEntity));
    }

    #[Test]
    public function array_cast_get_returns_empty_array_for_empty_string(): void
    {
        $cast = new ArrayCast();

        self::assertSame([], $cast->get('', 'tags', $this->dummyEntity));
    }

    #[Test]
    public function array_cast_set_encodes_array_to_json(): void
    {
        $cast = new ArrayCast();

        $result = $cast->set(['a', 'b'], 'tags', $this->dummyEntity);

        self::assertJsonStringEqualsJsonString('["a","b"]', $result);
    }

    // ── DatetimeCast ────────────────────────────────────────────

    #[Test]
    public function datetime_cast_get_parses_standard_format(): void
    {
        $cast = new DatetimeCast();

        $result = $cast->get('2026-09-27 14:30:00', 'created_at', $this->dummyEntity);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2026-09-27 14:30:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function datetime_cast_set_formats_datetime_object(): void
    {
        $cast = new DatetimeCast();
        $dt = new \DateTimeImmutable('2026-09-27 14:30:00');

        $result = $cast->set($dt, 'created_at', $this->dummyEntity);

        self::assertSame('2026-09-27 14:30:00', $result);
    }

    #[Test]
    public function datetime_cast_get_returns_null_for_null(): void
    {
        $cast = new DatetimeCast();

        self::assertNull($cast->get(null, 'created_at', $this->dummyEntity));
    }

    #[Test]
    public function datetime_cast_get_passes_through_datetime_objects(): void
    {
        $cast = new DatetimeCast();
        $dt = new \DateTimeImmutable('2026-09-27 14:30:00');

        $result = $cast->get($dt, 'created_at', $this->dummyEntity);

        self::assertSame($dt, $result);
    }

    #[Test]
    public function datetime_cast_get_parses_iso_format(): void
    {
        $cast = new DatetimeCast();

        $result = $cast->get('2026-09-27T14:30:00+00:00', 'created_at', $this->dummyEntity);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
    }

    // ── BooleanCast ─────────────────────────────────────────────

    #[Test]
    public function boolean_cast_get_converts_int_to_bool(): void
    {
        $cast = new BooleanCast();

        self::assertTrue($cast->get(1, 'active', $this->dummyEntity));
        self::assertFalse($cast->get(0, 'active', $this->dummyEntity));
    }

    #[Test]
    public function boolean_cast_get_converts_string_to_bool(): void
    {
        $cast = new BooleanCast();

        self::assertTrue($cast->get('1', 'active', $this->dummyEntity));
        self::assertTrue($cast->get('true', 'active', $this->dummyEntity));
        self::assertFalse($cast->get('0', 'active', $this->dummyEntity));
    }

    #[Test]
    public function boolean_cast_get_returns_null_for_null(): void
    {
        $cast = new BooleanCast();

        self::assertNull($cast->get(null, 'active', $this->dummyEntity));
    }

    #[Test]
    public function boolean_cast_set_converts_bool_to_int(): void
    {
        $cast = new BooleanCast();

        self::assertSame(1, $cast->set(true, 'active', $this->dummyEntity));
        self::assertSame(0, $cast->set(false, 'active', $this->dummyEntity));
    }

    // ── DecimalCast ─────────────────────────────────────────────

    #[Test]
    public function decimal_cast_get_converts_string_to_float(): void
    {
        $cast = new DecimalCast(scale: 2);

        $result = $cast->get('99.99', 'price', $this->dummyEntity);

        self::assertSame(99.99, $result);
    }

    #[Test]
    public function decimal_cast_set_formats_float_with_scale(): void
    {
        $cast = new DecimalCast(scale: 2);

        $result = $cast->set(99.999, 'price', $this->dummyEntity);

        self::assertSame('100.00', $result);
    }

    #[Test]
    public function decimal_cast_set_preserves_scale_4(): void
    {
        $cast = new DecimalCast(scale: 4);

        $result = $cast->set(3.14159265, 'pi', $this->dummyEntity);

        self::assertSame('3.1416', $result);
    }

    #[Test]
    public function decimal_cast_get_returns_null_for_null(): void
    {
        $cast = new DecimalCast();

        self::assertNull($cast->get(null, 'price', $this->dummyEntity));
    }
}

/**
 * Test-backed enum for EnumCast tests.
 */
enum TestStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
}
