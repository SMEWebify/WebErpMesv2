{{-- Génération automatique des ordres de travail. $plan : null sur la création (cochée par défaut). --}}
<div class="card mb-3 bg-body-tertiary">
    <div class="card-body">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="auto_generate" value="0">
            <input class="form-check-input" type="checkbox" role="switch" name="auto_generate" id="auto_generate" value="1"
                   @checked(old('auto_generate', $plan?->auto_generate ?? true))>
            <label class="form-check-label fw-bold" for="auto_generate">{{ __('general_content.gmao_auto_generate_trans_key') }}</label>
        </div>
        <p class="text-muted small mb-3">{{ __('general_content.gmao_auto_generate_help_trans_key') }}</p>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label for="interval_days">{{ __('general_content.gmao_interval_days_trans_key') }}</label>
                <input type="number" min="1" class="form-control @error('interval_days') is-invalid @enderror" name="interval_days" id="interval_days"
                       value="{{ old('interval_days', $plan?->interval_days) }}">
                @error('interval_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('general_content.gmao_interval_days_help_trans_key') }}</div>
            </div>
            <div class="col-md-6 mb-2">
                <label for="lead_days">{{ __('general_content.gmao_lead_days_trans_key') }}</label>
                <input type="number" min="0" class="form-control" name="lead_days" id="lead_days"
                       value="{{ old('lead_days', $plan?->lead_days ?? 7) }}">
                <div class="form-text">{{ __('general_content.gmao_lead_days_help_trans_key') }}</div>
            </div>
        </div>
    </div>
</div>
