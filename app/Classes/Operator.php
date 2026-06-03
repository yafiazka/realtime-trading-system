<?php


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

    public function isGreaterThan(OperatorAmount $other): bool
    {
        return $this->amount->isGreaterThan($other->amount);
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function __toString(): string
    {
        return (string) $this->amount;
    }
}
