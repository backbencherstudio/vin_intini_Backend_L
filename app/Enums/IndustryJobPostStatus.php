<?php

namespace App\Enums;

enum IndustryJobPostStatus: string
{
    case DRAFT = 'draft';

    case PENDING_REVIEW = 'pending_review';

    case APPROVED = 'approved';

    case REJECTED = 'rejected';

    case EXPIRED = 'expired';
}
