@extends('adminlte::page')

@section('title', 'Factur-X ' . $invoice->code)

@section('content_header')
    <h1>Factur-X {{ $invoice->code }}</h1>
@stop

@section('content')
<div class="card card-outline card-danger">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exclamation-triangle text-danger mr-1"></i>
            {{ $title }}
        </h3>
    </div>
    <div class="card-body">
        @if(! empty($items))
            <ul class="mb-0">
                @foreach($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="card-footer">
        @if($invoice->companies_id)
            <a href="{{ route('companies.show', ['id' => $invoice->companies_id]) }}" class="btn btn-sm btn-primary">
                <i class="fas fa-building mr-1"></i> Ouvrir la fiche client
            </a>
        @endif
        <a href="{{ route('invoices.show', ['id' => $invoice->id]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-invoice mr-1"></i> Retour à la facture
        </a>
    </div>
</div>
@stop
