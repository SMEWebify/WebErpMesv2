@extends('adminlte::page')

@section('title', 'Pré-commandes importées')

@section('content_header')
    <h1>Pré-commandes importées</h1>
@stop

@section('content')
    @if(session('success'))
        <x-adminlte-alert theme="success" title="Succès" dismissable>
            {{ session('success') }}
        </x-adminlte-alert>
    @endif

    @if($errors->any())
        <x-adminlte-alert theme="danger" title="Erreur" dismissable>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    <x-adminlte-card title="Dépôt des PDF" theme="info" maximizable>
        <form id="pre-orders-upload-form" method="POST" action="{{ route('pre-orders.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="pdfs[]" id="pre-orders-upload-input" class="d-none" accept="application/pdf" multiple>

            <div id="pre-orders-dropzone" class="pre-orders-dropzone text-center p-4">
                <i class="fas fa-file-pdf fa-2x mb-2 text-danger"></i>
                <p class="mb-2">Glissez-déposez vos fichiers PDF ici.</p>
                <p class="text-muted mb-3">ou</p>
                <button type="button" id="pre-orders-upload-trigger" class="btn btn-primary btn-sm">Sélectionner des PDF</button>
                <div id="pre-orders-file-list" class="mt-3 text-left small text-muted"></div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success btn-sm">Analyser</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card title="Pré-commandes" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>PDF source</th>
                        <th>Nb lignes</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preOrders as $preOrder)
                        <tr>
                            <td>
                                <a href="{{ route('pre-orders.pdf', $preOrder) }}" target="_blank" rel="noopener noreferrer">
                                    {{ $preOrder->source_pdf }}
                                </a>
                            </td>
                            <td>{{ $preOrder->lines_count }}</td>
                            <td>{{ $preOrder->formatted_total_price }}</td>
                            <td>
                                @if($preOrder->status === \App\Models\Workflow\PreOrder::STATUS_CONVERTED)
                                    <span class="badge badge-success">Convertie</span>
                                @else
                                    <span class="badge badge-warning">À traiter</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('pre-orders.show', $preOrder) }}" class="btn btn-xs btn-default text-primary">
                                    <i class="fa fa-lg fa-fw fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Aucune pré-commande importée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $preOrders->links() }}
        </div>
    </x-adminlte-card>

    @php
        $invoiceErrorRows = $invoiceReportRows->filter(fn (array $row) => $row['is_error'] ?? false)->values();
    @endphp

    <x-adminlte-card title="Log d'import" theme="secondary" maximizable>
        @if(!empty($invoiceReportReadError))
            <x-adminlte-alert theme="warning" title="Rapport indisponible" dismissable>
                Le fichier de rapport n'est pas disponible pour le moment.
            </x-adminlte-alert>
        @elseif($invoiceErrorRows->isEmpty())
            <p class="mb-0 text-muted">Aucune erreur d'import disponible pour aujourd'hui.</p>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fichier</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Articles extraits</th>
                            <th>Détails</th>
                            <th>Durée (s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoiceErrorRows as $reportRow)
                            <tr>
                                <td>{{ $reportRow['filename'] }}</td>
                                <td>{{ $reportRow['date'] }}</td>
                                <td>
                                    @if($reportRow['is_error'])
                                        <span class="badge badge-danger">Erreur</span>
                                    @elseif($reportRow['is_warning'])
                                        <span class="badge badge-warning">Alerte</span>
                                    @else
                                        <span class="badge badge-success">Succès</span>
                                    @endif
                                    <span class="ml-1">{{ $reportRow['status'] }}</span>
                                </td>
                                <td>{{ $reportRow['items_extracted'] }}</td>
                                <td>{{ $reportRow['error_details'] }}</td>
                                <td>{{ number_format((float) $reportRow['duration_sec'], 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-adminlte-card>
@stop

@section('css')
    <style>
        .pre-orders-dropzone {
            border: 2px dashed #adb5bd;
            border-radius: .5rem;
            background: #f8f9fa;
            transition: all .2s ease;
            cursor: pointer;
        }

        .pre-orders-dropzone.is-dragover {
            border-color: #007bff;
            background: #e9f3ff;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropzone = document.getElementById('pre-orders-dropzone');
            const input = document.getElementById('pre-orders-upload-input');
            const trigger = document.getElementById('pre-orders-upload-trigger');
            const fileList = document.getElementById('pre-orders-file-list');

            if (!dropzone || !input || !trigger || !fileList) {
                return;
            }

            const renderFileList = () => {
                if (!input.files.length) {
                    fileList.textContent = '';
                    return;
                }

                const items = Array.from(input.files).map((file) => `• ${file.name}`);
                fileList.innerHTML = items.join('<br>');
            };

            trigger.addEventListener('click', function () {
                input.click();
            });

            dropzone.addEventListener('click', function (event) {
                if (event.target !== trigger) {
                    input.click();
                }
            });

            input.addEventListener('change', renderFileList);

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                });
            });

            dropzone.addEventListener('drop', function (event) {
                const files = event.dataTransfer?.files;
                if (!files || !files.length) {
                    return;
                }

                const dataTransfer = new DataTransfer();
                Array.from(files)
                    .filter((file) => file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))
                    .forEach((file) => dataTransfer.items.add(file));

                input.files = dataTransfer.files;
                renderFileList();
            });
        });
    </script>
@stop
