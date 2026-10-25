{{-- Rapport premier article : trois formulaires (pièce, matière / procédés, cotes).
     Données préparées par App\Services\Inspection\FaiReport::build(). Format neutre
     par défaut, intitulés AS9102 si config('inspection.report_format') = as9102. --}}
@php
    $t = static fn (string $key, array $replace = []) => __('inspection.' . $key, $replace);
    $as9102 = ($report['format'] ?? 'neutral') === 'as9102';
    $f1 = $report['form1'];
    $box = static fn (int $n, string $label) => $as9102 ? $n . '. ' . $label : $label;
    $val = static fn ($v) => filled($v) ? e($v) : '&nbsp;';
    $date = static fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d/m/Y') : '';
    $num = static fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, ',', ''), '0'), ',');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $t('report_title') }} — {{ $f1['fair_identifier'] }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 9px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 14px 0 4px; padding: 3px 6px; background: #e9ecef; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #888; padding: 3px 5px; vertical-align: top; }
        th { background: #f1f3f5; text-align: left; font-weight: bold; }
        .label { display: block; font-size: 7px; color: #555; text-transform: uppercase; }
        .head td { border: none; padding: 0; }
        .ok { color: #146c43; font-weight: bold; }
        .nok { color: #b02a37; font-weight: bold; }
        .muted { color: #777; }
        .page-break { page-break-before: always; }
        .draft { position: fixed; top: 40%; left: 20%; font-size: 90px; color: rgba(200, 0, 0, .08); transform: rotate(-20deg); }
    </style>
</head>
<body>
@if($draft)<div class="draft">{{ $t('report_draft_watermark') }}</div>@endif

<table class="head">
    <tr>
        <td>
            <h1>{{ $as9102 ? 'SAE AS9102 — ' . $t('report_title_as9102') : $t('report_title') }}</h1>
            <span class="muted">{{ $factory?->name }} — {{ $f1['fair_identifier'] }}</span>
        </td>
        <td style="text-align:right">
            <span class="muted">{{ $t('report_generated_at', ['date' => now()->format('d/m/Y H:i')]) }}</span>
        </td>
    </tr>
</table>

{{-- Formulaire 1 : identification de la pièce --}}
<h2>{{ $as9102 ? $t('form1_title_as9102') : $t('form1_title') }}</h2>
<table>
    <tr>
        <td><span class="label">{{ $box(1, $t('f_part_number')) }}</span>{!! $val($f1['part_number']) !!}</td>
        <td><span class="label">{{ $box(2, $t('f_part_name')) }}</span>{!! $val($f1['part_name']) !!}</td>
        <td><span class="label">{{ $box(3, $t('f_serial_number')) }}</span>{!! $val($f1['serial_number']) !!}</td>
        <td><span class="label">{{ $box(4, $t('f_fair_identifier')) }}</span>{!! $val($f1['fair_identifier']) !!}</td>
    </tr>
    <tr>
        <td><span class="label">{{ $box(5, $t('f_part_revision')) }}</span>{!! $val($f1['part_revision']) !!}</td>
        <td><span class="label">{{ $box(6, $t('f_drawing_number')) }}</span>{!! $val($f1['drawing_number']) !!}</td>
        <td><span class="label">{{ $box(7, $t('f_drawing_revision')) }}</span>{!! $val($f1['drawing_revision']) !!}</td>
        <td><span class="label">{{ $box(8, $t('f_additional_changes')) }}</span>{!! $val($f1['additional_changes']) !!}</td>
    </tr>
    <tr>
        <td><span class="label">{{ $box(9, $t('f_process_reference')) }}</span>{!! $val($f1['process_reference']) !!}</td>
        <td><span class="label">{{ $box(10, $t('f_organization')) }}</span>{!! $val($f1['organization']) !!}</td>
        <td><span class="label">{{ $box(11, $t('f_supplier_code')) }}</span>{!! $val($f1['supplier_code']) !!}</td>
        <td><span class="label">{{ $box(12, $t('f_po_number')) }}</span>{!! $val($f1['po_number']) !!}</td>
    </tr>
    <tr>
        <td><span class="label">{{ $box(13, $t('f_detail_assembly')) }}</span>{{ $t('detail_assembly_' . ($f1['detail_assembly'] ?? 'detail')) }}</td>
        <td><span class="label">{{ $box(14, $t('f_fai_type')) }}</span>{{ $t('fai_type_' . $f1['fai_type']) }}</td>
        <td colspan="2"><span class="label">{{ $t('f_partial_reason') }}</span>{!! $val($f1['partial_reason']) !!}</td>
    </tr>
    <tr>
        <td colspan="2"><span class="label">{{ $t('customer') }}</span>{!! $val($f1['customer']) !!}</td>
        <td colspan="2"><span class="label">{{ $box(19, $t('f_has_nc')) }}</span>
            <span class="{{ $f1['has_nc'] ? 'nok' : 'ok' }}">{{ $f1['has_nc'] ? $t('yes') : $t('no') }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="3"><span class="label">{{ $box(20, $t('f_verified_by')) }}</span>{!! $val($f1['verified_by']) !!}</td>
        <td><span class="label">{{ $box(21, $t('f_date')) }}</span>{{ $date($f1['verified_at']) }}</td>
    </tr>
    <tr>
        <td colspan="3"><span class="label">{{ $box(22, $t('f_approved_by')) }}</span>{!! $val($f1['approved_by']) !!}</td>
        <td><span class="label">{{ $box(23, $t('f_date')) }}</span>{{ $date($f1['approved_at']) }}</td>
    </tr>
    <tr>
        <td colspan="3"><span class="label">{{ $box(24, $t('f_customer_approval')) }}</span>{!! $val($f1['customer_approval']) !!}</td>
        <td><span class="label">{{ $box(25, $t('f_date')) }}</span>{{ $date($f1['customer_approved_at']) }}</td>
    </tr>
    <tr>
        <td colspan="4"><span class="label">{{ $box(26, $t('f_comments')) }}</span>{!! nl2br(e($f1['comment'] ?? '')) ?: '&nbsp;' !!}</td>
    </tr>
</table>

{{-- Formulaire 2 : matière, procédés, certificats --}}
<h2>{{ $as9102 ? $t('form2_title_as9102') : $t('form2_title') }}</h2>
<table>
    <thead>
        <tr>
            <th style="width:16%">{{ $t('f2_kind') }}</th>
            <th>{{ $t('f2_label') }}</th>
            <th style="width:22%">{{ $t('f2_supplier') }}</th>
            <th style="width:18%">{{ $t('f2_reference') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($report['form2'] as $row)
            <tr>
                <td>{{ $t('f2_kind_' . $row['kind']) }}</td>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['supplier'] }}</td>
                <td>{{ $row['reference'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">{{ $t('f2_empty') }}</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Formulaire 3 : cotes --}}
<h2 class="page-break">{{ $as9102 ? $t('form3_title_as9102') : $t('form3_title') }}</h2>
<table>
    <thead>
        <tr>
            <th style="width:4%">{{ $as9102 ? '5. ' : '' }}N°</th>
            <th style="width:7%">{{ $as9102 ? '6. ' : '' }}{{ $t('f3_location') }}</th>
            <th style="width:8%">{{ $as9102 ? '7. ' : '' }}{{ $t('f3_designator') }}</th>
            <th style="width:22%">{{ $as9102 ? '8. ' : '' }}{{ $t('f3_requirement') }}</th>
            <th>{{ $as9102 ? '9. ' : '' }}{{ $t('f3_results') }}</th>
            <th style="width:7%">{{ $t('f3_conformity') }}</th>
            <th style="width:10%">{{ $as9102 ? '10. ' : '' }}{{ $t('f3_tools') }}</th>
            <th style="width:8%">{{ $as9102 ? '13. ' : '' }}{{ $t('f3_nc') }}</th>
            <th style="width:12%">{{ $as9102 ? '14. ' : '' }}{{ $t('f_comments') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($report['form3'] as $row)
            <tr>
                <td><strong>{{ $row['number'] }}</strong></td>
                <td>{{ $row['location'] }}</td>
                <td>{{ $row['designator'] }}</td>
                <td>{{ $row['requirement_print'] }}</td>
                <td>
                    @forelse($row['results'] as $r)
                        <span class="{{ $r['result'] === 'nok' ? 'nok' : '' }}">{{ $t('part') }} {{ $r['part'] }} : {{ $r['value'] !== null ? $num($r['value']) : ($r['result'] === 'ok' ? $t('mark_ok') : $t('mark_nok')) }}</span>@if(! $loop->last)<br>@endif
                    @empty
                        <span class="muted">{{ $t('not_measured') }}</span>
                    @endforelse
                </td>
                <td class="{{ $row['result'] }}">{{ $row['result'] ? ($row['result'] === 'ok' ? $t('conforming') : $t('non_conforming')) : '' }}</td>
                <td>{{ $row['tools'] }}</td>
                <td>{{ $row['nc'] }}</td>
                <td>{{ $row['comments'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
