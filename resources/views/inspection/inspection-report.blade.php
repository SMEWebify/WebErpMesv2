<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport d'inspection - {{ $project->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin-bottom: 0; }
        h2 { font-size: 16px; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f1f1f1; }
        .muted { color: #666; }
        .badge-ok { color: #0a7d2c; font-weight: bold; }
        .badge-nok { color: #b00020; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rapport d'inspection {{ $project->code }}</h1>
    <p class="muted">Généré le {{ now()->format('d/m/Y H:i') }}</p>

    <h2>Informations projet</h2>
    <table>
        <tr>
            <th>Projet</th>
            <td>{{ $project->title }}</td>
        </tr>
        <tr>
            <th>Client</th>
            <td>{{ optional($project->Company)->label }}</td>
        </tr>
        <tr>
            <th>Commande</th>
            <td>{{ $project->orders_id ?? '-' }}</td>
        </tr>
        <tr>
            <th>Quantité prévue</th>
            <td>{{ $project->quantity_planned ?? '-' }}</td>
        </tr>
        <tr>
            <th>Statut</th>
            <td>{{ $project->status }}</td>
        </tr>
    </table>

    <h2>Points de contrôle</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Libellé</th>
                <th>Nominal</th>
                <th>Tolérance</th>
                <th>Unité</th>
                <th>Plan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->ControlPoints as $point)
                <tr>
                    <td>{{ $point->number }}</td>
                    <td>{{ $point->label }}</td>
                    <td>{{ $point->nominal_value ?? '-' }}</td>
                    <td>
                        @if($point->tol_min !== null || $point->tol_max !== null)
                            {{ $point->tol_min ?? '-' }} / {{ $point->tol_max ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $point->unit ?? '-' }}</td>
                    <td>
                        @if($point->plan_page || $point->plan_ref)
                            {{ $point->plan_page ? 'P. ' . $point->plan_page : '' }} {{ $point->plan_ref }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Mesures</h2>
    <table>
        <thead>
            <tr>
                <th>Session</th>
                <th>Point</th>
                <th>Série</th>
                <th>Valeur</th>
                <th>Résultat</th>
                <th>Écart</th>
                <th>Opérateur</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->MeasureSessions as $session)
                @foreach($session->Measures as $measure)
                    <tr>
                        <td>{{ $session->session_code }}</td>
                        <td>{{ optional($measure->ControlPoint)->number }} - {{ optional($measure->ControlPoint)->label }}</td>
                        <td>{{ $measure->serial_number ?? '-' }}</td>
                        <td>{{ $measure->measured_value ?? '-' }}</td>
                        <td>
                            @if($measure->result === 'ok')
                                <span class="badge-ok">OK</span>
                            @elseif($measure->result === 'nok')
                                <span class="badge-nok">NOK</span>
                            @else
                                NA
                            @endif
                        </td>
                        <td>{{ $measure->deviation ?? '-' }}</td>
                        <td>{{ optional($measure->MeasuredBy)->name ?? '-' }}</td>
                        <td>{{ optional($measure->measured_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <h2>Non-conformités</h2>
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Description</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($project->NonConformities as $nc)
                <tr>
                    <td>{{ $nc->title }}</td>
                    <td>{{ $nc->description ?? '-' }}</td>
                    <td>{{ $nc->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Aucune non-conformité.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
