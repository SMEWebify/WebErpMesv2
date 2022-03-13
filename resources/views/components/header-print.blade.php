<div class="col-sm-4 invoice-col">
    From
    <address>
        <strong>{{ $factoryName }}</strong><br>
        {{ $factoryAddress }}<br>
        {{ $factoryZipcode }}, {{ $factoryCity }}<br>
        Phone: {{ $factoryPhoneNumber }}<br>
        Email: {{ $factoryMail }}
    </address>
</div>
<!-- /.col -->
<div class="col-sm-4 invoice-col">
    To
    <address>
        <strong>{{ $companieLabel }}</strong> - <strong>{{ $companieCivility }} - {{ $companieFirstName }}  {{ $companieName }}</strong><br>
        {{ $companieAdress }}<br>
        {{ $companieZipcode }}, {{ $companieCity }}<br>
        {{ $companieCountry }}<br>
        Phone: {{ $companieNumber }}<br>
        Email: {{ $companieMail }}
    </address>
</div>
<!-- /.col -->
<div class="col-sm-4 invoice-col">
    <h1>{{  $documentName }} #{{  $code }}</h1>
    <b>Your Ref:</b> {{ $customerReference }}<br>
</div>
<!-- /.col -->