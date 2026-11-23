<?php

namespace App\Providers;

use App\Services\Integrations\FactoryTwin\ErpTwinDataProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Jumeau numérique d'atelier (package commercial nestprod/factory-twin).
 *
 * Le package n'est PAS une dépendance Composer de l'ERP (licence propriétaire,
 * ERP en LGPL) : il est chargé depuis le dossier FACTORY_TWIN_PATH s'il existe.
 * Sans lui, /production/digital-twin reste la page de présentation commerciale
 * et rien d'autre ne change (pas de table twin_scenes, pas de route en plus).
 */
class FactoryTwinServiceProvider extends ServiceProvider
{
    private const PACKAGE_PROVIDER = 'Nestprod\\FactoryTwin\\FactoryTwinServiceProvider';

    private const CONTRACT = 'Nestprod\\FactoryTwin\\Contracts\\TwinDataProvider';

    public static function available(): bool
    {
        return app()->bound('factory-twin.available');
    }

    public function register(): void
    {
        $path = self::packagePath();

        if ($path !== null) {
            self::autoload($path);
        } elseif (! class_exists(self::PACKAGE_PROVIDER)) {
            return; // ni dossier ni installation Composer : page de présentation
        }

        // Enregistré pendant register() : la macro Route::factoryTwin doit exister
        // avant le chargement de routes/web.php.
        $this->app->register(self::PACKAGE_PROVIDER);
        $this->app->bind(self::CONTRACT, ErpTwinDataProvider::class);
        $this->app->instance('factory-twin.available', true);

        if ($path !== null) {
            $this->loadMigrationsFrom($path . '/database/migrations');
        }
    }

    /**
     * Autoloader PSR-4 propre au package. Pas `addPsr4()` de Composer : il garde en
     * mémoire les classes déjà cherchées sans succès et ne les retrouverait plus.
     */
    private static function autoload(string $path): void
    {
        static $registered = [];
        if (isset($registered[$path])) {
            return;
        }
        $registered[$path] = true;

        spl_autoload_register(function (string $class) use ($path) {
            $prefix = 'Nestprod\\FactoryTwin\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }
            $file = $path . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }

    /** Dossier du package (absolu, ou relatif à la racine de l'ERP), s'il est bien là. */
    private static function packagePath(): ?string
    {
        $configured = trim((string) config('services.factory_twin.path'));
        if ($configured === '') {
            return null;
        }

        $path = preg_match('#^([a-zA-Z]:)?[\\\\/]#', $configured) ? $configured : base_path($configured);
        $path = rtrim(str_replace('\\', '/', $path), '/');

        return is_file($path . '/src/FactoryTwinServiceProvider.php') ? $path : null;
    }
}
