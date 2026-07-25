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

    foreach (['PlatformIdentity', 'Identity', 'Tenancy', 'Catalog'] as $module) {
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

        if ($module === 'Identity') {
            $infrastructureDependencies[] = Layer::withName('Tenancy Application');
            $bootstrapDependencies[] = Layer::withName('Tenancy Application');
        }

        array_push($layers, $domain, $application, $infrastructure, $presentation, $bootstrap);
        array_push(
            $rulesets,
            Ruleset::forLayer($domain)->accesses($sharedDomain),
            Ruleset::forLayer($application)->accesses(...$applicationDependencies),
            Ruleset::forLayer($infrastructure)->accesses(...$infrastructureDependencies),
            Ruleset::forLayer($presentation)->accesses(
                $application,
                $domain,
                $sharedApplication,
                $sharedDomain,
            ),
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
