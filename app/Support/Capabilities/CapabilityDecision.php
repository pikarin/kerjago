<?php

namespace App\Support\Capabilities;

use App\Enums\CapabilityDenialReason;

/**
 * The answer to "may this employer do this?" — allowed, plus why not.
 *
 * Never collapsed to a bool. Two of the three gated surfaces degrade rather
 * than refuse, and both need the reason to choose their treatment; a boolean
 * would push that decision back out to the call sites the resolver exists to
 * keep ignorant.
 *
 * @phpstan-type SerializedDecision array{allowed: bool, reason: string|null}
 */
final readonly class CapabilityDecision
{
    private function __construct(
        public bool $allowed,
        public ?CapabilityDenialReason $reason,
    ) {}

    public static function allow(): self
    {
        return new self(true, null);
    }

    public static function deny(CapabilityDenialReason $reason): self
    {
        return new self(false, $reason);
    }

    public function isDenied(): bool
    {
        return ! $this->allowed;
    }

    /**
     * @return SerializedDecision
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'reason' => $this->reason?->value,
        ];
    }
}
