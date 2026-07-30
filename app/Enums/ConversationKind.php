<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Kerjago's conversation vocabulary.
 *
 * Deliberately host-side rather than inside app/Chat/: "cold outreach" is
 * recruitment language, and the chat module stores `kind` as an opaque string
 * so it can be extracted without carrying Kerjago's domain with it.
 */
enum ConversationKind: string
{
    use HasOptions;

    /** Bound to an Application — the jobseeker applied, both sides can talk. */
    case Application = 'application';

    /** An employer contacted a jobseeker found through Talent Search. */
    case ColdOutreach = 'cold_outreach';

    /** Internal Kerjago team talking to a jobseeker or an employer. */
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Application => 'Application',
            self::ColdOutreach => 'Outreach',
            self::Internal => 'Kerjago',
        };
    }
}
