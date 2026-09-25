<?php

namespace App\Enums;

enum IndustryJobPostStatus: string
{
    case DRAFT     = 'draft';
    case PENDING   = 'pending';
    case PUBLISHED = 'published';
    case REJECTED  = 'rejected';
    case ARCHIVE   = 'archive';
    case EXPIRED   = 'expired';
}
