<div class="col-4">
    From
    <address>
        <strong>{{ $factoryName }}</strong><br>
        {{ $factoryAddress }}<br>
        {{ $factoryZipcode }}, {{ $factoryCity }}<br>
        {{ __('general_content.phone_trans_key') }}: {{ $factoryPhoneNumber }}<br>
        Email: {{ $factoryMail }}
    </address>
</div>
<!-- /.col -->
<div class="col-4">
    To
    <address>
        <strong>{{ $companieLabel }}</strong> - <strong>{{ $companieCivility }} - {{ $companieFirstName }}  {{ $companieName }}</strong><br>
        {{ $companieAdress }}<br>
        {{ $companieZipcode }}, {{ $companieCity }}<br>
        {{ $companieCountry }}<br>
        {{ __('general_content.phone_trans_key') }}: {{ $companieNumber }}<br>
        Email: {{ $companieMail }}
    </address>
</div>
<!-- /.col -->
<div class="col-4">
    <h1>{{  $documentName }} #{{  $code }}</h1>
    <b>Your Ref:</b> {{ $customerReference }}<br>
</div>
<!-- /.col -->