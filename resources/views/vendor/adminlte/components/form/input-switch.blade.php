{{--
    Surcharge locale du composant <x-adminlte-input-switch> du paquet
    jeroennoten/laravel-adminlte.

    La version d'origine s'appuie sur le plugin jQuery d'interrupteur v3.3.4
    (2016, dépôt archivé), dont _getClasses() appelle $.isArray() — supprimé de
    jQuery 4.0. Cette surcharge rend un interrupteur 100 % CSS, sans jQuery et
    sans dépendance à Bootstrap : le style ne réutilise aucune classe .custom-*
    (Bootstrap 4) ni .form-* (Bootstrap 5), car les deux frameworks coexistent
    selon les pages (AdminLTE 3 embarque BS4, certaines vues chargent en plus
    BS5 via @vite). Les classes sont préfixées « wem-switch » et ne peuvent donc
    entrer en conflit avec ni l'un ni l'autre.

    Contrat conservé à l'identique : name, value="true" à la soumission, état
    initial (is-checked / checked), libellés data-on-text / data-off-text,
    couleurs data-on-color / data-off-color, tailles sm|lg, is-invalid et le
    rappel de l'ancienne valeur après erreur de validation.
--}}

@extends('adminlte::components.form.input-group-component')

{{-- NOTE : tout tient dans un SEUL bloc PHP, y compris l'appel à
     $setErrorsBag(). Blade extrait les blocs PHP bruts avec une regex non
     gourmande avant toute autre compilation : une directive PHP en ligne
     placée juste au-dessus serait prise pour l'ouverture de ce bloc, et tout
     le code intermédiaire se retrouverait avalé sans être exécuté. Ne pas
     réintroduire de directive PHP en ligne ici. --}}

@php
    // Set errors bag internally
    $setErrorsBag($errors ?? null);

    // Palette AdminLTE 3 / Bootstrap 4. Une valeur inconnue est réutilisée
    // telle quelle, ce qui autorise aussi bien "teal" que "#20c997".
    $swPalette = [
        'primary' => ['#007bff', '#fff'], 'secondary' => ['#6c757d', '#fff'],
        'success' => ['#28a745', '#fff'], 'danger' => ['#dc3545', '#fff'],
        'warning' => ['#ffc107', '#1f2d3d'], 'info' => ['#17a2b8', '#fff'],
        'teal' => ['#20c997', '#fff'], 'indigo' => ['#6610f2', '#fff'],
        'purple' => ['#6f42c1', '#fff'], 'pink' => ['#e83e8c', '#fff'],
        'orange' => ['#fd7e14', '#1f2d3d'], 'navy' => ['#001f3f', '#fff'],
        'olive' => ['#3d9970', '#fff'], 'lime' => ['#01ff70', '#1f2d3d'],
        'fuchsia' => ['#f012be', '#fff'], 'maroon' => ['#d81b60', '#fff'],
        'light' => ['#f8f9fa', '#1f2d3d'], 'dark' => ['#343a40', '#fff'],
        'gray' => ['#adb5bd', '#1f2d3d'], 'default' => ['#e9ecef', '#495057'],
    ];

    $swResolveColor = static function ($name, $fallback) use ($swPalette) {
        $name = $name !== null && $name !== '' ? $name : $fallback;

        return $swPalette[$name] ?? [$name, '#fff'];
    };

    // Une chaîne vide, "0", "false", "off" ou "no" valent « décoché ». Cela
    // couvre :checked="$bool" (Blade omet l'attribut si false) aussi bien que
    // checked="checked" écrit en dur.
    $swIsTruthy = static function ($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return ! in_array(strtolower($value), ['', '0', 'false', 'off', 'no'], true);
        }

        return (bool) $value;
    };

    // État initial : config['state'] (alimenté par la prop is-checked côté PHP),
    // puis l'attribut natif checked s'il est présent, puis l'ancienne valeur
    // soumise en cas d'erreur de validation.
    $swState = ! empty($config['state']);

    if ($attributes->has('checked')) {
        $swState = $swIsTruthy($attributes->get('checked'));
    }

    if ($enableOldSupport && isset($errors) && $errors->any()) {
        $swState = (bool) $getOldValue($errorKey);
    }

    $swOnText = $attributes->get('data-on-text') ?? ($config['onText'] ?? 'ON');
    $swOffText = $attributes->get('data-off-text') ?? ($config['offText'] ?? 'OFF');

    [$swOnBg, $swOnFg] = $swResolveColor(
        $attributes->get('data-on-color') ?? ($config['onColor'] ?? null), 'primary'
    );
    [$swOffBg, $swOffFg] = $swResolveColor(
        $attributes->get('data-off-color') ?? ($config['offColor'] ?? null), 'default'
    );

    // Attributs consommés par le rendu : ils ne doivent pas être réémis sur
    // l'<input>, où ils ne serviraient plus à rien.
    $swInputAttributes = $attributes->except([
        'data-on-text', 'data-off-text', 'data-on-color', 'data-off-color',
        'checked',
    ]);

    $swStyle = sprintf(
        '--wem-switch-on-bg:%s;--wem-switch-on-fg:%s;--wem-switch-off-bg:%s;--wem-switch-off-fg:%s',
        $swOnBg, $swOnFg, $swOffBg, $swOffFg
    );
@endphp

{{-- Set input group item section --}}

