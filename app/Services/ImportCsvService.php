<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Services\Imports\SpreadsheetReader;

/**
 * Imports CSV / Excel : sociétés, articles, devis, commandes (assistant de
 * l'écran Imports/Exports et anciens formulaires « numéro de colonne ») et
 * lignes de devis / commande (onglet « Importation de lignes »).
 *
 * Une correspondance champ → n° de colonne (à partir de 0) décrit le fichier.
 * Chaque ligne est créée (ou, pour un article existant, mise à jour) ou rejetée
 * avec son numéro et la raison ; l'en-tête et les lignes vides ne sont ni l'un
 * ni l'autre. process() sait aussi simuler l'import sans rien écrire : c'est
 * l'aperçu de l'assistant.
 */
class ImportCsvService
{
    /** Types proposés par l'assistant de l'écran Imports/Exports. */
    public const WIZARD_TYPES = ['companies', 'products', 'quotes', 'orders'];

    /** Nombre maximal de rejets détaillés dans le message (le reste est compté). */
    private const MAX_LISTED_REJECTS = 50;

    private const MAX_FILE_SIZE_MB = 5;

    /** Champs numériques contrôlés avant l'enregistrement. */
    private const NUMERIC_FIELDS = [
        'purchased_price', 'selling_price', 'thickness', 'weight', 'x_size', 'y_size', 'z_size',
        'x_oversize', 'y_oversize', 'z_oversize', 'qty_eco_min', 'qty_eco_max', 'diameter',
        'diameter_oversize', 'section_size', 'qty', 'discount',
    ];

    private const DATE_FIELDS = ['validity_date', 'delivery_date'];

    private $numberOfLinesImported = 0;
    protected $orderService;
    protected SpreadsheetReader $reader;

    public function __construct(
        OrderService $orderService,
        ?SpreadsheetReader $reader = null,
                    ){
        $this->orderService = $orderService;
        $this->reader = $reader ?? new SpreadsheetReader();
    }

    // -------------------------------------------------------------------------
    // Fichier
    // -------------------------------------------------------------------------

