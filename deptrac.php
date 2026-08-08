<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $sharedDomain = Layer::withName('Shared Domain')->collectors(
        DirectoryConfig::create('app/Shared/Domain/.*'),
    );
    $sharedApplication = Layer::withName('Shared Application')->collectors(
        DirectoryConfig::create('app/Shared/Application/.*'),
    );
    $sharedInfrastructure = Layer::withName('Shared Infrastructure')->collectors(
        DirectoryConfig::create('app/Shared/Infrastructure/.*'),
    );

    $layers = [
        $sharedDomain,
        $sharedApplication,
        $sharedInfrastructure,
    ];
    $rulesets = [
        Ruleset::forLayer($sharedDomain),
        Ruleset::forLayer($sharedApplication)->accesses($sharedDomain),
        Ruleset::forLayer($sharedInfrastructure)->accesses(
            $sharedApplication,
            $sharedDomain,
        ),
    ];

    foreach (['PlatformIdentity', 'Identity', 'Tenancy', 'Catalog', 'Sales', 'Dining', 'Kitchen', 'OrderingChannel', 'PaymentsGateway', 'Reservation', 'Promotion', 'Reporting'] as $module) {
        $domain = Layer::withName("{$module} Domain")->collectors(
            DirectoryConfig::create("app/Modules/{$module}/Domain/.*"),
        );
        $application = Layer::withName("{$module} Application")->collectors(
            DirectoryConfig::create("app/Modules/{$module}/Application/.*"),
        );
        $infrastructure = Layer::withName("{$module} Infrastructure")->collectors(
            DirectoryConfig::create("app/Modules/{$module}/Infrastructure/(?!Providers/).*"),
        );
        $presentation = Layer::withName("{$module} Presentation")->collectors(
            DirectoryConfig::create("app/Modules/{$module}/Presentation/.*"),
        );
        $bootstrap = Layer::withName("{$module} Bootstrap")->collectors(
            DirectoryConfig::create("app/Modules/{$module}/Infrastructure/Providers/.*"),
        );
        $applicationDependencies = [
            $domain,
            $sharedApplication,
            $sharedDomain,
        ];

        if ($module === 'Tenancy') {
            $applicationDependencies[] = Layer::withName('Identity Application');
        }

        if (in_array($module, ['Catalog', 'Sales', 'Dining', 'Kitchen', 'OrderingChannel', 'PaymentsGateway', 'Reservation', 'Promotion', 'Reporting'], true)) {
            $applicationDependencies[] = Layer::withName('Tenancy Application');
        }

        if ($module === 'Sales') {
            $applicationDependencies[] = Layer::withName('Catalog Application');
        }

        if ($module === 'Dining') {
            $applicationDependencies[] = Layer::withName('Sales Application');
        }

        if ($module === 'Kitchen') {
            $applicationDependencies[] = Layer::withName('Catalog Application');
            $applicationDependencies[] = Layer::withName('Sales Application');
        }

        if ($module === 'OrderingChannel') {
            $applicationDependencies[] = Layer::withName('Catalog Application');
            $applicationDependencies[] = Layer::withName('Dining Application');
            $applicationDependencies[] = Layer::withName('Dining Domain');
            $applicationDependencies[] = Layer::withName('Sales Application');
            $applicationDependencies[] = Layer::withName('Sales Domain');
        }

        if ($module === 'PaymentsGateway') {
            $applicationDependencies[] = Layer::withName('Sales Application');
            $applicationDependencies[] = Layer::withName('Sales Domain');
        }

        if ($module === 'Reservation') {
            $applicationDependencies[] = Layer::withName('Dining Application');
            $applicationDependencies[] = Layer::withName('Dining Domain');
        }

        if ($module === 'Promotion') {
            $applicationDependencies[] = Layer::withName('Sales Domain');
        }

        $infrastructureDependencies = [
            $application,
            $domain,
            $sharedInfrastructure,
            $sharedApplication,
            $sharedDomain,
        ];
        $bootstrapDependencies = [
            $presentation,
            $infrastructure,
            $application,
            $domain,
            $sharedInfrastructure,
            $sharedApplication,
            $sharedDomain,
        ];

        if ($module === 'Tenancy') {
            $infrastructureDependencies[] = Layer::withName('Identity Application');
            $bootstrapDependencies[] = Layer::withName('Identity Application');
        }

        if (in_array($module, ['Catalog', 'Sales', 'Dining', 'Kitchen', 'OrderingChannel', 'PaymentsGateway', 'Reservation', 'Promotion', 'Reporting'], true)) {
            $infrastructureDependencies[] = Layer::withName('Tenancy Application');
            $bootstrapDependencies[] = Layer::withName('Tenancy Application');
        }

        if ($module === 'Dining') {
            $bootstrapDependencies[] = Layer::withName('Sales Application');
        }

        if ($module === 'Kitchen') {
            $bootstrapDependencies[] = Layer::withName('Identity Domain');
            $bootstrapDependencies[] = Layer::withName('Catalog Application');
            $bootstrapDependencies[] = Layer::withName('Sales Application');
        }

        if ($module === 'OrderingChannel') {
            $bootstrapDependencies[] = Layer::withName('Catalog Application');
            $bootstrapDependencies[] = Layer::withName('Dining Application');
            $bootstrapDependencies[] = Layer::withName('Dining Domain');
            $bootstrapDependencies[] = Layer::withName('Sales Application');
            $bootstrapDependencies[] = Layer::withName('Sales Domain');
        }

        if ($module === 'PaymentsGateway') {
            $bootstrapDependencies[] = Layer::withName('Sales Application');
            $bootstrapDependencies[] = Layer::withName('Sales Domain');
        }

        if ($module === 'Reservation') {
            $bootstrapDependencies[] = Layer::withName('Dining Application');
            $bootstrapDependencies[] = Layer::withName('Dining Domain');
        }

        if ($module === 'Promotion') {
            $bootstrapDependencies[] = Layer::withName('Sales Domain');
        }

        if ($module === 'Identity') {
            $infrastructureDependencies[] = Layer::withName('Tenancy Application');
            $bootstrapDependencies[] = Layer::withName('Tenancy Application');
        }

        $presentationDependencies = [
            $application,
            $domain,
            $sharedApplication,
            $sharedDomain,
        ];

        if (in_array($module, ['Catalog', 'Sales', 'Dining', 'Kitchen', 'OrderingChannel', 'PaymentsGateway', 'Reservation', 'Promotion', 'Reporting'], true)) {
            $presentationDependencies[] = Layer::withName('Tenancy Application');
        }

        if ($module === 'Kitchen') {
            $presentationDependencies[] = Layer::withName('Tenancy Domain');
        }

        array_push($layers, $domain, $application, $infrastructure, $presentation, $bootstrap);
        array_push(
            $rulesets,
            Ruleset::forLayer($domain)->accesses($sharedDomain),
            Ruleset::forLayer($application)->accesses(...$applicationDependencies),
            Ruleset::forLayer($infrastructure)->accesses(...$infrastructureDependencies),
            Ruleset::forLayer($presentation)->accesses(...$presentationDependencies),
            Ruleset::forLayer($bootstrap)->accesses(...$bootstrapDependencies),
        );
    }

    $config
        ->paths('./app')
        ->excludeFiles('#app/(Http|Models|Providers)/#')
        ->layers(...$layers)
        ->rulesets(...$rulesets)
        ->cacheFile('./storage/framework/cache/deptrac.cache');
};
