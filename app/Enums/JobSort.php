<?php

namespace App\Enums;

enum JobSort: string
{
    case Relevance = 'relevance';
    case Newest = 'newest';
}
