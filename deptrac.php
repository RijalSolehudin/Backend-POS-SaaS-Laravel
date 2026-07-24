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
            DirectoryConfig::create("app/Modules/{$module}/Infrastructure/.*"),
        );
        $presentation = Layer::withName("{$module} Presentation")->collectors(
            DirectoryConfig::create("app/Modules/{$module}/Presentation/.*"),
        );

        array_push($layers, $domain, $application, $infrastructure, $presentation);
        array_push(
            $rulesets,
            Ruleset::forLayer($domain)->accesses($sharedDomain),
            Ruleset::forLayer($application)->accesses(
                $domain,
                $sharedApplication,
                $sharedDomain,
            ),
            Ruleset::forLayer($infrastructure)->accesses(
                $application,
                $domain,
                $sharedInfrastructure,
                $sharedApplication,
                $sharedDomain,
            ),
            Ruleset::forLayer($presentation)->accesses(
                $application,
                $domain,
                $sharedApplication,
                $sharedDomain,
            ),
        );
    }

    $config
        ->paths('./app')
        ->excludeFiles('#app/(Http|Models|Providers)/#')
        ->layers(...$layers)
        ->rulesets(...$rulesets)
        ->cacheFile('./storage/framework/cache/deptrac.cache');
};
