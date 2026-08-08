<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Contracts;

use App\Modules\Kitchen\Application\Data\PrinterDispatchResult;
use App\Modules\Kitchen\Domain\Models\KitchenPrintJob;

interface KitchenPrinterDispatcher
{
    public function dispatch(KitchenPrintJob $job): PrinterDispatchResult;
}
