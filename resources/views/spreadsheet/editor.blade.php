@extends('adminlte::page')

@section('title', 'Éditeur de tableur')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $spreadsheet->name }}</h1>
        <div class="d-flex align-items-center">
            <button
                type="button"
                class="btn btn-outline-info btn-sm mr-3"
                data-toggle="modal"
                data-target="#spreadsheet-help-modal"
            >
                Aide formules
            </button>
            <span id="save-status" class="text-muted">Prêt</span>
        </div>
    </div>
@stop

@section('content')
    <div id="univer-container" style="height: calc(100vh - 60px);"></div>

    <div class="modal fade" id="spreadsheet-help-modal" tabindex="-1" role="dialog" aria-labelledby="spreadsheet-help-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="spreadsheet-help-modal-label">Aide des formules tableur</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>
                        Vous pouvez utiliser les fonctions standards du tableur (ex: <code>=SUM(A1:A10)</code>,
                        <code>=IF(A1&gt;0;"OK";"KO")</code>) ainsi que les formules métier WEM ci-dessous.
                    </p>

                    <h6 class="mt-3">Commandes WEM disponibles</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Formule</th>
                                    <th>Description</th>
                                    <th>Exemple</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>WEM_STOCK(reference)</code></td>
                                    <td>Retourne le stock actuel d'une référence article.</td>
                                    <td><code>=WEM_STOCK("TL-AC-2000x1000-EP1")</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_COMMANDES_EN_COURS()</code></td>
                                    <td>Retourne le nombre de commandes ouvertes.</td>
                                    <td><code>=WEM_COMMANDES_EN_COURS()</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_COMMANDES_RETARD()</code></td>
                                    <td>Retourne le nombre de commandes en retard.</td>
                                    <td><code>=WEM_COMMANDES_RETARD()</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_LISTE_COMMANDES_RETARD()</code></td>
                                    <td>Retourne la liste des références de commandes en retard (texte).</td>
                                    <td><code>=WEM_LISTE_COMMANDES_RETARD()</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_LISTE_COMMANDES([status])</code></td>
                                    <td>Retourne la liste des références de commandes (status: <code>all</code>, <code>open</code>, <code>closed</code>).</td>
                                    <td><code>=WEM_LISTE_COMMANDES("open")</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_CA_COMMANDES_OUVERTES()</code></td>
                                    <td>Retourne le CA HT total des commandes ouvertes.</td>
                                    <td><code>=WEM_CA_COMMANDES_OUVERTES()</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_CA_MOIS(mois)</code></td>
                                    <td>Retourne le chiffre d'affaires HT du mois (format <code>YYYY-MM</code>).</td>
                                    <td><code>=WEM_CA_MOIS("2026-03")</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_DELAI_MOYEN()</code></td>
                                    <td>Retourne le délai moyen de production en jours.</td>
                                    <td><code>=WEM_DELAI_MOYEN()</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mt-4">Variables pratiques WEM</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Variable</th>
                                    <th>Description</th>
                                    <th>Exemple</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>WEM_MOIS_COURANT()</code></td>
                                    <td>Retourne le mois courant au format <code>YYYY-MM</code>.</td>
                                    <td><code>=WEM_CA_MOIS(WEM_MOIS_COURANT())</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_AUJOURDHUI()</code></td>
                                    <td>Retourne la date du jour au format <code>YYYY-MM-DD</code>.</td>
                                    <td><code>=WEM_AUJOURDHUI()</code></td>
                                </tr>
                                <tr>
                                    <td><code>WEM_ANNEE_COURANTE()</code></td>
                                    <td>Retourne l'année en cours (format numérique).</td>
                                    <td><code>=WEM_ANNEE_COURANTE()</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <small class="text-muted">
                        Astuce&nbsp;: après avoir saisi une formule, appuyez sur Entrée pour calculer la cellule.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.WEM_SPREADSHEET = {
            id: {{ $spreadsheet->id }},
            name: @json($spreadsheet->name),
            saveUrl: "{{ route('spreadsheet.save', $spreadsheet) }}",
            dataApiBase: "/api/spreadsheet/data",
            csrfToken: "{{ csrf_token() }}",
            sheets: @json($spreadsheet->sheets->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'data' => $s->data]))
        };
    </script>
    <script src="{{ mix('js/spreadsheet.js') }}"></script>
@stop
