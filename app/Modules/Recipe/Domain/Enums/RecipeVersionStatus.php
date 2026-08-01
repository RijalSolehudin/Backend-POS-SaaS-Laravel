<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Enums;

enum RecipeVersionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
