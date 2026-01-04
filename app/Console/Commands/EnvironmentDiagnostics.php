<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnvironmentDiagnostics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wem:diagnostics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check common local setup requirements for WebErpMesv2.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running environment diagnostics...');
        $failures = 0;
        $warnings = 0;

        $failures += $this->check(
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'PHP version is compatible (>= 8.2).',
            'PHP 8.2 or later is required.'
        );

        $failures += $this->check(
            extension_loaded('zip'),
            'ZIP extension is enabled.',
            'The PHP ZIP extension is missing.'
        );

        $failures += $this->check(
            !empty(config('app.key')),
            'APP_KEY is set.',
            'APP_KEY is missing; run "php artisan key:generate".'
        );

        $failures += $this->check(
            $this->isWritable(storage_path()) && $this->isWritable(base_path('bootstrap/cache')),
            'Storage and bootstrap/cache directories are writable.',
            'Storage and bootstrap/cache must be writable by the web server.'
        );

        $warnings += $this->check(
            $this->isRedisReady(),
            'Redis configuration detected for cache/session/queue.',
            'Redis connection details appear incomplete; cache, session, or queues may fail.',
            'warn'
        );

        $warnings += $this->check(
            $this->isDatabaseConfigured(),
            'Database credentials appear customized.',
            'Database credentials look like defaults; set DB_* to real values.',
            'warn'
        );

        $warnings += $this->check(
            $this->isPusherConfigured(),
            'Broadcasting configuration looks valid.',
            'Broadcast driver is "pusher" but credentials are missing.',
            'warn'
        );

        if ($failures === 0 && $warnings === 0) {
            $this->info('Diagnostics completed without issues.');
            return self::SUCCESS;
        }

        if ($failures === 0) {
            $this->warn('Diagnostics completed with warnings. Please review the messages above.');
            return self::SUCCESS;
        }

        $this->error('Diagnostics completed with failures. Please address the errors above.');
        return self::FAILURE;
    }

    private function check(bool $condition, string $success, string $failure, string $severity = 'fail'): int
    {
        if ($condition) {
            $this->line("✔ {$success}");
            return 0;
        }

        if ($severity === 'warn') {
            $this->warn("⚠ {$failure}");
            return 1;
        }

        $this->error("✖ {$failure}");
        return 1;
    }

    private function isRedisReady(): bool
    {
        $drivers = [
            config('cache.default'),
            config('session.driver'),
            config('queue.default'),
        ];

        $usesRedis = in_array('redis', $drivers, true);
        $host = env('REDIS_HOST');

        return !$usesRedis || !empty($host);
    }

    private function isDatabaseConfigured(): bool
    {
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        return $database !== 'wem' || $username !== 'wem_user' || $password !== 'password';
    }

    private function isPusherConfigured(): bool
    {
        if (config('broadcasting.default') !== 'pusher') {
            return true;
        }

        return !empty(env('PUSHER_APP_ID')) && !empty(env('PUSHER_APP_KEY')) && !empty(env('PUSHER_APP_SECRET'));
    }

    private function isWritable(string $path): bool
    {
        return is_dir($path) && is_writable($path);
    }
}
