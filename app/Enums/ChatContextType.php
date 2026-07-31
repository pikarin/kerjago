<?php

namespace App\Enums;

/**
 * The host records a conversation may be bound to.
 *
 * The chat module stores `context_type` as an opaque string and never inspects
 * it; this enum is the host's own list of legal values, and
 * App\Support\Chat\DomainContextResolver is what turns them into labels.
 */
enum ChatContextType: string
{
    case Job = 'job';
    case Application = 'application';
}
