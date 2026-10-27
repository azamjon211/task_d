<?php

namespace App\Support;

use InvalidArgumentException;
use Stringable;

final readonly class Money implements Stringable
{
    public const int SCALE = 2;

    public const string MAX = '9999999999999.99';

    private const int WORKING_SCALE = 12;

    private function __construct(public string $amount) {}

    public static function of(string|int $amount): self
    {
        $amount = trim((string) $amount);

        if (! preg_match('/^-?\d+(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException("Noto'g'ri pul summasi: [{$amount}].");
        }

        return new self(self::normalize(bcadd($amount, '0', self::SCALE)));
    }

    public static function zero(): self
    {
        return new self('0.00');
    }

    public function plus(self $other): self
    {
        return new self(self::normalize(bcadd($this->amount, $other->amount, self::SCALE)));
    }

    public function minus(self $other): self
    {
        return new self(self::normalize(bcsub($this->amount, $other->amount, self::SCALE)));
    }

    public function multipliedBy(string|int $factor): self
    {
        $factor = (string) $factor;

        if (! is_numeric($factor)) {
            throw new InvalidArgumentException("Noto'g'ri koeffitsiyent: [{$factor}].");
        }

        return new self(self::roundHalfUp(bcmul($this->amount, $factor, self::WORKING_SCALE)));
    }

    public function percent(string $percent): self
    {
        return $this->multipliedBy(bcdiv($percent, '100', self::WORKING_SCALE));
    }

    public function compareTo(self $other): int
    {
        return bccomp($this->amount, $other->amount, self::SCALE);
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function isZero(): bool
    {
        return $this->equals(self::zero());
    }

    public function isPositive(): bool
    {
        return $this->greaterThan(self::zero());
    }

    public function isNegative(): bool
    {
        return $this->lessThan(self::zero());
    }

    public function min(self $other): self
    {
        return $this->lessThan($other) ? $this : $other;
    }

    public function max(self $other): self
    {
        return $this->greaterThan($other) ? $this : $other;
    }

    public function nonNegative(): self
    {
        return $this->max(self::zero());
    }

    public function format(): string
    {
        $negative = str_starts_with($this->amount, '-');
        [$integer, $fraction] = explode('.', ltrim($this->amount, '-'));

        $grouped = ltrim(strrev(chunk_split(strrev($integer), 3, ' ')), ' ');

        return ($negative ? '-' : '').$grouped.','.$fraction;
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    private static function roundHalfUp(string $value): string
    {
        $offset = str_starts_with($value, '-') ? '-0.005' : '0.005';

        return self::normalize(bcadd(bcadd($value, $offset, self::WORKING_SCALE), '0', self::SCALE));
    }

    private static function normalize(string $value): string
    {
        return bccomp($value, '0', self::SCALE) === 0 ? '0.00' : $value;
    }
}
