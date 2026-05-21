<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;

class OpenSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            $config = config('services.opensearch');

            $builder = ClientBuilder::create()
                ->setHosts($config['hosts'] ?? ['http://localhost:9200']);

            if (! empty($config['username']) && ! empty($config['password'])) {
                $builder->setBasicAuthentication($config['username'], $config['password']);
            }

            return $builder->build();
        });
    }
}
