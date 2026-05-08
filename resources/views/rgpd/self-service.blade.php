@extends('adminlte::page')

@section('title', __('Mes données personnelles (RGPD)'))

@section('content_header')
    <h1>{{ __('Mes données personnelles (RGPD)') }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Article 15 / 20 — Droit d\'accès et portabilité') }}</h3>
            </div>
            <div class="card-body">
                <p>{{ __('Téléchargez l\'ensemble des données personnelles que nous détenons sur vous, au format JSON.') }}</p>
                <a href="{{ route('me.rgpd.export') }}" class="btn btn-primary">
                    <i class="fas fa-download mr-1"></i> {{ __('Télécharger mes données') }}
                </a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Article 17 — Droit à l\'effacement') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    {{ __('Votre demande sera transmise à un administrateur. L\'effacement effectif peut nécessiter une anonymisation si des pièces comptables (factures, commandes) sont liées à votre compte — la loi française impose une conservation de 10 ans pour ces documents.') }}
                </p>
                <form method="POST" action="{{ route('me.rgpd.erase') }}" onsubmit="return confirm('{{ __('Confirmer la demande d\'effacement ?') }}')">
                    @csrf
                    <div class="form-group">
                        <label for="reason">{{ __('Motif (facultatif)') }}</label>
                        <textarea name="reason" id="reason" rows="3" maxlength="1000" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-user-times mr-1"></i> {{ __('Demander l\'effacement') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
