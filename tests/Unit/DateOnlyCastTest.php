<?php

namespace Tests\Unit;

use App\Data\Casts\DateOnlyCast;
use Carbon\Carbon;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;
use Tests\TestCase;

class DateOnlyCastTest extends TestCase
{
    private DateOnlyCast $cast;
    private DataProperty $property;
    private CreationContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new DateOnlyCast();

        // Mock the required dependencies
        $this->property = $this->createMock(DataProperty::class);
        $this->context = $this->createMock(CreationContext::class);
    }

    public function test_cast_returns_carbon_instance_from_date_string(): void
    {
        $dateString = '2025-06-22';
        $result = $this->cast->cast($this->property, $dateString, [], $this->context);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-06-22', $result->toDateString());
        $this->assertEquals('00:00:00', $result->toTimeString());
    }

    public function test_cast_returns_null_for_null_value(): void
    {
        $result = $this->cast->cast($this->property, null, [], $this->context);

        $this->assertNull($result);
    }

    public function test_cast_returns_start_of_day_from_carbon_instance(): void
    {
        $carbon = Carbon::parse('2025-06-22 15:30:45');
        $result = $this->cast->cast($this->property, $carbon, [], $this->context);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-06-22', $result->toDateString());
        $this->assertEquals('00:00:00', $result->toTimeString());
    }

    public function test_cast_handles_datetime_string(): void
    {
        $datetimeString = '2025-06-22 15:30:45';
        $result = $this->cast->cast($this->property, $datetimeString, [], $this->context);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-06-22', $result->toDateString());
        $this->assertEquals('00:00:00', $result->toTimeString());
    }

    public function test_cast_handles_different_valid_date_formats(): void
    {
        $dateFormats = [
            '2025-06-22',
            '2025/06/22',
            '2025-06-22T00:00:00',
            '2025-06-22 00:00:00',
        ];

        foreach ($dateFormats as $dateFormat) {
            $result = $this->cast->cast($this->property, $dateFormat, [], $this->context);

            $this->assertInstanceOf(Carbon::class, $result, "Failed to parse format: $dateFormat");
            $this->assertEquals('00:00:00', $result->toTimeString());
            $this->assertEquals('2025-06-22', $result->toDateString());
        }
    }

    public function test_cast_preserves_date_but_resets_time(): void
    {
        $testCases = [
            ['2025-01-01 23:59:59', '2025-01-01'],
            ['2025-12-31 12:30:45', '2025-12-31'],
            ['2025-06-15 06:00:00', '2025-06-15'],
        ];

        foreach ($testCases as [$input, $expectedDate]) {
            $result = $this->cast->cast($this->property, $input, [], $this->context);

            $this->assertInstanceOf(Carbon::class, $result);
            $this->assertEquals($expectedDate, $result->toDateString());
            $this->assertEquals('00:00:00', $result->toTimeString());
        }
    }

    public function test_cast_modifies_carbon_instance_to_start_of_day(): void
    {
        $carbon = Carbon::create(2025, 6, 22, 14, 30, 45);
        $originalTime = $carbon->toTimeString();

        // Verify the original has time
        $this->assertEquals('14:30:45', $originalTime);

        $result = $this->cast->cast($this->property, $carbon, [], $this->context);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-06-22', $result->toDateString());
        $this->assertEquals('00:00:00', $result->toTimeString());

        // The cast returns the same instance, modified
        $this->assertSame($carbon, $result);
    }

    public function test_cast_works_with_iso_date_format(): void
    {
        $isoDate = '2025-06-22T14:30:45Z';
        $result = $this->cast->cast($this->property, $isoDate, [], $this->context);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-06-22', $result->toDateString());
        $this->assertEquals('00:00:00', $result->toTimeString());
    }

    public function test_cast_handles_date_with_timezone(): void
    {
        $dateWithTimezone = '2025-06-22 14:30:45 UTC';
        $result = $this->cast->cast($this->property, $dateWithTimezone, [], $this->context);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-06-22', $result->toDateString());
        $this->assertEquals('00:00:00', $result->toTimeString());
    }
}
