<?php

namespace Tests\Unit;

use App\Enums\PriorityEnum;
use Tests\TestCase;

class PriorityEnumTest extends TestCase
{
    public function test_priority_enum_has_correct_values(): void
    {
        $this->assertEquals(1, PriorityEnum::HIGH->value);
        $this->assertEquals(2, PriorityEnum::MIDHIGH->value);
        $this->assertEquals(3, PriorityEnum::MID->value);
        $this->assertEquals(4, PriorityEnum::MIDLOW->value);
        $this->assertEquals(5, PriorityEnum::LOW->value);
    }

    public function test_priority_enum_cases(): void
    {
        $cases = PriorityEnum::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(PriorityEnum::HIGH, $cases);
        $this->assertContains(PriorityEnum::MIDHIGH, $cases);
        $this->assertContains(PriorityEnum::MID, $cases);
        $this->assertContains(PriorityEnum::MIDLOW, $cases);
        $this->assertContains(PriorityEnum::LOW, $cases);
    }

    public function test_priority_enum_from_value(): void
    {
        $this->assertEquals(PriorityEnum::HIGH, PriorityEnum::from(1));
        $this->assertEquals(PriorityEnum::MIDHIGH, PriorityEnum::from(2));
        $this->assertEquals(PriorityEnum::MID, PriorityEnum::from(3));
        $this->assertEquals(PriorityEnum::MIDLOW, PriorityEnum::from(4));
        $this->assertEquals(PriorityEnum::LOW, PriorityEnum::from(5));
    }

    public function test_priority_enum_try_from_invalid_value(): void
    {
        $this->assertNull(PriorityEnum::tryFrom(0));
        $this->assertNull(PriorityEnum::tryFrom(6));
        $this->assertNull(PriorityEnum::tryFrom(99));
    }

    public function test_priority_enum_labels(): void
    {
        $this->assertEquals('High', PriorityEnum::HIGH->label());
        $this->assertEquals('Mid-High', PriorityEnum::MIDHIGH->label());
        $this->assertEquals('Medium', PriorityEnum::MID->label());
        $this->assertEquals('Mid-Low', PriorityEnum::MIDLOW->label());
        $this->assertEquals('Low', PriorityEnum::LOW->label());
    }

    public function test_priority_enum_all_cases_have_labels(): void
    {
        foreach (PriorityEnum::cases() as $priority) {
            $label = $priority->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }
}
