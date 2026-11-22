{{-- Variables remplacées dans les modèles d'e-mail (App\Services\Mail\MailPersonalization). --}}
<div class="card mb-3">
    <div class="card-header"><i class="fas fa-code me-1"></i>{{ __('mail_personalization.variables') }}</div>
    <div class="card-body small">
        <p class="text-muted">{{ __('mail_personalization.variables_hint') }}</p>
        <dl class="row mb-0">
            @foreach(['code', 'libelle', 'client', 'contact', 'utilisateur', 'entreprise', 'signature'] as $variable)
                <dt class="col-5"><code>{{ '{'.$variable.'}' }}</code></dt>
                <dd class="col-7">{{ __('mail_personalization.variable.'.$variable) }}</dd>
            @endforeach
        </dl>
        <p class="text-muted mt-2 mb-0">{{ __('mail_personalization.reminder_variables_hint') }}</p>
    </div>
</div>
