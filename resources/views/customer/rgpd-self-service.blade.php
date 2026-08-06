@extends('customer.layouts.app')

@section('title', __('Mes données personnelles (RGPD)'))

@section('content')
<div class="row">
    <div class="col-md-8">
        <h2 class="mb-4">{{ __('Mes données personnelles (RGPD)') }}</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">{{ __('Article 15 / 20 - Droit d\'accès et portabilité') }}</h5>
                <p class="text-muted">{{ __('Téléchargez l\'ensemble des données que nous détenons sur vous (identité, commandes, factures, livraisons), au format JSON.') }}</p>
                <a href="{{ route('customer.rgpd.export') }}" class="btn btn-primary">
                    {{ __('Télécharger mes données') }}
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">{{ __('Article 17 - Droit à l\'effacement') }}</h5>
                <p class="text-muted">
                    {{ __('Votre demande sera transmise à un administrateur. L\'effacement peut nécessiter une anonymisation si des pièces comptables (factures, commandes) sont liées à votre compte - la loi française impose une conservation de 10 ans pour ces documents.') }}
                </p>
                <form method="POST" action="{{ route('customer.rgpd.erase') }}" onsubmit="return confirm('{{ __('Confirmer la demande d\'effacement ?') }}')">
                    @csrf
                    <div class="mb-3">
                        <label for="reason" class="form-label">{{ __('Motif (facultatif)') }}</label>
                        <textarea name="reason" id="reason" rows="3" maxlength="1000" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        {{ __('Demander l\'effacement') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
