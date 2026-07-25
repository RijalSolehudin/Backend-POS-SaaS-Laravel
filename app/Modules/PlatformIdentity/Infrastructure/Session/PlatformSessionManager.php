<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Session;

use Illuminate\Contracts\Container\Container;
use Illuminate\Session\EncryptedStore;
use Illuminate\Session\SessionManager;

final class PlatformSessionManager extends SessionManager
{
    /** @var array<string, mixed> */
    private array $platformConfig;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $session = config('platform_identity.session');

        $this->platformConfig = [
            'driver' => 'database',
            'connection' => config('session.connection'),
            'table' => $session['table'],
            'lifetime' => $session['idle_minutes'],
            'expire_on_close' => false,
            'encrypt' => true,
            'cookie' => $session['cookie'],
            'path' => $session['path'],
            'domain' => config('session.domain'),
            'secure' => $session['secure'],
            'http_only' => true,
            'same_site' => $session['same_site'],
            'partitioned' => false,
            'serialization' => 'json',
            'lottery' => config('session.lottery', [2, 100]),
        ];
    }

    public function getDefaultDriver(): string
    {
        return 'database';
    }

    /**
     * @return array<string, mixed>
     */
    public function getSessionConfig(): array
    {
        return $this->platformConfig;
    }

    protected function createDatabaseDriver(): EncryptedStore
    {
        $handler = new PlatformDatabaseSessionHandler(
            $this->container->make('db')->connection($this->platformConfig['connection']),
            $this->platformConfig['table'],
            $this->platformConfig['lifetime'],
            $this->container,
        );

        return new EncryptedStore(
            $this->platformConfig['cookie'],
            $handler,
            $this->container->make('encrypter'),
            null,
            'json',
        );
    }
}
