{{--
    « ? » qui ouvre la section d'un guide (resources/guides/{guide}/guide.md, ancre `## Titre {#section}`).
    Rien n'est affiché si l'utilisateur ne peut pas lire le guide (permission, option du .env).
    Écrire `guide` avant `section` : tests/Feature/GuideHelpTest.php relit ces attributs dans le code.
--}}
@props(['guide', 'section' => null])
@php($link = app(\App\Services\Guides\GuideLibrary::class)->helpLink(auth()->user(), $guide, $section))
@if ($link)
    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" {{ $attributes->merge(['class' => 'wem-guide-help']) }}
       title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></a>
@endif
