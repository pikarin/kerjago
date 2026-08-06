<?php

namespace App\Support\Masking;

use Illuminate\Support\Str;

/**
 * The masks a Locked Candidate is rendered with.
 *
 * One class rather than a mask per Resource, so the web pages, Admingo and any
 * future JSON API cannot disagree about how much of a name is shown. Every mask
 * is fixed-length: a bullet run that tracked the real length would leak it, and
 * a preserved email domain would tell an employer where the candidate works.
 *
 * Masking is one-way and computed server-side. Nothing here is reversible, and
 * the raw value never leaves PHP for a locked candidate.
 */
class Mask
{
    private const string BULLET = '•';

    private const int FIXED_LENGTH = 5;

    /**
     * First word whole, every later word reduced to an initial: "Budi Santoso
     * Wijaya" becomes "Budi S. W.". A single-word name is returned as-is —
     * there is nothing to withhold, and padding it would invent a surname.
     */
    public static function name(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $words = preg_split('/\s+/', trim($name), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return null;
        }

        $first = array_shift($words);

        $initials = array_map(
            fn (string $word): string => Str::upper(Str::substr($word, 0, 1)).'.',
            $words,
        );

        return trim($first.' '.implode(' ', $initials));
    }

    /**
     * Local part, domain name and TLD all replaced. Keeping the TLD would still
     * be a hint, and keeping the domain would name the candidate's employer.
     */
    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        return self::bullets().'@'.self::bullets().'.'.self::bullets();
    }

    /**
     * Nothing is preserved, not even the country code: a masked prefix plus a
     * masked tail is still enough to narrow a number down.
     */
    public static function phone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        return self::bullets();
    }

    private static function bullets(): string
    {
        return str_repeat(self::BULLET, self::FIXED_LENGTH);
    }
}
