{{-- Fiche suiveuse (ex-instruction de fabrication) — même structure et même rendu
     que la fiche suiveuse Nest2Prod : page de garde commande, puis une fiche par
     ligne article. Données préparées par App\Services\Documents\ProductionSheetBuilder.
     Lots consommés (coulées) : imprimés seulement s'il y en a. Les @if / @endif qui les
     entourent sont collés à <td> / </td> ou en colonne 0, pour qu'une fiche sans lot garde
     exactement son rendu (tests/Fixtures/production-sheet, à ne jamais régénérer). --}}
@php
    $t = static fn (string $key, array $replace = []) => __('production_sheet.' . $key, $replace);
    $emptyValue = $t('empty_value');
    $customerDisplay = filled($customerName) ? $customerName : $emptyValue;
    $formattedDate = $generatedAt?->format('d/m/Y H:i') ?? '';
    $sheetsCount = $sheets->count();

    $formatNumber = static function ($value, int $decimals = 2) use ($emptyValue): string {
        if ($value === null || $value === '' || (float) $value == 0.0) {
            return $emptyValue;
        }

        $formatted = number_format((float) $value, $decimals, ',', ' ');

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    };

    $formatDate = static function ($value, string $format = 'd/m/Y') use ($emptyValue): string {
        return $value ? \Illuminate\Support\Carbon::parse($value)->format($format) : $emptyValue;
    };

    $formatMinutes = static function ($minutes) use ($emptyValue): string {
        if ($minutes === null || $minutes === '' || (float) $minutes <= 0) {
            return $emptyValue;
        }

        $minutes = (int) round((float) $minutes);

        return $minutes >= 60
            ? sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60)
            : sprintf('%d min', $minutes);
    };

    $display = static fn ($value) => (is_string($value) ? trim($value) : $value) ?: $emptyValue;

    $checkbox = '&#9744;';

    $orderQrSrc ??= null;
    $earliestDue = $sheets->pluck('due_date')->filter()->sort()->first();
    $orderMaterials = $sheets
        ->map(fn ($sheet) => trim(trim((string) $sheet['material']) . ($sheet['thickness'] ? ' ' . $formatNumber($sheet['thickness']) . ' mm' : '')))
        ->filter()
        ->unique()
        ->values();
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $t('document_title', ['ref' => $orderCode]) }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #0f172a;
            margin: 0;
        }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #94a3b8;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #e2e8f0;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .section { margin-top: 6px; }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background: #0f172a;
            color: #ffffff;
            padding: 3px 6px;
        }
        .label {
            background: #f1f5f9;
            color: #334155;
            font-weight: bold;
            width: 17%;
        }
        .header-title { font-size: 18px; font-weight: bold; letter-spacing: 0.05em; }
        .header-of { font-size: 22px; font-weight: bold; margin-top: 4px; }
        .header-meta { font-size: 10px; color: #334155; margin-top: 4px; line-height: 1.5; }
        .qr { width: 80px; height: 80px; }
        .part-image { max-width: 150px; max-height: 120px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .ops td { height: 18px; vertical-align: middle; }
        .ops .seq { width: 5%; text-align: center; font-weight: bold; }
        .write-line td { height: 18px; vertical-align: middle; }
        .nowrap { white-space: nowrap; }
        .ops td.barcode-cell { padding: 2px 6px; text-align: center; }
        .task-barcode { display: block; margin: 0 auto; width: 100%; height: 22px; }
        .big-qty { font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>
{{-- Page de garde : récapitulatif de la commande --}}
<div class="sheet order-cover">
    <table>
        <tr>
            <td style="width: 78%; border-right: none;">
                <div class="header-title">{{ $t('cover.title') }}</div>
                <div class="header-of">{{ $t('fields.order_ref') }} {{ $orderCode }}</div>
                <div class="header-meta">
                    <strong>{{ $t('fields.customer') }} :</strong> {{ $customerDisplay }}
                    @if (filled($customerReference))
                        &nbsp;·&nbsp; <strong>{{ $t('fields.customer_reference') }} :</strong> {{ $customerReference }}
                    @endif
                    <br>
                    <strong>{{ $t('fields.generated_at') }} :</strong> {{ $formattedDate }}
                </div>
            </td>
            <td style="width: 22%; border-left: none;" class="center">
                @if ($orderQrSrc)
                    <img src="{{ $orderQrSrc }}" alt="" class="qr">
                    <div class="muted">{{ $t('cover.scan_hint') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">{{ $t('cover.sections.summary') }}</div>
        <table>
            <tr>
                <td class="label">{{ $t('cover.lines_count') }}</td>
                <td class="big-qty">{{ $sheetsCount }}</td>
                <td class="label">{{ $t('cover.total_required') }}</td>
                <td class="big-qty">{{ number_format($totalRequired, 0, ',', ' ') }}</td>
                <td class="label">{{ $t('cover.total_delivered') }}</td>
                <td>{{ number_format($totalDelivered, 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="label">{{ $t('cover.earliest_due') }}</td>
                <td class="big-qty">{{ $formatDate($earliestDue) }}</td>
                <td class="label">{{ $t('fields.material') }}</td>
                <td colspan="3">{{ $orderMaterials->isNotEmpty() ? $orderMaterials->implode(', ') : $emptyValue }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ $t('cover.sections.lines') }}</div>
        <table>
            <tr>
                <th style="width: 6%;" class="center">{{ $t('cover.sheet') }}</th>
                <th style="width: 7%;">{{ $t('fields.line_ref') }}</th>
                <th style="width: 18%;">{{ $t('fields.article_ref') }}</th>
                <th>{{ $t('fields.label') }}</th>
                <th style="width: 15%;">{{ $t('fields.material') }}</th>
                <th style="width: 8%;" class="right">{{ $t('cover.qty') }}</th>
                <th style="width: 10%;">{{ $t('fields.due_date') }}</th>
                <th style="width: 5%;" class="center">{!! $checkbox !!}</th>
            </tr>
            @foreach ($sheets as $index => $sheet)
                <tr class="write-line">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $display($sheet['ordre']) }}</td>
                    <td><strong>{{ $display($sheet['code']) }}</strong></td>
                    <td>{{ \Illuminate\Support\Str::limit((string) $display($sheet['label']), 60) }}</td>
                    <td>
                        {{ $display($sheet['material']) }}
                        @if ($sheet['thickness']) — {{ $formatNumber($sheet['thickness']) }} mm @endif
                    </td>
                    <td class="right">{{ $formatNumber($sheet['qty']) }}</td>
                    <td>{{ $formatDate($sheet['due_date']) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ $t('cover.sections.shipping') }}</div>
        <table>
            <tr class="write-line">
                <td class="label">{{ $t('cover.prepared_by') }}</td>
                <td style="width: 23%;"></td>
                <td class="label" style="width: 8%;">{{ $t('release.date') }}</td>
                <td style="width: 17%;"></td>
                <td class="label" style="width: 8%;">{{ $t('ops.visa') }}</td>
                <td style="width: 17%;"></td>
            </tr>
            <tr class="write-line">
                <td class="label">{{ $t('cover.delivery_note') }}</td>
                <td></td>
                <td class="label">{{ $t('release.date') }}</td>
                <td></td>
                <td class="label">{{ $t('ops.visa') }}</td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ $t('sections.observations') }}</div>
        <table>
            <tr><td style="height: 70px;"></td></tr>
        </table>
    </div>
</div>

@foreach ($sheets as $index => $sheet)
    @php
        $operations = collect($sheet['operations']);
        $components = collect($sheet['components']);
        $dimensions = collect([$sheet['x_size'], $sheet['y_size'], $sheet['z_size']])
            ->filter(fn ($v) => (float) $v > 0)
            ->map(fn ($v) => $formatNumber($v, 1));
        $totalPlanned = $operations->sum(fn ($op) => (float) ($op['planned_time_min'] ?? 0));
        $hasBarcode = $operations->contains(fn ($op) => ! empty($op['barcode_src']));
        $unit = filled($sheet['unit']) ? ' ' . $sheet['unit'] : '';
    @endphp
    <div class="sheet">
        {{-- En-tête : identification de la ligne + QR commande --}}
        <table>
            <tr>
                <td style="width: 78%; border-right: none;">
                    <div class="header-title">{{ $t('title') }}</div>
                    <div class="header-of">{{ $display($sheet['code']) }}</div>
                    <div class="header-meta">
                        <strong>{{ $t('fields.order_ref') }} :</strong> {{ $orderCode }}
                        @if (filled($sheet['ordre'])) &nbsp;·&nbsp; <strong>{{ $t('fields.line_ref') }} :</strong> {{ $sheet['ordre'] }} @endif
                        <br>
                        <strong>{{ $t('fields.customer') }} :</strong> {{ $customerDisplay }}
                        @if (filled($customerReference)) ({{ $customerReference }}) @endif
                    </div>
                </td>
                <td style="width: 22%; border-left: none;" class="center">
                    @if ($orderQrSrc)
                        <img src="{{ $orderQrSrc }}" alt="" class="qr">
                    @endif
                    <div class="muted">{{ $t('fields.sheet_index', ['current' => $index + 1, 'total' => $sheetsCount]) }}</div>
                </td>
            </tr>
        </table>

        {{-- Pièce --}}
        <div class="section">
            <div class="section-title">{{ $t('sections.part') }}</div>
            <table>
                <tr>
                    <td class="label">{{ $t('fields.article_ref') }}</td>
                    <td>{{ $display($sheet['product_code'] ?: $sheet['code']) }}</td>
                    <td class="label">{{ $t('fields.revision') }}</td>
                    <td>{{ $display($sheet['revision']) }}</td>
                    <td rowspan="{{ empty($sheet['equipment_tags']) ? 6 : 7 }}" class="center" style="width: 24%; vertical-align: middle;">
                        @if ($sheet['image_src'])
                            <img src="{{ $sheet['image_src'] }}" alt="" class="part-image">
                        @else
                            <span class="muted">{{ $t('fields.no_image') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">{{ $t('fields.label') }}</td>
                    <td colspan="3">{{ $display($sheet['label']) }}</td>
                </tr>
                @if (!empty($sheet['equipment_tags']))
                <tr>
                    <td class="label">{{ $t('fields.equipment_tags') }}</td>
                    <td colspan="3"><strong>{{ implode(', ', $sheet['equipment_tags']) }}</strong></td>
                </tr>
                @endif
                <tr>
                    <td class="label">{{ $t('fields.material') }}</td>
                    <td>{{ $display($sheet['material']) }}</td>
                    <td class="label">{{ $t('fields.thickness') }}</td>
                    <td>{{ $sheet['thickness'] ? $formatNumber($sheet['thickness']) . ' mm' : $emptyValue }}</td>
                </tr>
                <tr>
                    <td class="label">{{ $t('fields.dimensions') }}</td>
                    <td>{{ $dimensions->count() >= 2 ? $dimensions->implode(' × ') . ' mm' : $emptyValue }}</td>
                    <td class="label">{{ $t('fields.weight') }}</td>
                    <td>{{ $sheet['weight'] ? $formatNumber($sheet['weight'], 3) . ' kg' : $emptyValue }}</td>
                </tr>
                <tr>
                    <td class="label">{{ $t('fields.bend_count') }}</td>
                    <td>{{ $sheet['bend_count'] !== null ? $sheet['bend_count'] : $emptyValue }}</td>
                    <td class="label">{{ $t('fields.finish') }}</td>
                    <td>{{ $display($sheet['finishing']) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ $t('fields.cad_file') }}</td>
                    <td colspan="3">{{ $display($sheet['cad_file']) }}</td>
                </tr>
            </table>
        </div>

        {{-- Lancement --}}
        <div class="section">
            <div class="section-title">{{ $t('sections.launch') }}</div>
            <table>
                <tr>
                    <td class="label">{{ $t('fields.required_qty') }}</td>
                    <td class="big-qty">{{ $formatNumber($sheet['qty']) }}{{ $unit }}</td>
                    <td class="label">{{ $t('fields.due_date') }}</td>
                    <td class="big-qty">{{ $formatDate($sheet['due_date']) }}</td>
                    <td class="label">{{ $t('fields.internal_delay') }}</td>
                    <td>{{ $formatDate($sheet['internal_delay']) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ $t('fields.planned_start') }}</td>
                    <td>{{ $formatDate($sheet['planned_start'], 'd/m/Y H:i') }}</td>
                    <td class="label">{{ $t('fields.planned_end') }}</td>
                    <td>{{ $formatDate($sheet['planned_end'], 'd/m/Y H:i') }}</td>
                    <td class="label">{{ $t('fields.generated_at') }}</td>
                    <td>{{ $formattedDate }}</td>
                </tr>
            </table>
        </div>

        {{-- Matière & nomenclature --}}
        <div class="section">
            <div class="section-title">{{ $t('sections.material') }}</div>
            @if ($components->isNotEmpty())
            <table>
                    <tr>
                        <th class="center" style="width: 5%;">{{ $t('ops.seq') }}</th>
                        <th style="width: 22%;">{{ $t('bom.component') }}</th>
                        <th>{{ $t('bom.label') }}</th>
                        <th style="width: 10%;" class="right">{{ $t('bom.qty') }}</th>
                        <th style="width: 15%;">{{ $t('bom.location') }}</th>
                        <th style="width: 15%;">{{ $t('bom.lot') }}</th>
                    </tr>
                    @foreach ($components as $component)
                        <tr class="write-line">
                            <td class="center">{{ $display($component['ordre']) }}</td>
                            <td>{{ $display($component['component']) }}</td>
                            <td>{{ $display($component['label']) }}</td>
                            <td class="right">{{ $formatNumber($component['qty'], 3) }}</td>
                            <td></td>
                            <td>@if (! empty($component['batches']))
                                @foreach ($component['batches'] as $batch)
                                    <div class="batch"><strong>{{ $display($batch['heat_number']) }}</strong> <span class="muted">({{ $batch['code'] }}@if (filled($batch['certificate_type'])) · {{ $batch['certificate_type'] }}@endif) × {{ $formatNumber($batch['qty'], 3) }}</span></div>
                                @endforeach
@endif</td>
                        </tr>
                    @endforeach
            </table>
            @endif
            <table>
                <tr class="write-line">
                    <td class="label">{{ $t('fields.material_lot') }}</td>
                    <td style="width: 33%;"></td>
                    <td class="label">{{ $t('fields.certificate') }}</td>
                    <td style="width: 33%;"></td>
                </tr>
            </table>
        </div>

        {{-- Gamme de fabrication / pointage --}}
        <div class="section">
            <div class="section-title">
                {{ $t('sections.routing') }}
                @if ($totalPlanned > 0)
                    <span style="font-weight: normal; text-transform: none;">— {{ $t('fields.total_planned', ['time' => $formatMinutes($totalPlanned)]) }}</span>
                @endif
            </div>
            <table class="ops">
                <tr>
                    <th class="center">{{ $t('ops.seq') }}</th>
                    <th style="width: 14%;">{{ $t('ops.operation') }}</th>
                    <th style="width: 14%;">{{ $t('ops.workcenter') }}</th>
                    <th style="width: 8%;">{{ $t('ops.planned_time') }}</th>
                    <th style="width: 10%;">{{ $t('ops.date') }}</th>
                    <th style="width: {{ $hasBarcode ? 9 : 13 }}%;">{{ $t('ops.operator') }}</th>
                    <th style="width: 8%;">{{ $t('ops.good_qty') }}</th>
                    <th style="width: 8%;">{{ $t('ops.scrap_qty') }}</th>
                    <th style="width: 7%;">{{ $t('ops.time_spent') }}</th>
                    <th style="width: {{ $hasBarcode ? 7 : 13 }}%;">{{ $t('ops.visa') }}</th>
                    @if ($hasBarcode)
                        <th class="center" style="width: 14%;">{{ $t('ops.declare') }}</th>
                    @endif
                </tr>
                @foreach ($operations as $operation)
                    <tr>
                        <td class="seq">{{ $display($operation['ordre']) }}</td>
                        <td>
                            <strong>{{ $display($operation['label']) }}</strong>
                            @if (filled($operation['service']))
                                <div class="muted" style="font-size: 8px;">{{ $operation['service'] }}</div>
                            @endif
                        </td>
                        <td>{{ $display($operation['workcenter']) }}</td>
                        <td>{{ $formatMinutes($operation['planned_time_min']) }}</td>
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                        @if ($hasBarcode)
                            <td class="barcode-cell">
                                @if ($operation['barcode_src'])
                                    <img src="{{ $operation['barcode_src'] }}" alt="" class="task-barcode">
                                @endif
                            </td>
                        @endif
                    </tr>
@if (! empty($operation['batches']))
                    <tr class="op-batches">
                        <td class="seq"></td>
                        <td colspan="{{ $hasBarcode ? 10 : 9 }}">
                            <strong>{{ $t('batches.consumed') }} :</strong>
                            @foreach ($operation['batches'] as $batch)
                                <div class="batch">
                                    {{ $t('batches.code') }} <strong>{{ $display($batch['code']) }}</strong>
                                    · {{ $t('batches.heat_number') }} <strong>{{ $display($batch['heat_number']) }}</strong>
                                    · {{ $t('batches.supplier_batch') }} {{ $display($batch['supplier_batch_code']) }}
                                    · {{ $t('batches.certificate') }} {{ $display($batch['certificate_type']) }}
                                    · {{ $t('batches.qty') }} {{ $formatNumber($batch['qty'], 3) }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
@endif
                @endforeach
                @for ($i = 0; $i < ($hasBarcode ? 1 : 2); $i++)
                    <tr>
                        <td class="seq"></td>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        @if ($hasBarcode)<td></td>@endif
                    </tr>
                @endfor
            </table>
        </div>

        {{-- Contrôles qualité --}}
        <div class="section">
            <div class="section-title">{{ $t('sections.quality') }}</div>
            <table>
                <tr>
                    <th style="width: 20%;">{{ $t('quality.check') }}</th>
                    <th style="width: 32%;">{{ $t('quality.result') }}</th>
                    <th style="width: 14%;">{{ $t('ops.date') }}</th>
                    <th style="width: 18%;">{{ $t('ops.operator') }}</th>
                    <th style="width: 16%;">{{ $t('ops.visa') }}</th>
                </tr>
                @foreach (['first_part', 'in_process', 'final'] as $checkKey)
                    <tr class="write-line">
                        <td>{{ $t('quality.' . $checkKey) }}</td>
                        <td class="nowrap">{!! $checkbox !!} {{ $t('quality.ok') }} &nbsp;&nbsp; {!! $checkbox !!} {{ $t('quality.nok') }}</td>
                        <td></td><td></td><td></td>
                    </tr>
                @endforeach
            </table>
        </div>

        {{-- Observations / non-conformités --}}
        <div class="section">
            <div class="section-title">{{ $t('sections.observations') }}</div>
            <table>
                @if (filled($sheet['notes']))
                    <tr>
                        <td><strong>{{ $t('fields.notes') }} :</strong> {{ $sheet['notes'] }}</td>
                    </tr>
                @endif
                <tr class="write-line"><td>{!! $checkbox !!} {{ $t('fields.non_conformity') }} &nbsp; {{ $t('fields.nc_number') }} : ______________</td></tr>
            </table>
        </div>

        {{-- Libération --}}
        <div class="section">
            <div class="section-title">{{ $t('sections.release') }}</div>
            <table>
                <tr class="write-line">
                    <td class="label" style="width: 14%;">{{ $t('release.good_qty') }}</td>
                    <td style="width: 11%;"></td>
                    <td class="label" style="width: 14%;">{{ $t('release.scrap_qty') }}</td>
                    <td style="width: 11%;"></td>
                    <td class="label" style="width: 8%;">{{ $t('release.date') }}</td>
                    <td style="width: 17%;"></td>
                    <td class="label" style="width: 8%;">{{ $t('ops.visa') }}</td>
                    <td style="width: 17%;"></td>
                </tr>
            </table>
        </div>
    </div>
@endforeach
</body>
</html>