    /**
     * Error message for an uploaded file (presence, size, extension), null when acceptable.
     */
    public function fileError($file): ?string
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return __('imports.no_file');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE_MB * 1024 * 1024) {
            return __('imports.too_large', ['size' => self::MAX_FILE_SIZE_MB]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, SpreadsheetReader::EXTENSIONS, true)) {
            return __('imports.bad_extension', ['extensions' => implode(', ', SpreadsheetReader::EXTENSIONS)]);
        }

        return null;
    }

    /**
     * Rows of a CSV, text or Excel file, indexed from 0 (empty line = []).
     */
    public function readFile(string $path, string $extension): array
    {
        return $this->reader->read($path, $extension);
    }

    // -------------------------------------------------------------------------
    // Champs et correspondance
    // -------------------------------------------------------------------------

    /**
     * Fields of an import type, in display order.
     *
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    public function fields(string $type): array
    {
        $fields = [];
        foreach (config("imports.types.$type", []) as $key => $field) {
            $fields[] = [
                'key'      => $key,
                'label'    => $this->fieldLabel($field),
                'required' => (bool) ($field['required'] ?? false),
            ];
        }

        return $fields;
    }

    private function fieldLabel(array $field): string
    {
        $label = $field['label'] ?? '';

        return str_contains($label, '.') ? __($label) : $label;
    }

    /**
     * Header name compared without case, accents nor punctuation.
     */
    public static function normalizeHeader(?string $value): string
    {
        $value = str_replace(['°', 'º', '’', "'"], ' ', mb_strtolower((string) $value));
        $value = preg_replace('/[^a-z0-9%]+/', ' ', Str::ascii($value));

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Suggested column of each field from the header names : exact name first,
     * then a header starting with a known name (« Épaisseur (mm) »). A column
     * is given to one field only.
     *
     * @param  array<int, string|null> $headers
     * @return array<string, int> field => column index
     */
    public function suggestMapping(string $type, array $headers): array
    {
        $normalized = array_map([self::class, 'normalizeHeader'], $headers);
        $candidates = [];
        foreach (config("imports.types.$type", []) as $key => $field) {
            $names = array_merge($field['synonyms'] ?? [], [$this->fieldLabel($field), str_replace('_', ' ', $key)]);
            $candidates[$key] = array_values(array_unique(array_filter(array_map([self::class, 'normalizeHeader'], $names))));
        }

        $mapping = [];
        $used = [];
        foreach ([false, true] as $prefix) {
            foreach ($candidates as $key => $names) {
                if (isset($mapping[$key])) {
                    continue;
                }
                foreach ($normalized as $index => $header) {
                    if ($header === '' || isset($used[$index])) {
                        continue;
                    }
                    foreach ($names as $name) {
                        if ($header === $name || ($prefix && str_starts_with($header, $name . ' '))) {
                            $mapping[$key] = $index;
                            $used[$index] = true;
                            continue 3;
                        }
                    }
                }
            }
        }

        return $mapping;
    }

    // -------------------------------------------------------------------------
    // Moteur
    // -------------------------------------------------------------------------

    /**
     * Imports (or simulates, $dryRun) the rows of a file.
     *
     * @param  array<string, int|string|null> $mapping field => column index
     * @param  array<string, mixed>           $options products : methods_services_id, methods_families_id,
     *                                                 methods_units_id, purchased, sold ; lines : parent_id
     * @return array{error: ?string, created: int, updated: int, rejected: int,
     *               rejects: array<int, array{line: int, reason: string}>,
     *               lines: array<int, array{status: string, reason: ?string}>}
     */
    public function process(string $type, array $rows, array $mapping, bool $header, array $options = [], bool $dryRun = false, int $previewLines = 0, ?string $filename = null): array
    {
        $result = ['error' => null, 'created' => 0, 'updated' => 0, 'rejected' => 0, 'rejects' => [], 'lines' => []];

        $definition = config("imports.types.$type");
        if (!$definition) {
            $result['error'] = __('imports.unknown_type');
            return $result;
        }

        $map = [];
        foreach (array_keys($definition) as $key) {
            $column = $mapping[$key] ?? null;
            $map[$key] = ($column === null || $column === '' || !is_numeric($column)) ? null : (int) $column;
        }

        $missing = array_filter(array_keys($definition), fn ($key) => ($definition[$key]['required'] ?? false) && $map[$key] === null);
        if ($missing) {
            $result['error'] = __('imports.unmapped', ['fields' => implode(', ', array_map(fn ($key) => $this->fieldLabel($definition[$key]), $missing))]);
            return $result;
        }

        $context = $this->context($type, $options, $filename);
        if (is_string($context)) {
            $result['error'] = $context;
            return $result;
        }

        $handler = 'row' . Str::studly($type);
        $dataLines = 0;

        foreach ($rows as $index => $data) {
            if ($data === []) {
                continue;
            }
            if ($header) {
                $header = false;
                continue;
            }
            $dataLines++;
            $line = $index + 1;

            try {
                $reason = $this->checkValues($data, $map, $definition);
                $status = $reason === null ? $this->{$handler}($data, $map, $context, $dryRun) : 'reject';
                if (is_array($status)) {
                    [$status, $reason] = $status;
                }
            } catch (\Throwable $e) {
                report($e);
                [$status, $reason] = ['reject', __('imports.reject.save_failed', ['message' => Str::limit($e->getMessage(), 120)])];
            }

            match ($status) {
                'create' => $result['created']++,
                'update' => $result['updated']++,
                default  => $result['rejected']++,
            };
            if ($status === 'reject') {
                $result['rejects'][] = ['line' => $line, 'reason' => $reason];
            }
            if ($dataLines <= $previewLines) {
                $result['lines'][$line] = ['status' => $status, 'reason' => $status === 'reject' ? $reason : null];
            }
        }

        if (!$dryRun) {
            $this->numberOfLinesImported = $result['created'] + $result['updated'];
        }

        return $result;
    }

    /**
     * Settings shared by every line of an import, or the reason why the import
     * cannot start.
     */
    private function context(string $type, array $options, ?string $filename): array|string
    {
        $context = ['user_id' => Auth::id(), 'filename' => $filename, 'seen' => [], 'options' => $options];

        if (in_array($type, ['quotes', 'orders'], true)) {
            $settings = [
                'payment_conditions' => AccountingPaymentConditions::getDefault(),
                'payment_methods'    => AccountingPaymentMethod::getDefault(),
                'deliveries'         => AccountingDelivery::getDefault(),
            ];
            foreach ($settings as $key => $setting) {
                if (is_null($setting)) {
                    return __('imports.no_defaults', ['setting' => str_replace('_', ' ', $key)]);
                }
            }
            $context['settings'] = $settings;
        }

        if ($type === 'products') {
            foreach (['methods_services_id', 'methods_families_id', 'methods_units_id'] as $key) {
                if (empty($options[$key])) {
                    return __('imports.product_options');
                }
            }
        }

        if (in_array($type, ['quote-lines', 'order-lines'], true)) {
            $unit = MethodsUnits::where('default', 1)->first();
            $vat  = AccountingVat::where('default', 1)->first();
            if (empty($unit->id) || empty($vat->id)) {
                return __('imports.no_line_defaults');
            }
            $context['unit_id']   = $unit->id;
            $context['vat_id']    = $vat->id;
            $context['parent_id'] = $options['parent_id'] ?? null;
            // Lignes ajoutées à la suite des lignes existantes.
            $context['ordre'] = $type === 'quote-lines'
                ? (int) QuoteLines::where('quotes_id', $context['parent_id'])->max('ordre')
                : (int) OrderLines::where('orders_id', $context['parent_id'])->max('ordre');
        }

        return $context;
    }

    /**
     * Required columns filled, numbers and dates readable.
     */
    private function checkValues(array $data, array $map, array $definition): ?string
    {
        $missing = [];
        foreach ($definition as $key => $field) {
            if (($field['required'] ?? false) && $this->value($data, $map, $key) === null) {
                $missing[] = $this->fieldLabel($field);
            }
        }
        if ($missing) {
            return __('imports.reject.required', ['fields' => implode(', ', $missing)]);
        }

        foreach (array_keys($map) as $key) {
            $value = $this->value($data, $map, $key);
            if ($value === null) {
                continue;
            }
            if (in_array($key, self::NUMERIC_FIELDS, true) && !is_numeric($this->parseDecimal($value))) {
                return __('imports.reject.not_a_number', ['field' => $this->fieldLabel($definition[$key]), 'value' => $value]);
            }
            if (in_array($key, self::DATE_FIELDS, true) && !$this->isDate($this->parseDate($value))) {
                return __('imports.reject.bad_date', ['field' => $this->fieldLabel($definition[$key]), 'value' => $value]);
            }
        }

        return null;
    }

    /**
     * A code already in the database, or earlier in the same file.
     */
    private function alreadySeen(array &$context, string $code, callable $existsInDatabase): bool
    {
        $key = mb_strtolower($code);
        if (isset($context['seen'][$key])) {
            return true;
        }
        $context['seen'][$key] = true;

        return $existsInDatabase($code);
    }

    // -------------------------------------------------------------------------
    // Lignes, par type : 'create' | 'update' | ['reject', raison]
    // -------------------------------------------------------------------------

    private function rowCompanies(array $data, array $map, array &$context, bool $dryRun): string|array
    {
        $code = $this->value($data, $map, 'code');
        if ($this->alreadySeen($context, $code, fn ($c) => Companies::where('code', $c)->exists())) {
            return ['reject', __('imports.reject.already_exists', ['code' => $code])];
        }

        if (!$dryRun) {
            Companies::create([
                'uuid'                => Str::uuid(),
                'code'                => $code,
                'client_type'         => 1,
                'label'               => $this->value($data, $map, 'label'),
                'website'             => $this->value($data, $map, 'website'),
                'fbsite'              => $this->value($data, $map, 'fbsite'),
                'twittersite'         => $this->value($data, $map, 'twittersite'),
                'lkdsite'             => $this->value($data, $map, 'lkdsite'),
                'siren'               => $this->value($data, $map, 'siren'),
                'naf_code'            => $this->value($data, $map, 'naf_code'),
                'intra_community_vat' => $this->value($data, $map, 'intra_community_vat'),
                'discount'            => $this->parseDecimal($this->value($data, $map, 'discount')),
                'user_id'             => $context['user_id'],
                'csv_file_name'       => $context['filename'],
            ]);
        }

        return 'create';
    }

    private function rowProducts(array $data, array $map, array &$context, bool $dryRun): string|array
    {
        $code = $this->value($data, $map, 'code');
        $status = $this->alreadySeen($context, $code, fn ($c) => Products::where('code', $c)->exists()) ? 'update' : 'create';

        if (!$dryRun) {
            $options = $context['options'];
            $decimal = fn (string $key) => $this->parseDecimal($this->value($data, $map, $key));
            $product = Products::firstOrNew(['code' => $code]);

            $product->fill([
                'label'               => $this->value($data, $map, 'label'),
                'ind'                 => $this->value($data, $map, 'ind'),
                'methods_services_id' => $options['methods_services_id'],
                'methods_families_id' => $options['methods_families_id'],
                'purchased'           => $options['purchased'] ?? 2,
                'purchased_price'     => $decimal('purchased_price'),
                'sold'                => $options['sold'] ?? 2,
                'selling_price'       => $decimal('selling_price'),
                'methods_units_id'    => $options['methods_units_id'],
                'material'            => $this->value($data, $map, 'material'),
                'thickness'           => $decimal('thickness'),
                'weight'              => $decimal('weight'),
                'x_size'              => $decimal('x_size'),
                'y_size'              => $decimal('y_size'),
                'z_size'              => $decimal('z_size'),
                'x_oversize'          => $decimal('x_oversize'),
                'y_oversize'          => $decimal('y_oversize'),
                'z_oversize'          => $decimal('z_oversize'),
                'comment'             => $this->value($data, $map, 'comment'),
                'qty_eco_min'         => $decimal('qty_eco_min'),
                'qty_eco_max'         => $decimal('qty_eco_max'),
                'diameter'            => $decimal('diameter'),
                'diameter_oversize'   => $decimal('diameter_oversize'),
                'section_size'        => $decimal('section_size'),
                'finishing'           => $this->value($data, $map, 'finishing'),
                'csv_file_name'       => $context['filename'],
            ]);

            $product->save();
        }

        return $status;
    }

    private function rowQuotes(array $data, array $map, array &$context, bool $dryRun): string|array
    {
        $code = $this->value($data, $map, 'code');
        if ($this->alreadySeen($context, $code, fn ($c) => Quotes::withTemplates()->where('code', $c)->exists())) {
            return ['reject', __('imports.reject.already_exists', ['code' => $code])];
        }

        $customer = $this->resolveCustomer($this->value($data, $map, 'companies_id'));
        if (is_string($customer)) {
            return ['reject', $customer];
        }
        [$company, $defaultAddress, $defaultContact] = $customer;

        if (!$dryRun) {
            $settings = $context['settings'];
            Quotes::create([
                'uuid'                             => Str::uuid(),
                'code'                             => $code,
                'label'                            => $this->value($data, $map, 'label'),
                'customer_reference'               => $this->value($data, $map, 'customer_reference'),
                'companies_id'                     => $company->id,
                'companies_contacts_id'            => $defaultContact->id,
                'companies_addresses_id'           => $defaultAddress->id,
                'validity_date'                    => $this->parseDate($this->value($data, $map, 'validity_date')),
                'user_id'                          => $context['user_id'],
                'accounting_payment_conditions_id' => $settings['payment_conditions']->id,
                'accounting_payment_methods_id'    => $settings['payment_methods']->id,
                'accounting_deliveries_id'         => $settings['deliveries']->id,
                'comment'                          => $this->value($data, $map, 'comment'),
                'csv_file_name'                    => $context['filename'],
            ]);
        }

        return 'create';
    }

    private function rowOrders(array $data, array $map, array &$context, bool $dryRun): string|array
    {
        $code = $this->value($data, $map, 'code');
        if ($this->alreadySeen($context, $code, fn ($c) => Orders::where('code', $c)->exists())) {
            return ['reject', __('imports.reject.already_exists', ['code' => $code])];
        }

        $customer = $this->resolveCustomer($this->value($data, $map, 'companies_id'));
        if (is_string($customer)) {
            return ['reject', $customer];
        }
        [$company, $defaultAddress, $defaultContact] = $customer;

        if (!$dryRun) {
            $settings = $context['settings'];
            $this->orderService->createOrder(
                $code,
                $this->value($data, $map, 'label'),
                $this->value($data, $map, 'customer_reference'),
                $company->id,
                $defaultContact->id,
                $defaultAddress->id,
                $this->parseDate($this->value($data, $map, 'validity_date')),
                1, // Ouverte (orders.statu n'accepte pas null)
                $context['user_id'],
                $settings['payment_conditions']->id,
                $settings['payment_methods']->id,
                $settings['deliveries']->id,
                $this->value($data, $map, 'comment'),
                1,
                null,
                $context['filename']
            );
        }

        return 'create';
    }

    private function rowQuoteLines(array $data, array $map, array &$context, bool $dryRun): string|array
    {
        if (!$dryRun) {
            $line = QuoteLines::create([
                'quotes_id'          => $context['parent_id'],
                'ordre'              => ++$context['ordre'],
                'code'               => $this->value($data, $map, 'code'),
                'label'              => $this->value($data, $map, 'label'),
                'qty'                => $this->parseDecimal($this->value($data, $map, 'qty')),
                'methods_units_id'   => $context['unit_id'],
                'selling_price'      => $this->parseDecimal($this->value($data, $map, 'selling_price')),
                'discount'           => $this->parseDecimal($this->value($data, $map, 'discount')) ?? 0,
                'accounting_vats_id' => $context['vat_id'],
                'delivery_date'      => $this->parseDate($this->value($data, $map, 'delivery_date')),
            ]);

            QuoteLineDetails::create(['quote_lines_id' => $line->id]);
        }

        return 'create';
    }

    private function rowOrderLines(array $data, array $map, array &$context, bool $dryRun): string|array
    {
        if (!$dryRun) {
            $qty = $this->parseDecimal($this->value($data, $map, 'qty'));

            $line = OrderLines::create([
                'orders_id'               => $context['parent_id'],
                'ordre'                   => ++$context['ordre'],
                'code'                    => $this->value($data, $map, 'code'),
                'ind'                     => $this->value($data, $map, 'ind'),
                'label'                   => $this->value($data, $map, 'label'),
                'qty'                     => $qty,
                'delivered_qty'           => 0,
                'delivered_remaining_qty' => $qty,
                'invoiced_qty'            => 0,
                'invoiced_remaining_qty'  => $qty,
                'methods_units_id'        => $context['unit_id'],
                'selling_price'           => $this->parseDecimal($this->value($data, $map, 'selling_price')),
                'discount'                => $this->parseDecimal($this->value($data, $map, 'discount')) ?? 0,
                'accounting_vats_id'      => $context['vat_id'],
                'delivery_date'           => $this->parseDate($this->value($data, $map, 'delivery_date')),
            ]);

            OrderLineDetails::create(['order_lines_id' => $line->id]);
        }

        return 'create';
    }

    // -------------------------------------------------------------------------
    // Valeurs
    // -------------------------------------------------------------------------

    /**
     * Value of a field for a line : trimmed, null when the field has no column,
     * the column is absent from the line or the cell is empty.
     */
    private function value(array $data, array $map, string $field): ?string
    {
        $column = $map[$field] ?? null;
        if ($column === null) {
            return null;
        }

        $value = $data[$column] ?? null;
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Company and its default address and contact, or the reason of the rejection.
     *
     * @return array{0: Companies, 1: CompaniesAddresses, 2: CompaniesContacts}|string
     */
    private function resolveCustomer(?string $companyCode): array|string
    {
        $company = $companyCode === null ? null : Companies::where('code', $companyCode)->first();
        if (!$company) {
            return __('imports.reject.company_unknown', ['code' => $companyCode]);
        }

        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $company->id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $company->id]);
        if (is_null($defaultAddress) || is_null($defaultContact)) {
            return __('imports.reject.company_default', ['code' => $company->code]);
        }

        return [$company, $defaultAddress, $defaultContact];
    }

    /** "1 234,50" → "1234.50" ; null reste null. */
    private function parseDecimal(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        return str_replace([',', ' ', "\u{00A0}", "\u{202F}"], ['.', '', '', ''], $value);
    }

    /**
     * Dates written the French way (25/06/2024, 25-06-2024, 25.06.2024) become
     * Y-m-d ; an ISO date or an unknown format is kept as it is.
     */
    private function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$#', $value, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->format('Y-m-d');
        }

        return $value;
    }

    private function isDate(?string $value): bool
    {
        if ($value === null || !preg_match('#^(\d{4})-(\d{2})-(\d{2})( \d{2}:\d{2}(:\d{2})?)?$#', $value, $m)) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /**
     * Get the number of lines created or updated by the last import.
     */
    public function getNumberOfLinesImported()
    {
        return $this->numberOfLinesImported;
    }

    // -------------------------------------------------------------------------
    // Messages
    // -------------------------------------------------------------------------

    /**
     * Rejections written as « Ligne n : raison », at most $limit.
     *
     * @return array<int, string>
     */
    public function rejectMessages(array $rejects, int $limit = self::MAX_LISTED_REJECTS): array
    {
        $messages = array_map(fn ($r) => __('imports.reject.line', ['line' => $r['line'], 'reason' => $r['reason']]), array_slice($rejects, 0, $limit));
        if (count($rejects) > $limit) {
            $messages[] = __('imports.more_rejects', ['count' => count($rejects) - $limit]);
        }

        return $messages;
    }

    /**
     * End message : « N créée(s), M rejetée(s) », with « U mise(s) à jour » when articles were updated.
     */
    public function resultMessage(array $result): string
    {
        return $result['updated'] > 0
            ? __('imports.result_with_updates', ['created' => $result['created'], 'updated' => $result['updated'], 'rejected' => $result['rejected']])
            : __('imports.result', ['created' => $result['created'], 'rejected' => $result['rejected']]);
    }

    // -------------------------------------------------------------------------
    // Formulaires « numéro de colonne » (anciennes routes, onglets des lignes)
    // -------------------------------------------------------------------------

    private function importFromRequest(string $type, Request $request, array $options = []): RedirectResponse
    {
        $file = $request->file('import_file');
        if ($error = $this->fileError($file)) {
            return redirect()->back()->withErrors($error);
        }

        try {
            $rows = $this->readFile($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->withErrors(__('imports.unreadable', ['message' => $e->getMessage()]));
        }

        $mapping = [];
        foreach (array_keys(config("imports.types.$type", [])) as $key) {
            $mapping[$key] = $request->input($key);
        }

        $result = $this->process($type, $rows, $mapping, !empty($request->input('header')), $options, false, 0, $file->getClientOriginalName());
        if ($result['error'] !== null) {
            return redirect()->back()->with('error', $result['error']);
        }

        $response = redirect()->back();
        $response = $result['created'] + $result['updated'] > 0
            ? $response->with('success', $this->resultMessage($result))
            : $response->with('error', __('imports.nothing_created', ['rejected' => $result['rejected']]));

        return $result['rejects'] ? $response->withErrors($this->rejectMessages($result['rejects'])) : $response;
    }

    public function importCompanies(Request $request): RedirectResponse
    {
        return $this->importFromRequest('companies', $request);
    }

    public function importQuotes(Request $request): RedirectResponse
    {
        return $this->importFromRequest('quotes', $request);
    }

    public function importOrders(Request $request): RedirectResponse
    {
        return $this->importFromRequest('orders', $request);
    }

    public function importProducts(Request $request): RedirectResponse
    {
        return $this->importFromRequest('products', $request, $request->only(['methods_services_id', 'methods_families_id', 'methods_units_id', 'purchased', 'sold']));
    }

    public function importQuoteLines($idQuote, Request $request): RedirectResponse
    {
        return $this->importFromRequest('quote-lines', $request, ['parent_id' => $idQuote]);
    }

    public function importOrderLines($idOrder, Request $request): RedirectResponse
    {
        return $this->importFromRequest('order-lines', $request, ['parent_id' => $idOrder]);
    }
}
