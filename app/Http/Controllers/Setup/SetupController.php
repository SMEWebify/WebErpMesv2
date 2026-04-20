<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\Factory;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Methods\MethodsUnits;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupController extends Controller
{
    /**
     * Permissions par défaut avec leurs libellés affichés dans le wizard.
     */
    private const DEFAULT_PERMISSIONS = [
        'companies-menu'             => 'CRM — Sociétés',
        'leads-menu'                 => 'CRM — Prospects',
        'opportunities-menu'         => 'CRM — Opportunités',
        'quotes-menu'                => 'Commercial — Devis',
        'orders-menu'                => 'Commercial — Commandes',
        'scheduling-menu'            => 'Production — Planification',
        'deliverys-menu'             => 'Commercial — Livraisons',
        'invoices-menu'              => 'Comptabilité — Factures',
        'products-menu'              => 'Produits — Catalogue',
        'stock-lot-serial-management'=> 'Produits — Lots & Séries',
        'purchases-menu'             => 'Achats',
        'quality-menu'               => 'Qualité',
        'settings-time-menu'         => 'Temps — Paramètres',
        'methods-menu'               => 'Méthodes',
        'accounting-menu'            => 'Comptabilité',
        'human-resources-menu'       => 'Ressources humaines',
        'documents-menu'             => 'Documents',
        'reports-menu'               => 'Rapports',
        'spreadsheet-menu'           => 'Tableur',
        'osh-menu'                   => 'Santé & Sécurité (SST)',
        'your-company-menu'          => 'Administration — Société',
        'asset_manager'              => 'Gestionnaire d\'actifs',
        'asset-menu'                 => 'Menu Actifs',
    ];

    public function index()
    {
        $this->ensureDefaultPermissionsExist();

        $factory  = Factory::first();
        $vat      = AccountingVat::where('default', 1)->first() ?? AccountingVat::first();
        $pc       = AccountingPaymentConditions::where('default', 1)->first() ?? AccountingPaymentConditions::first();
        $pm       = AccountingPaymentMethod::where('default', 1)->first() ?? AccountingPaymentMethod::first();
        $del      = AccountingDelivery::where('default', 1)->first() ?? AccountingDelivery::first();
        $unit     = MethodsUnits::where('default', 1)->first() ?? MethodsUnits::first();
        $roles    = Role::all(['id', 'name'])->toArray();
        $userRole = auth()->user()->roles()->first();
        $budget   = EstimatedBudgets::where('year', now()->year)->first();

        $permissions = Permission::all(['id', 'name'])->map(fn ($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'label' => self::DEFAULT_PERMISSIONS[$p->name] ?? $p->name,
        ])->values()->toArray();

        $userPermissions = $userRole
            ? $userRole->permissions->pluck('name')->toArray()
            : array_column($permissions, 'name');

        $initial = [
            'firstStep' => $this->firstIncompleteStep(),
            'company'   => $factory ? [
                'name'     => $factory->name         ?? '',
                'address'  => $factory->address      ?? '',
                'city'     => $factory->city         ?? '',
                'zipcode'  => $factory->zipcode      ?? '',
                'phone'    => $factory->phone_number ?? '',
                'mail'     => $factory->mail         ?? '',
                'siren'    => $factory->siren        ?? '',
                'vat_num'  => $factory->vat_num      ?? '',
                'logo_url' => $factory->picture ? asset('images/factory/' . $factory->picture) : null,
            ] : [],
            'vat' => $vat ? [
                'code'  => $vat->code,
                'label' => $vat->label,
                'rate'  => (string) $vat->rate,
            ] : [],
            'paymentCondition' => $pc ? [
                'code'      => $pc->code,
                'label'     => $pc->label,
                'months'    => $pc->number_of_month,
                'days'      => $pc->number_of_day,
                'month_end' => $pc->month_end,
            ] : [],
            'paymentMethod' => $pm ? [
                'code'  => $pm->code,
                'label' => $pm->label,
            ] : [],
            'delivery' => $del ? [
                'code'  => $del->code,
                'label' => $del->label,
            ] : [],
            'unit' => $unit ? [
                'code'  => $unit->code,
                'label' => $unit->label,
                'type'  => $unit->type,
            ] : [],
            'roles'               => $roles,
            'userRoleId'          => $userRole?->id,
            'availablePermissions'=> $permissions,
            'userPermissions'     => $userPermissions,
            'estimatedBudget'     => $budget ? [
                'year'     => $budget->year,
                'amount1'  => $budget->amount1,  'amount2'  => $budget->amount2,
                'amount3'  => $budget->amount3,  'amount4'  => $budget->amount4,
                'amount5'  => $budget->amount5,  'amount6'  => $budget->amount6,
                'amount7'  => $budget->amount7,  'amount8'  => $budget->amount8,
                'amount9'  => $budget->amount9,  'amount10' => $budget->amount10,
                'amount11' => $budget->amount11, 'amount12' => $budget->amount12,
            ] : null,
            'currentYear'  => now()->year,
            'dashboardUrl' => route('dashboard'),
        ];

        $endpoints = [
            'step1' => route('setup.company'),
            'step2' => route('setup.vat'),
            'step3' => route('setup.payment-condition'),
            'step4' => route('setup.payment-method'),
            'step5' => route('setup.delivery'),
            'step6' => route('setup.unit'),
            'step7' => route('setup.role'),
            'step8' => route('setup.estimated-budget'),
        ];

        return view('setup.index', compact('initial', 'endpoints'));
    }

    public function saveCompany(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|min:2|max:255',
            'address' => 'nullable|string|max:255',
            'city'    => 'nullable|string|max:255',
            'zipcode' => 'nullable|string|max:20',
            'phone'   => 'nullable|string|max:50',
            'mail'    => 'nullable|email|max:255',
            'siren'   => 'nullable|string|max:20',
            'vat_num' => 'nullable|string|max:50',
            'logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $update = [
            'name'         => $data['name'],
            'address'      => $data['address'] ?? null,
            'city'         => $data['city']    ?? null,
            'zipcode'      => $data['zipcode'] ?? null,
            'phone_number' => $data['phone']   ?? null,
            'mail'         => $data['mail']    ?? null,
            'siren'        => $data['siren']   ?? null,
            'vat_num'      => $data['vat_num'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $dir = public_path('images/factory');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file     = $request->file('logo');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move($dir, $filename);

            $existing = Factory::first();
            if ($existing?->picture && file_exists($dir . '/' . $existing->picture)) {
                @unlink($dir . '/' . $existing->picture);
            }

            $update['picture'] = $filename;
        }

        Factory::updateOrCreate(['id' => 1], $update);

        return response()->json(['ok' => true]);
    }

    public function saveVat(Request $request)
    {
        $data = $request->validate([
            'code'  => 'required|string|max:20',
            'label' => 'required|string|max:255',
            'rate'  => 'required|numeric|min:0|max:100',
        ]);

        AccountingVat::firstOrCreate(
            ['code' => $data['code']],
            ['label' => $data['label'], 'rate' => $data['rate'], 'default' => 1]
        );

        return response()->json(['ok' => true]);
    }

    public function savePaymentCondition(Request $request)
    {
        $data = $request->validate([
            'code'      => 'required|string|max:20',
            'label'     => 'required|string|max:255',
            'months'    => 'required|integer|min:0',
            'days'      => 'required|integer|min:0',
            'month_end' => 'required|integer|min:0|max:1',
        ]);

        AccountingPaymentConditions::firstOrCreate(
            ['code' => $data['code']],
            [
                'label'           => $data['label'],
                'number_of_month' => $data['months'],
                'number_of_day'   => $data['days'],
                'month_end'       => $data['month_end'],
                'default'         => 1,
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function savePaymentMethod(Request $request)
    {
        $data = $request->validate([
            'code'  => 'required|string|max:20',
            'label' => 'required|string|max:255',
        ]);

        AccountingPaymentMethod::firstOrCreate(
            ['code' => $data['code']],
            ['label' => $data['label'], 'default' => 1]
        );

        return response()->json(['ok' => true]);
    }

    public function saveDelivery(Request $request)
    {
        $data = $request->validate([
            'code'  => 'required|string|max:20',
            'label' => 'required|string|max:255',
        ]);

        AccountingDelivery::firstOrCreate(
            ['code' => $data['code']],
            ['label' => $data['label'], 'default' => 1]
        );

        return response()->json(['ok' => true]);
    }

    public function saveUnit(Request $request)
    {
        $data = $request->validate([
            'code'  => 'required|string|max:20',
            'label' => 'required|string|max:255',
            'type'  => 'required|integer|min:1|max:5',
        ]);

        MethodsUnits::firstOrCreate(
            ['code' => $data['code']],
            ['label' => $data['label'], 'type' => $data['type'], 'default' => 1]
        );

        return response()->json(['ok' => true]);
    }

    public function saveRole(Request $request)
    {
        $data = $request->validate([
            'role_id'     => 'nullable|integer|exists:roles,id',
            'role_name'   => 'required_without:role_id|nullable|string|min:2|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $user = auth()->user();

        if (!empty($data['role_id'])) {
            $role = Role::findById($data['role_id']);
        } else {
            $role = Role::firstOrCreate(['name' => $data['role_name']]);
        }

        // Filtre sur les permissions qui existent réellement en base
        if (!empty($data['permissions'])) {
            $existing = Permission::whereIn('name', $data['permissions'])->pluck('name')->toArray();
            $role->syncPermissions($existing);
        }

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        return response()->json(['ok' => true]);
    }

    public function saveEstimatedBudget(Request $request)
    {
        $data = $request->validate([
            'year'     => 'required|integer|min:2000|max:2100',
            'amount1'  => 'required|numeric|min:0',
            'amount2'  => 'required|numeric|min:0',
            'amount3'  => 'required|numeric|min:0',
            'amount4'  => 'required|numeric|min:0',
            'amount5'  => 'required|numeric|min:0',
            'amount6'  => 'required|numeric|min:0',
            'amount7'  => 'required|numeric|min:0',
            'amount8'  => 'required|numeric|min:0',
            'amount9'  => 'required|numeric|min:0',
            'amount10' => 'required|numeric|min:0',
            'amount11' => 'required|numeric|min:0',
            'amount12' => 'required|numeric|min:0',
        ]);

        EstimatedBudgets::updateOrCreate(
            ['year' => $data['year']],
            $data
        );

        return response()->json(['ok' => true]);
    }

    private function firstIncompleteStep(): int
    {
        if (!Factory::exists()) return 1;
        if (!AccountingVat::exists()) return 2;
        if (!AccountingPaymentConditions::exists()) return 3;
        if (!AccountingPaymentMethod::exists()) return 4;
        if (!AccountingDelivery::exists()) return 5;
        if (!MethodsUnits::exists()) return 6;
        if (!Role::exists() || auth()->user()->roles()->count() === 0) return 7;
        if (!EstimatedBudgets::where('year', now()->year)->exists()) return 8;
        return 1; // tout est fait, le middleware aurait dû rediriger
    }

    private function ensureDefaultPermissionsExist(): void
    {
        if (Permission::exists()) {
            return; // seeder déjà passé ou permissions déjà créées, on ne touche à rien
        }

        foreach (array_keys(self::DEFAULT_PERMISSIONS) as $name) {
            Permission::create(['name' => $name]);
        }
    }
}
