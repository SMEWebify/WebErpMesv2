<?php

namespace App\Providers;

use App\Services\AI\AIGateway;
use App\Services\AI\Providers\ClaudeProvider;
use App\Services\AI\Providers\PythonMLProvider;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIGateway::class, function () {
            $gateway = new AIGateway();
            $gateway->registerProvider(new ClaudeProvider());
            $gateway->registerProvider(new PythonMLProvider());
            return $gateway;
        });
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ai.php', 'ai');
    }
}
