<?php

namespace App\Enums;

/**
 * Why a Candidate Unlock exists. Only AutoFirstTen is issued today; the other
 * two are the seams for purchased packages and staff support overrides, and
 * exist now so adding them later needs no migration.
 */
enum UnlockSource: string
{
    case AutoFirstTen = 'auto_first_10';
    case Purchased = 'purchased';
    case Admin = 'admin';
}
