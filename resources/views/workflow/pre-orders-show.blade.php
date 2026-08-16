@extends('adminlte::page')

@section('title', 'Pré-commande #'.$preOrder->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Pré-commande #{{ $preOrder->id }}</h1>
        <a href="{{ route('pre-orders.index') }}" class="btn btn-default btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Retour à l'index
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-adminlte-card title="Lignes importées" theme="warning" theme-mode="outline" maximizable>
                @php
                    $hasPendingMatching = $preOrder->lines->contains(fn ($line) => $line->suggested_product_id && ! $line->linked_product_id);
                @endphp

                <div class="d-flex mb-3">
                    <form method="POST" action="{{ route('pre-orders.matching', $preOrder) }}" class="mr-2">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-link mr-1"></i> Matching article
                        </button>
                    </form>

                    <form method="POST" action="{{ route('pre-orders.accept-all-matching', $preOrder) }}" class="mr-2">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" @disabled(! $hasPendingMatching)>
                            <i class="fas fa-check-double mr-1"></i> Valider toutes les lignes
                        </button>
                    </form>

                    <a href="{{ route('pre-orders.export-zero-price', $preOrder) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-file-csv mr-1"></i> Exporter articles à 0 €
                    </a>
                </div>

                <div class="table-responsive p-0">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Référence</th>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>PU</th>
                                <th>Total</th>
                                <th>Article matché</th>
                                <th>PU article</th>
                                <th>Validation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preOrder->lines as $line)
                                <tr>
                                    <td>{{ $line->row_index }}</td>
                                    <td>{{ $line->reference }}</td>
                                    <td>{{ $line->product }}</td>
                                    <td>{{ format_qty($line->quantity) }}</td>
                                    <td>{{ $line->formatted_selling_price }}</td>
                                    <td>{{ $line->formatted_total_price }}</td>
                                    <td>
                                        @if($line->linkedProduct)
                                            <span class="badge badge-success">{{ $line->linkedProduct->code }} - {{ $line->linkedProduct->label }}</span>
                                        @elseif($line->suggestedProduct)
                                            <span class="badge badge-warning">{{ $line->suggestedProduct->code }} - {{ $line->suggestedProduct->label }}</span>
                                        @else
                                            <span class="text-muted">Aucun</span>
                                        @endif
                                    </td>
                                    <td>{{ $line->formatted_matching_unit_price ?? '-' }}</td>
                                    <td>
                                        @if($line->linked_product_id)
                                            <span class="badge badge-success">Validé</span>
                                        @elseif($line->suggested_product_id)
                                            <form method="POST" action="{{ route('pre-orders.accept-matching', [$preOrder, $line]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-success">Accepter</button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-adminlte-card>

            <x-adminlte-card title="PDF source" theme="info" maximizable class="mt-3">
                @if($sourcePdfUrl)
                    <div class="mb-2">
                        <a href="{{ $sourcePdfUrl }}" target="_blank" rel="noopener" class="btn btn-default btn-sm">
                            <i class="fas fa-external-link-alt mr-1"></i> Ouvrir dans un nouvel onglet
                        </a>
                    </div>
                    <iframe
                        src="{{ $sourcePdfUrl }}#toolbar=1&navpanes=0"
                        title="PDF source de la pré-commande"
                        width="100%"
                        height="700"
                        style="border: 1px solid #dee2e6; border-radius: .25rem;"
                    ></iframe>
                @else
                    <p class="text-muted mb-0">PDF source introuvable pour cette pré-commande.</p>
                @endif
            </x-adminlte-card>
        </div>
        <div class="col-lg-4">
            <x-adminlte-card title="Transformation en commande" theme="lightblue" theme-mode="outline">
                @if($preOrder->status === \App\Models\Workflow\PreOrder::STATUS_CONVERTED)
                    <p class="mb-2"><span class="badge badge-success">Déjà convertie</span></p>
                    <p>Commande créée :
                        <a href="{{ route('orders.show', ['id' => $preOrder->converted_order_id]) }}">#{{ $preOrder->converted_order_id }}</a>
                    </p>
                @else
                    @php($duplicateWarning = session('duplicate_warning'))

                    @if($duplicateWarning)
                        <div class="alert alert-warning">
                            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Doublon possible</h6>
                            <p class="mb-1">
                                Une commande existe déjà :
                                <a href="{{ route('orders.show', ['id' => $duplicateWarning['id']]) }}" target="_blank" rel="noopener">#{{ $duplicateWarning['code'] }}</a>
                                - libellé « {{ $duplicateWarning['label'] }} »@if($duplicateWarning['customer_reference']), réf. client « {{ $duplicateWarning['customer_reference'] }} »@endif.
                            </p>
                            <p class="mb-0">Ce PDF a peut-être déjà été traité. Confirmer la création quand même ?</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('pre-orders.convert', $preOrder) }}">
                        @csrf
                        <input type="hidden" name="confirm_duplicate" value="{{ $duplicateWarning ? '1' : '0' }}">

                        <x-adminlte-input name="code" label="Code commande" value="{{ old('code', $generatedOrderCode) }}" required />
                        <x-adminlte-input name="label" label="Libellé" value="{{ old('label', $defaultLabel) }}" required />
                        <x-adminlte-input name="customer_reference" label="Référence client" value="{{ old('customer_reference') }}" />

                        <x-adminlte-select name="companies_id" label="Client *" id="companies_id">
                            <option value="">-- Sélectionner --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('companies_id') == $company->id)>{{ $company->label }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-select name="user_id" label="Responsable" required>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-input name="validity_date" type="date" label="Date de livraison" value="{{ old('validity_date') }}" />

                        <x-adminlte-select name="accounting_payment_conditions_id" label="Condition de paiement">
                            <option value="">--</option>
                            @foreach($paymentConditions as $item)
                                <option value="{{ $item->id }}" @selected(old('accounting_payment_conditions_id', optional($defaultPaymentCondition)->id) == $item->id)>{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-select name="accounting_payment_methods_id" label="Mode de paiement">
                            <option value="">--</option>
                            @foreach($paymentMethods as $item)
                                <option value="{{ $item->id }}" @selected(old('accounting_payment_methods_id', optional($defaultPaymentMethod)->id) == $item->id)>{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-select name="accounting_deliveries_id" label="Mode de livraison">
                            <option value="">--</option>
                            @foreach($deliveries as $item)
                                <option value="{{ $item->id }}" @selected(old('accounting_deliveries_id', optional($defaultDelivery)->id) == $item->id)>{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-select name="methods_units_id" label="Unité ligne" required>
                            @foreach($units as $item)
                                <option value="{{ $item->id }}" @selected(old('methods_units_id', optional($defaultUnit)->id) == $item->id)>{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-select name="accounting_vats_id" label="TVA ligne" required>
                            @foreach($vats as $item)
                                <option value="{{ $item->id }}" @selected(old('accounting_vats_id', optional($defaultVat)->id) == $item->id)>{{ $item->code }} - {{ $item->rate }}</option>
                            @endforeach
                        </x-adminlte-select>

                        <x-adminlte-input name="delivery_date" type="date" label="Date livraison des lignes" value="{{ old('delivery_date') }}" />
                        <x-adminlte-input name="discount" type="number" step="0.001" min="0" label="Remise (%)" value="{{ old('discount', 0) }}" />

                        <x-adminlte-select name="type" label="Type commande" required>
                            <option value="1" @selected(old('type') == 1)>Client</option>
                            <option value="2" @selected(old('type') == 2)>Interne</option>
                        </x-adminlte-select>

                        <x-adminlte-textarea name="comment" label="Commentaire" rows=3>{{ old('comment') }}</x-adminlte-textarea>

                        @if($duplicateWarning)
                            <button type="submit" class="btn btn-warning btn-block">Créer quand même la commande</button>
                            <a href="{{ route('pre-orders.show', $preOrder) }}" class="btn btn-default btn-block">Annuler</a>
                        @else
                            <button type="submit" class="btn btn-success btn-block">Créer la commande</button>
                        @endif
                    </form>
                @endif
            </x-adminlte-card>
        </div>
    </div>
@stop
