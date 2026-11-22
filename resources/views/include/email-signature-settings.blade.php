{{--
    Onglet « E-mail » du profil : signature ajoutée aux e-mails envoyés depuis
    l'ERP (documents, relances), App\Services\Mail\MailPersonalization.
--}}
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('profile.email-signature.update') }}">
            @csrf
            @method('PUT')
            <x-adminlte-card title="{{ __('mail_personalization.signature') }}" theme="primary">
                <p class="text-muted small">{{ __('mail_personalization.signature_hint') }}</p>
                <x-adminlte-text-editor name="email_signature" igroup-size="sm" placeholder="...">{{ \App\Support\SafeHtml::clean(old('email_signature', auth()->user()->email_signature)) }}</x-adminlte-text-editor>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle me-1"></i>{{ __('mail_personalization.signature_image_hint') }}
                </p>
                <x-slot name="footerSlot">
                    <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
            </x-adminlte-card>
        </form>
    </div>
    <div class="col-lg-4">
        @include('include.email-variables-help')
    </div>
</div>
