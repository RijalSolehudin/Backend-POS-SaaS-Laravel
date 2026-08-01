<?php

declare(strict_types=1);

return [
    'cash_out_approval_threshold_minor' => (int) env('SALES_CASH_OUT_APPROVAL_THRESHOLD_MINOR', 0),
    'audit_retention_years' => (int) env('SALES_AUDIT_RETENTION_YEARS', 2),
];
