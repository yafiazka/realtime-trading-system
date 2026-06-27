<?php

declare(strict_types=1);

namespace App\Classes;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class OperatorAmount
{
    private BigDecimal $amount;
    private const SCALE = 8;

    public function __construct(string|int|float $amount)
    {
        try {
            $this->amount = BigDecimal::of($amount)->toScale(self::SCALE, RoundingMode::HalfUp);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException("Invalid operator format.");
        }
    }

    public function add(OperatorAmount $other): self
    {
        return new self((string) $this->amount->plus($other->amount));
    }

    public function subtract(OperatorAmount $other): self
    {
        return new self((string) $this->amount->minus($other->amount));
    }

    public function multiply(string|float|int $multiplier): self
    {
        return new self((string) $this->amount->multipliedBy($multiplier));
    }

    public function isGreaterThan(OperatorAmount $other): bool
    {
        return $this->amount->isGreaterThan($other->amount);
    }

    public function isLessThan(OperatorAmount $other): bool
    {
        return $this->amount->isLessThan($other->amount);
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function modulo(OperatorAmount $divisor): OperatorAmount
    {
        if ($divisor->isZero()) {
            throw new InvalidArgumentException("Cannot be calculated with a zero divisor.");
        }
        $quotient = $this->amount->dividedBy($divisor->amount, self::SCALE, RoundingMode::Down);
        $floorQuotient = $quotient->toScale(0, RoundingMode::Down);
        $remainder = $this->amount->minus($floorQuotient->multipliedBy($divisor->amount));
        return new self((string) $remainder);
    }

    public function __toString(): string
    {
        return (string) $this->amount;
    }
}
