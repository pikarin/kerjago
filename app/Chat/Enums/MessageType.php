<?php

namespace App\Chat\Enums;

/**
 * Chat mechanics, not Kerjago vocabulary — safe to live inside the module.
 *
 * Contrast with conversation `kind` and `context_type`, which are opaque
 * strings here because their values ("cold_outreach", "job") are domain
 * language the module must not know. See App\Enums\ConversationKind.
 */
enum MessageType: string
{
    /** Written by a participant. */
    case Text = 'text';

    /** Emitted by the host application; carries no participant. */
    case System = 'system';
}
