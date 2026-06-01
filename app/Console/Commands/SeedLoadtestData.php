<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Génère un jeu de données réaliste pour les tests de charge (k6).
 *
 * Écrit UNIQUEMENT dans la connexion "loadtest" (base wem_loadtest par défaut).
 * Refuse de tourner sur toute base dont le nom ne contient pas "loadtest",
 * afin de ne JAMAIS toucher la base de développement/production.
 *
 * Inserts bruts par lots : contourne observers, events métier et activity-log
 * (rapide et fidèle au schéma). Volumes pilotés par --scale.
 *
 *   php artisan loadtest:seed --fresh --scale=0.2     # smoke (~20 %)
 *   php artisan loadtest:seed --fresh --scale=1.0     # profil "client mûr"
 */
class SeedLoadtestData extends Command
{
    protected $signature = 'loadtest:seed
        {--scale=1.0 : Facteur d\'échelle global (1.0 = profil "client mûr")}
        {--tasks-per-line=8 : Tâches (gamme) par ligne de commande}
        {--activities-per-task=10 : Pointages atelier par tâche}
        {--fresh : Vide les tables loadtest avant génération}';

    protected $description = 'Génère un dataset de test de charge dans la base wem_loadtest';

    private Connection $db;
    private float $scale;
    private int $seq = 0;
    private Carbon $start;

    /** Référentiels résolus une seule fois. */
    private array $ref = [];

    public function handle(): int
    {
        $this->db = DB::connection('loadtest');
        $dbName = $this->db->getDatabaseName();

        if (! str_contains($dbName, 'loadtest')) {
            $this->error("SÉCURITÉ : la connexion 'loadtest' pointe sur « {$dbName} », qui ne contient pas 'loadtest'.");
            $this->error('Refus d\'écrire. Configurez DB_LOADTEST_DATABASE=wem_loadtest dans .env.');
            return self::FAILURE;
        }

        $this->scale = max(0.01, (float) $this->option('scale'));
        $this->start = Carbon::create(2024, 1, 1, 8, 0, 0);
        $tasksPerLine = max(0, (int) $this->option('tasks-per-line'));
        $actPerTask = max(0, (int) $this->option('activities-per-task'));

        $this->info("Base cible : {$dbName} | scale={$this->scale} | tâches/ligne={$tasksPerLine} | pointages/tâche={$actPerTask}");

        if ($this->option('fresh')) {
            $this->truncateAll();
        }

        $this->db->statement('SET FOREIGN_KEY_CHECKS=0');

        $this->resolveReferentials();

        // Sociétés (clients + fournisseurs) ----------------------------------
        $companyIds = $this->seedCompanies($this->n(300));
        [$contactByCompany, $addressByCompany] = $this->seedContactsAddresses($companyIds);

        // Produits + stock ---------------------------------------------------
        $productIds = $this->seedProducts($this->n(800));
        $slpIds = $this->seedStockLocationProducts($productIds);
        $this->seedStockMoves($slpIds, $this->n(50000));

        // Devis --------------------------------------------------------------
        $this->seedQuotes($this->n(5000), $companyIds, $contactByCompany, $addressByCompany, $productIds);

        // Commandes + lignes + tâches + pointages (le cœur du coût) ----------
        [$orderIds, $orderLineIds] = $this->seedOrders(
            $this->n(3000), $companyIds, $contactByCompany, $addressByCompany, $productIds
        );
        $taskIds = $this->seedTasks($orderLineIds, $tasksPerLine, $productIds);
        $this->seedTaskActivities($taskIds, $actPerTask);

        // Livraisons + factures + écritures comptables (FEC) -----------------
        $this->seedDeliveriesInvoicesAccounting($orderIds, $orderLineIds, $companyIds, $contactByCompany, $addressByCompany);

        // Achats -------------------------------------------------------------
        $this->seedPurchases($this->n(1000), $companyIds, $contactByCompany, $addressByCompany, $taskIds, $slpIds);

        $this->db->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('Génération terminée.');
        $this->table(['Table', 'Lignes'], $this->counts());

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- helpers

    private function n(int $base): int
    {
        return max(1, (int) round($base * $this->scale));
    }

    private function code(string $prefix): string
    {
        return $prefix . '-' . str_pad((string) (++$this->seq), 7, '0', STR_PAD_LEFT);
    }

    private function uuid(): string
    {
        // UUID v4 déterministe-ish (pas de Str::uuid pour rester rapide et hors events)
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function someDate(): Carbon
    {
        return (clone $this->start)->addMinutes(random_int(0, 760 * 24 * 60));
    }

    private function ts(Carbon $d): string
    {
        return $d->format('Y-m-d H:i:s');
    }

    /** Insère par lots et renvoie les ids nouvellement créés (ordre d'insertion). */
    private function insertReturningIds(string $table, array $rows): array
    {
        if (empty($rows)) {
            return [];
        }
        $before = (int) $this->db->table($table)->max('id');
        foreach (array_chunk($rows, 1000) as $chunk) {
            $this->db->table($table)->insert($chunk);
        }
        return $this->db->table($table)->where('id', '>', $before)->orderBy('id')->pluck('id')->all();
    }

    private function insertBulk(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 1000) as $chunk) {
            $this->db->table($table)->insert($chunk);
        }
    }

    // ------------------------------------------------------------ référentiels

    private function resolveReferentials(): void
    {
        $now = $this->ts(now());

        // user technique (compte de génération + auteur des lignes)
        $this->ref['user'] = (int) ($this->db->table('users')->min('id')
            ?? $this->db->table('users')->insertGetId([
                'name' => 'Loadtest Bot', 'email' => 'loadtest-bot@example.test',
                'password' => Hash::make('password'), 'employment_status' => 1,
                'statu' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]));

        $this->ref['unit'] = $this->firstOrCreate('methods_units',
            ['code' => 'U', 'label' => 'Unité', 'type' => 1, 'default' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $this->ref['service'] = $this->firstOrCreate('methods_services',
            ['code' => 'LASER', 'ordre' => 1, 'label' => 'Découpe laser', 'type' => 1,
             'hourly_rate' => 65, 'margin' => 20, 'color' => '#3498db', 'is_nesting' => 0,
             'created_at' => $now, 'updated_at' => $now]);

        $this->ref['family'] = $this->firstOrCreate('methods_families',
            ['code' => 'FAM1', 'label' => 'Famille standard', 'methods_services_id' => $this->ref['service'],
             'created_at' => $now, 'updated_at' => $now]);

        $this->ref['vat'] = $this->firstOrCreate('accounting_vats',
            ['code' => 'TVA20', 'label' => 'TVA 20%', 'rate' => 20, 'default' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $this->ref['payCond'] = $this->firstOrCreate('accounting_payment_conditions',
            ['code' => '30J', 'label' => '30 jours', 'number_of_month' => 0, 'number_of_day' => 30, 'month_end' => 0,
             'default' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $this->ref['payMethod'] = $this->firstOrCreate('accounting_payment_methods',
            ['code' => 'VIR', 'label' => 'Virement', 'code_account' => '512', 'default' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $this->ref['delivery'] = $this->firstOrCreate('accounting_deliveries',
            ['code' => 'STD', 'label' => 'Standard', 'default' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $this->ref['allocation'] = $this->firstOrCreate('accounting_allocations',
            ['account' => '701000', 'label' => 'Ventes produits finis', 'accounting_vats_id' => $this->ref['vat'],
             'vat_account' => 44571, 'code_account' => 701000, 'type_imputation' => 1,
             'created_at' => $now, 'updated_at' => $now]);

        // Paramètres usine : sinon le middleware check.factory redirige vers le setup
        $this->firstOrCreate('factory',
            ['name' => 'Loadtest Factory', 'accounting_vats_id' => $this->ref['vat'],
             'created_at' => $now, 'updated_at' => $now]);

        // statuts (le listener cherche le titre exact "Finished")
        if ($this->db->table('statuses')->count() === 0) {
            $titles = ['Open', 'Started', 'In progress', 'Finished', 'Suspended', 'To RFQ', 'RFQ in progress', 'Outsourced', 'Supplied'];
            $rows = [];
            foreach ($titles as $i => $t) {
                $rows[] = ['title' => $t, 'order' => $i];
            }
            $this->db->table('statuses')->insert($rows);
        }
        $this->ref['statusIds'] = $this->db->table('statuses')->pluck('id')->all();
        $this->ref['statusFinished'] = (int) ($this->db->table('statuses')->where('title', 'Finished')->value('id') ?? $this->ref['statusIds'][0]);

        // stock (entrepôt + emplacements)
        $this->ref['stock'] = $this->firstOrCreate('stocks',
            ['code' => 'STK1', 'label' => 'Magasin principal', 'user_id' => $this->ref['user'], 'created_at' => $now, 'updated_at' => $now]);
        $this->ref['locationIds'] = $this->db->table('stock_locations')->pluck('id')->all();
        if (empty($this->ref['locationIds'])) {
            $rows = [];
            for ($i = 1; $i <= 10; $i++) {
                $rows[] = ['code' => 'LOC' . $i, 'label' => 'Emplacement ' . $i, 'stocks_id' => $this->ref['stock'],
                    'user_id' => $this->ref['user'], 'created_at' => $now, 'updated_at' => $now];
            }
            $this->ref['locationIds'] = $this->insertReturningIds('stock_locations', $rows);
        }
    }

    private function firstOrCreate(string $table, array $attrs): int
    {
        $existing = $this->db->table($table)->min('id');
        if ($existing) {
            return (int) $existing;
        }
        return (int) $this->db->table($table)->insertGetId($attrs);
    }

    // ------------------------------------------------------------------ seeds

    private function seedCompanies(int $count): array
    {
        $this->line("→ companies ({$count})");
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $isSupplier = $i % 3 === 0;
            $d = $this->ts($this->someDate());
            $rows[] = [
                'code' => $this->code('CLI'),
                'uuid' => $this->uuid(),
                'label' => 'Société ' . ($i + 1),
                'client_type' => 1,
                'statu_customer' => $isSupplier ? 1 : 2,
                'statu_supplier' => $isSupplier ? 2 : 1,
                'recept_controle' => 0,
                'active' => 1,
                'delivery_constraint' => 1,
                'tolerance_days' => 0,
                'quoted_delivery_note' => 0,
                'created_at' => $d, 'updated_at' => $d,
            ];
        }
        return $this->insertReturningIds('companies', $rows);
    }

    private function seedContactsAddresses(array $companyIds): array
    {
        $this->line('→ companies_contacts + companies_addresses');
        $contacts = [];
        $addresses = [];
        $now = $this->ts(now());
        foreach ($companyIds as $cid) {
            $contacts[] = ['companies_id' => $cid, 'ordre' => 1, 'civility' => 'M.', 'first_name' => 'Jean',
                'name' => 'Contact', 'mail' => 'contact' . $cid . '@example.test', 'default' => 1,
                'is_customer_portal_user' => 0, 'created_at' => $now, 'updated_at' => $now];
            $addresses[] = ['companies_id' => $cid, 'ordre' => 1, 'label' => 'Siège', 'adress' => '1 rue de l\'Usine',
                'zipcode' => '69000', 'city' => 'Lyon', 'country' => 'France', 'default' => 1,
                'created_at' => $now, 'updated_at' => $now];
        }
        $this->insertBulk('companies_contacts', $contacts);
        $this->insertBulk('companies_addresses', $addresses);

        // map company -> (contact id, address id) : 1 par société, ids contigus
        $contactRows = $this->db->table('companies_contacts')->orderBy('id')->get(['id', 'companies_id']);
        $addressRows = $this->db->table('companies_addresses')->orderBy('id')->get(['id', 'companies_id']);
        $byContact = [];
        foreach ($contactRows as $r) {
            $byContact[$r->companies_id] ??= $r->id;
        }
        $byAddress = [];
        foreach ($addressRows as $r) {
            $byAddress[$r->companies_id] ??= $r->id;
        }
        return [$byContact, $byAddress];
    }

    private function seedProducts(int $count): array
    {
        $this->line("→ products ({$count})");
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $d = $this->ts($this->someDate());
            $rows[] = [
                'code' => $this->code('PRD'),
                'label' => 'Produit ' . ($i + 1),
                'methods_services_id' => $this->ref['service'],
                'methods_families_id' => $this->ref['family'],
                'purchased' => 1, 'sold' => 1,
                'purchased_price' => random_int(5, 200),
                'selling_price' => random_int(20, 500),
                'methods_units_id' => $this->ref['unit'],
                'tracability_type' => 1,
                'created_at' => $d, 'updated_at' => $d,
            ];
        }
        return $this->insertReturningIds('products', $rows);
    }

    private function seedStockLocationProducts(array $productIds): array
    {
        $this->line('→ stock_location_products');
        $rows = [];
        $now = $this->ts(now());
        foreach ($productIds as $pid) {
            $rows[] = [
                'code' => $this->code('SLP'),
                'user_id' => $this->ref['user'],
                'stock_locations_id' => $this->ref['locationIds'][array_rand($this->ref['locationIds'])],
                'products_id' => $pid,
                'stock_qty' => random_int(0, 2000),
                'reserve_qty' => 0, 'mini_qty' => 10,
                'unit_cost' => random_int(5, 200),
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        return $this->insertReturningIds('stock_location_products', $rows);
    }

    private function seedStockMoves(array $slpIds, int $count): void
    {
        $this->line("→ stock_moves ({$count})");
        $entryTypes = [1, 3, 5, 12];
        $exitTypes = [2, 4, 6, 9];
        $bar = $this->output->createProgressBar($count);
        $buffer = [];
        for ($i = 0; $i < $count; $i++) {
            $entry = $i % 2 === 0;
            $d = $this->ts($this->someDate());
            $buffer[] = [
                'user_id' => $this->ref['user'],
                'qty' => random_int(1, 500),
                'reserve_qty' => 0, 'bad_qty' => 0,
                'stock_location_products_id' => $slpIds[array_rand($slpIds)],
                'typ_move' => $entry ? $entryTypes[array_rand($entryTypes)] : $exitTypes[array_rand($exitTypes)],
                'component_price' => random_int(5, 200),
                'status' => 1,
                'created_at' => $d, 'updated_at' => $d,
            ];
            if (count($buffer) >= 2000) {
                $this->insertBulk('stock_moves', $buffer);
                $buffer = [];
                $bar->advance(2000);
            }
        }
        if ($buffer) {
            $this->insertBulk('stock_moves', $buffer);
            $bar->advance(count($buffer));
        }
        $bar->finish();
        $this->newLine();
    }

    private function seedQuotes(int $count, array $companyIds, array $byContact, array $byAddress, array $productIds): void
    {
        $this->line("→ quotes ({$count}) + lignes (~15/devis) + détails");
        $customerIds = array_values(array_filter($companyIds, fn ($cid) => isset($byContact[$cid])));

        $headers = [];
        $meta = [];
        for ($i = 0; $i < $count; $i++) {
            $cid = $customerIds[array_rand($customerIds)];
            $d = $this->someDate();
            $headers[] = [
                'uuid' => $this->uuid(), 'code' => $this->code('DEV'), 'label' => 'Devis ' . ($i + 1),
                'companies_id' => $cid, 'companies_contacts_id' => $byContact[$cid], 'companies_addresses_id' => $byAddress[$cid],
                'statu' => random_int(1, 3), 'user_id' => $this->ref['user'],
                'accounting_payment_conditions_id' => $this->ref['payCond'],
                'accounting_payment_methods_id' => $this->ref['payMethod'],
                'accounting_deliveries_id' => $this->ref['delivery'],
                'created_at' => $this->ts($d), 'updated_at' => $this->ts($d),
            ];
            $meta[] = $d;
        }
        $quoteIds = $this->insertReturningIds('quotes', $headers);

        $lines = [];
        foreach ($quoteIds as $idx => $qid) {
            $d = $this->ts($meta[$idx]);
            $nb = random_int(10, 20);
            for ($o = 1; $o <= $nb; $o++) {
                $lines[] = [
                    'quotes_id' => $qid, 'ordre' => $o, 'label' => 'Ligne devis ' . $o,
                    'qty' => random_int(1, 1000), 'methods_units_id' => $this->ref['unit'],
                    'selling_price' => random_int(1, 500), 'discount' => 0,
                    'accounting_vats_id' => $this->ref['vat'], 'statu' => 1,
                    'product_id' => (string) $productIds[array_rand($productIds)],
                    'created_at' => $d, 'updated_at' => $d,
                ];
            }
            if (count($lines) >= 2000) {
                $this->flushQuoteLines($lines);
                $lines = [];
            }
        }
        if ($lines) {
            $this->flushQuoteLines($lines);
        }
    }

    private function flushQuoteLines(array $lines): void
    {
        $ids = $this->insertReturningIds('quote_lines', $lines);
        $details = [];
        $now = $this->ts(now());
        foreach ($ids as $lid) {
            $details[] = ['quote_lines_id' => $lid, 'material' => 'S235', 'thickness' => 2,
                'weight' => random_int(1, 50), 'created_at' => $now, 'updated_at' => $now];
        }
        $this->insertBulk('quote_line_details', $details);
    }

    private function seedOrders(int $count, array $companyIds, array $byContact, array $byAddress, array $productIds): array
    {
        $this->line("→ orders ({$count}) + lignes (~12/cmd) + détails");
        $customerIds = array_values(array_filter($companyIds, fn ($cid) => isset($byContact[$cid])));

        $headers = [];
        $meta = [];
        for ($i = 0; $i < $count; $i++) {
            $cid = $customerIds[array_rand($customerIds)];
            $d = $this->someDate();
            $headers[] = [
                'uuid' => $this->uuid(), 'code' => $this->code('CMD'), 'label' => 'Commande ' . ($i + 1),
                'companies_id' => $cid, 'companies_contacts_id' => $byContact[$cid], 'companies_addresses_id' => $byAddress[$cid],
                'statu' => random_int(1, 3), 'type' => 1, 'user_id' => $this->ref['user'],
                'accounting_payment_conditions_id' => $this->ref['payCond'],
                'accounting_payment_methods_id' => $this->ref['payMethod'],
                'accounting_deliveries_id' => $this->ref['delivery'],
                'created_at' => $this->ts($d), 'updated_at' => $this->ts($d),
            ];
            $meta[] = $d;
        }
        $orderIds = $this->insertReturningIds('orders', $headers);

        $allLineIds = [];
        $lines = [];
        $lineMeta = [];
        foreach ($orderIds as $idx => $oid) {
            $d = $this->ts($meta[$idx]);
            $nb = random_int(8, 16);
            for ($o = 1; $o <= $nb; $o++) {
                $qty = random_int(1, 500);
                $lines[] = [
                    'orders_id' => $oid, 'ordre' => $o, 'label' => 'Ligne cmd ' . $o,
                    'qty' => $qty, 'delivered_qty' => 0, 'delivered_remaining_qty' => $qty,
                    'invoiced_qty' => 0, 'invoiced_remaining_qty' => $qty,
                    'methods_units_id' => $this->ref['unit'], 'selling_price' => random_int(1, 500), 'discount' => 0,
                    'accounting_vats_id' => $this->ref['vat'], 'tasks_status' => 1, 'delivery_status' => 1, 'invoice_status' => 1,
                    'product_id' => (string) $productIds[array_rand($productIds)],
                    'created_at' => $d, 'updated_at' => $d,
                ];
                $lineMeta[] = $d;
            }
            if (count($lines) >= 2000) {
                $allLineIds = array_merge($allLineIds, $this->flushOrderLines($lines));
                $lines = [];
            }
        }
        if ($lines) {
            $allLineIds = array_merge($allLineIds, $this->flushOrderLines($lines));
        }
        return [$orderIds, $allLineIds];
    }

    private function flushOrderLines(array $lines): array
    {
        $ids = $this->insertReturningIds('order_lines', $lines);
        $details = [];
        $now = $this->ts(now());
        foreach ($ids as $lid) {
            $details[] = ['order_lines_id' => $lid, 'material' => 'S235', 'thickness' => 2,
                'weight' => random_int(1, 50), 'created_at' => $now, 'updated_at' => $now];
        }
        $this->insertBulk('order_line_details', $details);
        return $ids;
    }

    private function seedTasks(array $orderLineIds, int $perLine, array $productIds): array
    {
        if ($perLine === 0) {
            return [];
        }
        $total = count($orderLineIds) * $perLine;
        $this->line("→ tasks ({$total}) — {$perLine}/ligne");
        $bar = $this->output->createProgressBar(count($orderLineIds));
        $buffer = [];
        foreach ($orderLineIds as $lid) {
            $d = $this->ts($this->someDate());
            for ($t = 1; $t <= $perLine; $t++) {
                $type = $t === $perLine ? 7 : 1; // dernière = sous-traitance (pour les achats)
                $buffer[] = [
                    'label' => 'Opération ' . $t, 'ordre' => $t,
                    'order_lines_id' => $lid, 'products_id' => $productIds[array_rand($productIds)],
                    'methods_services_id' => $this->ref['service'], 'methods_units_id' => $this->ref['unit'],
                    'status_id' => $this->ref['statusIds'][array_rand($this->ref['statusIds'])],
                    'type' => $type, 'qty' => random_int(1, 500),
                    'seting_time' => random_int(0, 60), 'unit_time' => random_int(1, 30) / 10,
                    'unit_cost' => random_int(5, 80), 'unit_price' => random_int(10, 120),
                    'not_recalculate' => 0, 'priority' => 2,
                    'created_at' => $d, 'updated_at' => $d,
                ];
            }
            if (count($buffer) >= 2000) {
                $this->insertBulk('tasks', $buffer);
                $buffer = [];
            }
            $bar->advance();
        }
        if ($buffer) {
            $this->insertBulk('tasks', $buffer);
        }
        $bar->finish();
        $this->newLine();
        // récupère tous les ids de tâches rattachées à des lignes de commande
        return $this->db->table('tasks')->whereNotNull('order_lines_id')->pluck('id')->all();
    }

    private function seedTaskActivities(array $taskIds, int $perTask): void
    {
        if ($perTask === 0 || empty($taskIds)) {
            return;
        }
        $total = count($taskIds) * $perTask;
        $this->line("→ task_activities ({$total}) — {$perTask}/tâche");
        $bar = $this->output->createProgressBar(count($taskIds));
        $buffer = [];
        foreach ($taskIds as $tid) {
            $base = $this->someDate();
            for ($a = 0; $a < $perTask; $a++) {
                // alterne start(1)/end(2) + quelques déclarations de qté (4)
                $type = $a % 2 === 0 ? 1 : 2;
                if ($a === $perTask - 1) {
                    $type = 4;
                }
                $ts = (clone $base)->addMinutes($a * random_int(5, 90));
                $buffer[] = [
                    'task_id' => $tid, 'user_id' => $this->ref['user'], 'type' => $type,
                    'timestamp' => $this->ts($ts),
                    'good_qt' => $type === 4 ? random_int(1, 100) : 0, 'bad_qt' => 0,
                    'created_at' => $this->ts($ts), 'updated_at' => $this->ts($ts),
                ];
            }
            if (count($buffer) >= 5000) {
                $this->insertBulk('task_activities', $buffer);
                $buffer = [];
            }
            $bar->advance();
        }
        if ($buffer) {
            $this->insertBulk('task_activities', $buffer);
        }
        $bar->finish();
        $this->newLine();
    }

    private function seedDeliveriesInvoicesAccounting(array $orderIds, array $orderLineIds, array $companyIds, array $byContact, array $byAddress): void
    {
        // ~2/3 des commandes livrées + facturées
        $subset = array_slice($orderIds, 0, (int) round(count($orderIds) * 0.66));
        $this->line('→ deliverys + delivery_lines + invoices + invoice_lines + accounting_entries (' . count($subset) . ' cmd)');

        // map order -> lignes (échantillon léger : 3 lignes/cmd livrées)
        $orderLineRows = $this->db->table('order_lines')->whereIn('orders_id', $subset)
            ->orderBy('orders_id')->get(['id', 'orders_id', 'qty', 'selling_price', 'companies_id' => 'orders_id']);
        $linesByOrder = [];
        foreach ($orderLineRows as $r) {
            $linesByOrder[$r->orders_id][] = $r;
        }
        $orderCompany = $this->db->table('orders')->whereIn('id', $subset)->pluck('companies_id', 'id')->all();

        $now = $this->ts(now());
        $vatRate = (float) $this->db->table('accounting_vats')->where('id', $this->ref['vat'])->value('rate');

        foreach (array_chunk($subset, 300) as $chunk) {
            $delHeaders = [];
            $invHeaders = [];
            $map = [];
            foreach ($chunk as $oid) {
                $cid = $orderCompany[$oid] ?? null;
                if (! $cid || ! isset($byContact[$cid])) {
                    continue;
                }
                $d = $this->ts($this->someDate());
                $delHeaders[] = ['uuid' => $this->uuid(), 'code' => $this->code('BL'), 'label' => 'BL cmd ' . $oid,
                    'companies_id' => $cid, 'companies_contacts_id' => $byContact[$cid], 'companies_addresses_id' => $byAddress[$cid],
                    'statu' => 2, 'invoice_status' => 1, 'user_id' => $this->ref['user'], 'order_id' => $oid,
                    'created_at' => $d, 'updated_at' => $d];
                $invHeaders[] = ['uuid' => $this->uuid(), 'code' => $this->code('FA'), 'label' => 'Facture cmd ' . $oid,
                    'companies_id' => $cid, 'companies_contacts_id' => $byContact[$cid], 'companies_addresses_id' => $byAddress[$cid],
                    'statu' => 2, 'invoice_type' => 1, 'accounting_status' => 1, 'user_id' => $this->ref['user'], 'order_id' => $oid,
                    'created_at' => $d, 'updated_at' => $d];
                $map[] = $oid;
            }
            $delIds = $this->insertReturningIds('deliverys', $delHeaders);
            $invIds = $this->insertReturningIds('invoices', $invHeaders);

            $delLines = [];
            $invLines = [];
            foreach ($map as $k => $oid) {
                $someLines = array_slice($linesByOrder[$oid] ?? [], 0, 3);
                $ordre = 1;
                foreach ($someLines as $ol) {
                    $delLines[] = ['deliverys_id' => $delIds[$k], 'order_line_id' => $ol->id, 'ordre' => $ordre,
                        'qty' => (int) max(1, $ol->qty), 'statu' => 1, 'invoice_status' => 1, 'created_at' => $now, 'updated_at' => $now];
                    $invLines[] = ['invoices_id' => $invIds[$k], 'order_line_id' => $ol->id, 'ordre' => $ordre,
                        'qty' => $ol->qty, 'unit_price' => $ol->selling_price, 'discount' => 0, 'vat_rate' => $vatRate,
                        'accounting_allocation_id' => $this->ref['allocation'], 'invoice_status' => 1, 'exported' => 0,
                        'created_at' => $now, 'updated_at' => $now];
                    $ordre++;
                }
            }
            $this->insertBulk('delivery_lines', $delLines);
            $invLineIds = $this->insertReturningIds('invoice_lines', $invLines);

            // écritures FEC : 3 lignes équilibrées par ligne de facture (411 / 701 / 44571)
            $entries = [];
            foreach ($invLines as $k => $il) {
                $ht = round($il['qty'] * $il['unit_price'], 2);
                $tva = round($ht * $vatRate / 100, 2);
                $ttc = round($ht + $tva, 2);
                $seq = ++$this->seq;
                $ilId = $invLineIds[$k] ?? null;
                $common = ['journal_code' => 'VENT', 'journal_label' => 'Journal des ventes', 'sequence_number' => $seq,
                    'accounting_date' => $now, 'justification_reference' => 'FA-' . $seq, 'justification_date' => $now,
                    'validation_date' => $now, 'exported' => 0, 'invoice_line_id' => $ilId,
                    'created_at' => $now, 'updated_at' => $now];
                $entries[] = $common + ['account_number' => '411000', 'account_label' => 'Clients', 'entry_label' => 'Vente',
                    'debit_amount' => $ttc, 'credit_amount' => 0];
                $entries[] = $common + ['account_number' => '701000', 'account_label' => 'Ventes', 'entry_label' => 'Vente',
                    'debit_amount' => 0, 'credit_amount' => $ht];
                if ($tva > 0) {
                    $entries[] = $common + ['account_number' => '445710', 'account_label' => 'TVA collectée', 'entry_label' => 'TVA',
                        'debit_amount' => 0, 'credit_amount' => $tva];
                }
            }
            $this->insertBulk('accounting_entries', $entries);
        }
    }

    private function seedPurchases(int $count, array $companyIds, array $byContact, array $byAddress, array $taskIds, array $slpIds): void
    {
        $supplierIds = array_values(array_filter($companyIds, fn ($cid) => isset($byContact[$cid])));
        if (empty($taskIds)) {
            $this->line('→ purchases ignorés (pas de tâches générées pour relier purchase_lines.tasks_id)');
            return;
        }
        $this->line("→ purchases ({$count}) + lignes + réceptions");
        $now = $this->ts(now());

        foreach (array_chunk(range(1, $count), 200) as $chunk) {
            $headers = [];
            $recHeaders = [];
            foreach ($chunk as $i) {
                $cid = $supplierIds[array_rand($supplierIds)];
                $d = $this->ts($this->someDate());
                $headers[] = ['code' => $this->code('ACH'), 'label' => 'Achat ' . $i,
                    'companies_id' => $cid, 'companies_contacts_id' => $byContact[$cid], 'companies_addresses_id' => $byAddress[$cid],
                    'statu' => random_int(1, 4), 'user_id' => $this->ref['user'], 'created_at' => $d, 'updated_at' => $d];
                $recHeaders[] = ['code' => $this->code('REC'), 'label' => 'Réception ' . $i,
                    'companies_id' => $cid, 'statu' => 2, 'user_id' => $this->ref['user'], 'reception_controlled' => 0,
                    'created_at' => $d, 'updated_at' => $d];
            }
            $purchaseIds = $this->insertReturningIds('purchases', $headers);
            $receiptIds = $this->insertReturningIds('purchase_receipts', $recHeaders);

            $pLines = [];
            $pLineMeta = [];
            foreach ($purchaseIds as $k => $pid) {
                $nb = random_int(1, 4);
                for ($o = 1; $o <= $nb; $o++) {
                    $qty = random_int(1, 100);
                    $price = random_int(5, 200);
                    $pLines[] = ['purchases_id' => $pid, 'tasks_id' => $taskIds[array_rand($taskIds)], 'ordre' => $o,
                        'label' => 'Ligne achat ' . $o, 'qty' => $qty, 'selling_price' => $price, 'discount' => 0,
                        'unit_price_after_discount' => $price, 'total_selling_price' => $price * $qty,
                        'receipt_qty' => $qty, 'invoiced_qty' => 0, 'methods_units_id' => $this->ref['unit'],
                        'accounting_vats_id' => $this->ref['vat'], 'created_at' => $now, 'updated_at' => $now];
                    $pLineMeta[] = ['receipt' => $receiptIds[$k], 'qty' => $qty, 'ordre' => $o];
                }
            }
            $pLineIds = $this->insertReturningIds('purchase_lines', $pLines);

            $recLines = [];
            foreach ($pLineIds as $k => $plid) {
                $m = $pLineMeta[$k];
                $recLines[] = ['purchase_receipt_id' => $m['receipt'], 'purchase_line_id' => $plid, 'ordre' => $m['ordre'],
                    'receipt_qty' => $m['qty'], 'stock_location_products_id' => $slpIds[array_rand($slpIds)],
                    'accepted_qty' => $m['qty'], 'rejected_qty' => 0, 'created_at' => $now, 'updated_at' => $now];
            }
            $this->insertBulk('purchase_receipt_lines', $recLines);
        }
    }

    // --------------------------------------------------------------- utilitaires

    private function truncateAll(): void
    {
        $this->warn('--fresh : vidage des tables loadtest');
        $tables = ['task_activities', 'tasks', 'order_line_details', 'order_lines', 'orders',
            'quote_line_details', 'quote_lines', 'quotes', 'delivery_lines', 'deliverys',
            'invoice_lines', 'invoices', 'accounting_entries', 'purchase_receipt_lines', 'purchase_receipts',
            'purchase_lines', 'purchases', 'stock_moves', 'stock_location_products',
            'companies_contacts', 'companies_addresses', 'companies', 'products'];
        $this->db->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            try {
                $this->db->table($t)->truncate();
            } catch (\Throwable $e) {
                // table absente : ignore
            }
        }
        $this->db->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function counts(): array
    {
        $tables = ['companies', 'products', 'stock_location_products', 'stock_moves', 'quotes', 'quote_lines',
            'orders', 'order_lines', 'tasks', 'task_activities', 'deliverys', 'invoices', 'invoice_lines',
            'accounting_entries', 'purchases', 'purchase_lines', 'purchase_receipt_lines'];
        $out = [];
        foreach ($tables as $t) {
            try {
                $out[] = [$t, number_format($this->db->table($t)->count(), 0, '.', ' ')];
            } catch (\Throwable $e) {
                $out[] = [$t, 'n/a'];
            }
        }
        return $out;
    }
}
