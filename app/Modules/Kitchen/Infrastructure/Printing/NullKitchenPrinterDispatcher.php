<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Infrastructure\Printing;

use App\Modules\Kitchen\Application\Contracts\KitchenPrinterDispatcher;
use App\Modules\Kitchen\Application\Data\PrinterDispatchResult;
use App\Modules\Kitchen\Domain\Models\KitchenPrintJob;

final class NullKitchenPrinterDispatcher implements KitchenPrinterDispatcher
{
    public function dispatch(KitchenPrintJob $job): PrinterDispatchResult
    {
        return PrinterDispatchResult::sent();
    }
}