@section('input_group_item')

    {{-- Interrupteur : <label> englobant, l'association à l'<input> est donc
         implicite. Pas d'attribut for= ici pour éviter une double bascule. --}}
    <label class="wem-switch" style="{{ $swStyle }}">

        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" @checked($swState)
            {{ $swInputAttributes->merge([
                'class' => trim('wem-switch__input '.$makeItemClass()),
                'value' => 'true',
            ]) }}>

        <span class="wem-switch__track" aria-hidden="true">
            <span class="wem-switch__texts">
                <span class="wem-switch__text wem-switch__text--on">{{ $swOnText }}</span>
                <span class="wem-switch__text wem-switch__text--off">{{ $swOffText }}</span>
            </span>
            <span class="wem-switch__handle"></span>
        </span>

    </label>

@overwrite

{{-- Feuille de style de l'interrupteur, poussée une seule fois par page.
     @push('css') alimente @stack('css') rendu dans le <head> par
     adminlte::page, y compris pour les vues qui ne passent pas par Vite. --}}

@once
@push('css')
<style type="text/css">

    .wem-switch {
        position: relative;
        display: inline-flex;
        align-items: stretch;
        flex: 0 0 auto;
        margin: 0;
        cursor: pointer;
        -webkit-user-select: none;
        user-select: none;
        line-height: 1;
    }

    /* L'input reste dans le flux du formulaire et focusable, mais invisible. */
    .wem-switch__input {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: 0;
        padding: 0;
        opacity: 0;
        pointer-events: none;
    }

    .wem-switch__track {
        position: relative;
        display: inline-flex;
        align-items: center;
        box-sizing: border-box;
        min-width: 5rem;
        height: 2.25rem;
        padding: 0 .5rem;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: .25rem;
        background-color: var(--wem-switch-off-bg, #e9ecef);
        color: var(--wem-switch-off-fg, #495057);
        font-size: .875rem;
        font-weight: 400;
        transition: background-color .2s ease, color .2s ease, border-color .2s ease;
    }

    .wem-switch__input:checked ~ .wem-switch__track {
        background-color: var(--wem-switch-on-bg, #007bff);
        color: var(--wem-switch-on-fg, #fff);
    }

    /* Les deux libellés sont empilés dans la même cellule de grille : la
       largeur du composant est celle du plus long, et elle ne bouge pas à la
       bascule. */
    .wem-switch__texts {
        display: grid;
        flex: 1 1 auto;
        text-align: right;
        padding-left: 1.625rem;
        transition: padding .2s ease;
    }

    .wem-switch__input:checked ~ .wem-switch__track .wem-switch__texts {
        text-align: left;
        padding-left: 0;
        padding-right: 1.625rem;
    }

    .wem-switch__text {
        grid-area: 1 / 1;
        opacity: 0;
        white-space: nowrap;
        transition: opacity .15s ease;
    }

    .wem-switch__input:checked ~ .wem-switch__track .wem-switch__text--on,
    .wem-switch__input:not(:checked) ~ .wem-switch__track .wem-switch__text--off {
        opacity: 1;
    }

    .wem-switch__handle {
        position: absolute;
        top: 3px;
        bottom: 3px;
        left: 3px;
        width: 1.375rem;
        border-radius: .1875rem;
        background-color: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .35);
        transition: left .2s ease;
    }

    .wem-switch__input:checked ~ .wem-switch__track .wem-switch__handle {
        left: calc(100% - 1.375rem - 3px);
    }

    .wem-switch__input:focus-visible ~ .wem-switch__track {
        box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .35);
    }

    .wem-switch__input:disabled ~ .wem-switch__track {
        opacity: .65;
        cursor: not-allowed;
    }

    /* Tailles alignées sur les hauteurs de champ Bootstrap sm/lg. */
    .input-group-lg .wem-switch__track {
        height: 2.875rem;
        font-size: 1.25rem;
        min-width: 6.25rem;
    }

    .input-group-lg .wem-switch__handle {
        width: 1.75rem;
    }

    .input-group-lg .wem-switch__input:checked ~ .wem-switch__track .wem-switch__handle {
        left: calc(100% - 1.75rem - 3px);
    }

    .input-group-lg .wem-switch__texts {
        padding-left: 2rem;
    }

    .input-group-lg .wem-switch__input:checked ~ .wem-switch__track .wem-switch__texts {
        padding-left: 0;
        padding-right: 2rem;
    }

    .input-group-sm .wem-switch__track {
        height: 1.8125rem;
        font-size: .75rem;
        min-width: 4.25rem;
    }

    .input-group-sm .wem-switch__handle {
        width: 1.125rem;
    }

    .input-group-sm .wem-switch__input:checked ~ .wem-switch__track .wem-switch__handle {
        left: calc(100% - 1.125rem - 3px);
    }

    .input-group-sm .wem-switch__texts {
        padding-left: 1.375rem;
    }

    .input-group-sm .wem-switch__input:checked ~ .wem-switch__track .wem-switch__texts {
        padding-left: 0;
        padding-right: 1.375rem;
    }

    /* Reprise du signalement d'erreur de la version d'origine. */
    .adminlte-invalid-iswgroup > .wem-switch > .wem-switch__track,
    .wem-switch__input.is-invalid ~ .wem-switch__track {
        border-color: #dc3545;
        box-shadow: 0 .25rem .5rem rgba(255, 0, 0, .25);
    }

    @media (prefers-reduced-motion: reduce) {
        .wem-switch__track,
        .wem-switch__texts,
        .wem-switch__text,
        .wem-switch__handle {
            transition: none;
        }
    }

</style>
@endpush
@endonce
