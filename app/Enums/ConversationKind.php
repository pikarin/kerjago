<?php

namespace App\Enums;

/**
 * Kerjago's conversation vocabulary.
 *
 * Deliberately host-side rather than inside app/Chat/: "cold outreach" is
 * recruitment language, and the chat module stores `kind` as an opaque string
 * so it can be extracted without carrying Kerjago's domain with it.
 *
 * No HasOptions or label() here, unlike the other enums: ConversationResource
 * emits the raw value and the display labels live in ConversationList.vue.
 * A label() method would be a second copy of those strings with no caller.
 */
enum ConversationKind: string
{
    /** Bound to an Application — the jobseeker applied, both sides can talk. */
    case Application = 'application';

    /** An employer contacted a jobseeker found through Talent Search. */
    case ColdOutreach = 'cold_outreach';

    /** Internal Kerjago team talking to a jobseeker or an employer. */
    case Internal = 'internal';
}
