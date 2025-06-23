<?php

declare(strict_types=1);

namespace Tests\Unit\Data\Transformers;

use App\Data\Transformers\DateOnlyTransformer;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;

class DateOnlyTransformerTest extends TestCase
{
    private DateOnlyTransformer $transformer;
    private DataProperty $property;
    private TransformationContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new DateOnlyTransformer();
        $this->property = $this->createMock(DataProperty::class);
        $this->context = new TransformationContext();
    }

    #[Test]

    public function it_returns_null_for_null_input(): void
    {
        $result = $this->transformer->transform($this->property, null, $this->context);

        $this->assertNull($result);
    }

    #[Test]
    public function it_transforms_carbon_instance_to_date_only_string(): void
    {
        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->transformer->transform($this->property, $date, $this->context);

        $this->assertEquals('2024-03-15', $result);
    }

    #[Test]
    public function it_transforms_string_date_to_date_only_format(): void
    {
        $date = '2024-03-15 14:30:00';

        $result = $this->transformer->transform($this->property, $date, $this->context);

        $this->assertEquals('2024-03-15', $result);
    }
}
