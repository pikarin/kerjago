<?php

namespace App\Enums;

/**
 * Something an employer may do, named as a product feature rather than as a
 * code path — this is the vocabulary a package will one day be sold in, so
 * `BrowseTalentInFull` rather than `ViewFacetCounts`.
 *
 * Deliberately coarse. One capability covers everything one purchasable
 * feature covers; splitting it finer would mean the sales page and the enum
 * stop matching.
 */
enum EmployerCapability: string
{
    /**
     * Put a job ad live. Denied means the ad lands on Pending rather than
     * being refused — the employer still gets to write it.
     */
    case PublishJob = 'publish_job';

    /**
     * Talent Search at full depth: every page, facet counts and the result
     * total. Denied still returns results, just a shallow, uncounted view.
     */
    case BrowseTalentInFull = 'browse_talent_in_full';

    /**
     * Write in conversations with candidates. Denied leaves existing threads
     * readable and refuses anything new. Never covers staff threads — those
     * stay open so a company can always reach support.
     */
    case ParticipateInChat = 'participate_in_chat';
}
