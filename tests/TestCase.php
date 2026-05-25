<?php

namespace Tests;

use App\Models\Admin\Factory as FactoryModel;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent Vite from throwing ViteManifestNotFoundException when
        // public/build/manifest.json doesn't exist (e.g. CI without npm build).
        $this->withoutVite();

        $this->withoutMiddleware([
            \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);

        if (!FactoryModel::query()->exists()) {
            FactoryModel::create(['name' => 'Test Factory']);
        }
        if (!AccountingVat::exists()) {
            AccountingVat::factory()->create();
        }
        if (!AccountingPaymentConditions::exists()) {
            AccountingPaymentConditions::factory()->create();
        }
        if (!AccountingPaymentMethod::exists()) {
            AccountingPaymentMethod::factory()->create();
        }
        if (!AccountingDelivery::exists()) {
            AccountingDelivery::factory()->create();
        }
        if (!MethodsUnits::exists()) {
            MethodsUnits::factory()->create();
        }
    }

    protected function authenticateApiUser(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        return $user;
    }
}
