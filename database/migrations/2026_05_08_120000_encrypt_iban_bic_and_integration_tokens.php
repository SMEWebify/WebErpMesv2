<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Élargit les colonnes IBAN/BIC/IBAN-Qonto en `text` puis chiffre
     * les valeurs en clair. Idempotent : si une valeur est déjà chiffrée,
     * elle est laissée telle quelle.
     */
    public function up(): void
    {
        if (Schema::hasTable('factory')) {
            Schema::table('factory', function (Blueprint $table) {
                $table->text('iban')->nullable()->change();
                $table->text('bic')->nullable()->change();
            });
        }

        if (Schema::hasTable('qonto_connections')) {
            Schema::table('qonto_connections', function (Blueprint $table) {
                $table->text('iban')->nullable()->change();
            });
        }

        $this->encryptColumn('factory', ['iban', 'bic']);
        $this->encryptColumn('qonto_connections', ['iban', 'access_token', 'refresh_token']);
        $this->encryptSettings(['n2p_api_token']);
    }

    public function down(): void
    {
        // Pas de rollback : on ne révèle jamais des secrets en clair en
        // redescendant la migration. Restauration via backup si besoin.
    }

    private function encryptColumn(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->orderBy('id')->each(function ($row) use ($table, $columns) {
            $updates = [];

            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                if ($this->isEncrypted($value)) {
                    continue;
                }

                $updates[$column] = Crypt::encryptString($value);
            }

            if ($updates !== []) {
                DB::table($table)->where('id', $row->id)->update($updates);
            }
        });
    }

    private function encryptSettings(array $keys): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach ($keys as $key) {
            $row = DB::table('settings')->where('key', $key)->first();
            if (! $row) {
                continue;
            }

            $value = $row->value ?? null;
            if ($value === null || $value === '' || $this->isEncrypted($value)) {
                continue;
            }

            DB::table('settings')
                ->where('id', $row->id)
                ->update(['value' => Crypt::encryptString($value)]);
        }
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
