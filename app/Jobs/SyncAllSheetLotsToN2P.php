<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncAllSheetLotsToN2P implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(public readonly int $days = 30)
    {
    }

    public function handle(): void
    {
        Artisan::call('wem:n2p:sync-sheet-stock', ['--days' => $this->days]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('n2p')->error('SyncAllSheetLotsToN2P failed', [
            'days' => $this->days,
            'error' => $exception->getMessage(),
        ]);
    }
}
