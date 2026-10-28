{{--
    Description longue, caractéristiques et exigences d'une rangée préparée par
    App\Services\Documents\SalesLayoutRows (aucun montant).

    $row            rangée article
    $description    afficher la description longue (défaut : oui)
--}}
<div class="facts">
@if(($description ?? true) && !empty($row['long_description']))
    <div>{!! nl2br(e($row['long_description'])) !!}</div>
@endif
@if(!empty($row['characteristics']))
    <div>
    @foreach($row['characteristics'] as $fact)
        <strong>{{ $fact['label'] }}</strong> : {{ $fact['value'] }}@if(!$loop->last) · @endif
    @endforeach
    </div>
@endif
@if(!empty($row['requirements']))
    <div>
        <strong>{{ __('pdf_layouts.requirements') }}</strong> :
    @foreach($row['requirements'] as $requirement)
        {{ $requirement['label'] }}@if($requirement['label'] !== '' && $requirement['value'] !== '') : @endif{{ $requirement['value'] }}@if(!$loop->last) ; @endif
    @endforeach
    </div>
@endif
</div>
