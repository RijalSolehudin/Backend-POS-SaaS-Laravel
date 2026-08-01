<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Enums;

enum RecipeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
