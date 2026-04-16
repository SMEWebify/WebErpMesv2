<?php

namespace App\Providers;

use App\Services\AI\AIGateway;
use App\Services\AI\Modules\ERPAssistantModule;
use App\Services\AI\Providers\ClaudeProvider;
use App\Services\AI\Providers\PythonMLProvider;
use App\Services\AI\Providers\ToolAwareClaudeProvider;
use App\Services\AI\Tools\ERPToolRegistry;
use App\Services\AI\Tools\InvoiceQueryTool;
use App\Services\AI\Tools\OrderQueryTool;
use App\Services\AI\Tools\DailyJournalTool;
use App\Services\AI\Tools\QuoteQueryTool;
use App\Services\AI\Tools\StockQueryTool;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Tools
        $this->app->singleton(OrderQueryTool::class);
        $this->app->singleton(StockQueryTool::class);
        $this->app->singleton(InvoiceQueryTool::class);
        $this->app->singleton(QuoteQueryTool::class);
        $this->app->singleton(DailyJournalTool::class);

        $this->app->singleton(ERPToolRegistry::class, fn ($app) => new ERPToolRegistry(
            $app->make(OrderQueryTool::class),
            $app->make(StockQueryTool::class),
            $app->make(InvoiceQueryTool::class),
            $app->make(QuoteQueryTool::class),
            $app->make(DailyJournalTool::class),
        ));

        // Gateway avec tous les providers
        $this->app->singleton(AIGateway::class, function ($app) {
            $gateway = new AIGateway();
            $gateway->registerProvider(new ClaudeProvider());
            $gateway->registerProvider(new PythonMLProvider());
            $gateway->registerProvider(new ToolAwareClaudeProvider($app->make(ERPToolRegistry::class)));
            return $gateway;
        });

        // Module assistant ERP
        $this->app->singleton(ERPAssistantModule::class, fn ($app) => new ERPAssistantModule(
            $app->make(AIGateway::class),
            $app->make(ERPToolRegistry::class),
        ));
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ai.php', 'ai');
    }
}
