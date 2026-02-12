<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreOrdersUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        Config::set('filesystems.default', 'local');
        Storage::fake('local');
    }

    public function test_it_runs_python_script_after_pre_order_pdf_upload_when_configured(): void
    {
        Process::fake([
            '*' => Process::result('ok'),
        ]);

        Config::set('pre_orders.python_executable', '/usr/bin/python3');
        Config::set('pre_orders.python_script', '/tmp/pre-orders/main.py');
        Config::set('pre_orders.input_path', 'pre-orders/input');

        $this->actingAs(User::factory()->create());

        $response = $this->post(route('pre-orders.upload'), [
            'pdfs' => [
                UploadedFile::fake()->create('commande-client.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('pre-orders.index'))
            ->assertSessionHas('success', 'PDF(s) envoyé(s) et traitement Python exécuté avec succès.');

        $this->assertCount(1, Storage::disk('local')->files('pre-orders/input'));
    }

    public function test_it_only_uploads_pdfs_when_python_script_is_not_configured(): void
    {
        Process::fake();

        Config::set('pre_orders.python_executable', null);
        Config::set('pre_orders.python_script', null);
        Config::set('pre_orders.input_path', 'pre-orders/input');

        $this->actingAs(User::factory()->create());

        $response = $this->post(route('pre-orders.upload'), [
            'pdfs' => [
                UploadedFile::fake()->create('commande-client.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('pre-orders.index'))
            ->assertSessionHas('success', 'PDF(s) envoyé(s) dans le stockage avec succès.');

        Process::assertNothingRan();
    }
}
