<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function halfUpRoundingCases(): array
    {
        return [
            '0,005 yuqoriga' => ['1.00', '0.005', '0.01'],
            '0,0049 pastga' => ['0.98', '0.005', '0.00'],
            'aniq natija' => ['2500.00', '0.005', '12.50'],
            'kasr 0,5 dan katta' => ['1.01', '0.005', '0.01'],
            '0,015 yuqoriga' => ['3.00', '0.005', '0.02'],
        ];
    }

    #[DataProvider('halfUpRoundingCases')]
    public function test_multiplication_rounds_half_up_to_two_decimals(string $amount, string $factor, string $expected): void
    {
        $this->assertSame($expected, Money::of($amount)->multipliedBy($factor)->amount);
    }

    public function test_arithmetic_is_exact_without_float_errors(): void
    {
        $sum = Money::of('0.10')->plus(Money::of('0.20'));

        $this->assertSame('0.30', $sum->amount);
        $this->assertSame('2250.00', Money::of('3000')->minus(Money::of('750'))->amount);
    }

    public function test_more_than_two_decimals_is_rejected_instead_of_silently_rounded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('1.005');
    }

    public function test_format_for_display(): void
    {
        $this->assertSame('2 500,00', Money::of('2500')->format());
        $this->assertSame('1 234 567,89', Money::of('1234567.89')->format());
    }
}
