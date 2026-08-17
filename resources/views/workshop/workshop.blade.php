@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content')
{{--
  Accueil atelier : 4 tuiles qui remplissent l'écran de la tablette (2 x 2).
  Toute la tuile est cliquable — pas de petit bouton à viser avec des gants.
--}}
<div class="ws-home">
    <a class="ws-tile ws-tile--tasks" href="{{ route('workshop.task.lines') }}">
        <span class="ws-tile__badge badge badge-danger">Beta</span>
        <i class="ws-tile__icon fas fa-tasks"></i>
        <span class="ws-tile__title">{{ __('general_content.tasks_list_trans_key') }}</span>
        <span class="ws-tile__text">{{ __('general_content.note_1_trans_key') }}</span>
    </a>

    <a class="ws-tile ws-tile--production" href="{{ route('workshop.task.statu') }}">
        <span class="ws-tile__badge badge badge-danger">Beta</span>
        <i class="ws-tile__icon fas fa-play-circle"></i>
        <span class="ws-tile__title">{{ __('general_content.production_declaration_trans_key') }}</span>
        <span class="ws-tile__text">{{ __('general_content.note_2_trans_key') }}</span>
    </a>

    <a class="ws-tile ws-tile--stock" href="{{ route('workshop.stock.detail') }}">
        <span class="ws-tile__badge badge badge-danger">Beta</span>
        <i class="ws-tile__icon fas fa-barcode"></i>
        <span class="ws-tile__title">Stocks</span>
        <span class="ws-tile__text">Scannez une étiquette de mouvement de stock</span>
    </a>

    <a class="ws-tile ws-tile--reports" href="{{ route('workshop.reports') }}">
        <span class="ws-tile__badge badge badge-danger">Beta</span>
        <i class="ws-tile__icon fas fa-chart-line"></i>
        <span class="ws-tile__title">Reports</span>
        <span class="ws-tile__text">Réalisé pointé, rebuts, charge machine, andon</span>
    </a>
</div>
@stop

@section('css')
<style>
    /*
      La grille doit remplir la hauteur utile : on rend la chaîne AdminLTE flex
      (.content-wrapper > .content > .container-fluid) et les enfants s'étirent.

      Le min-height d'origine déduit navbar + footer, or ce layout ne rend aucun
      footer (pas de section 'footer' dans le projet) : sans ce recalcul une
      bande vide de 3,5 rem reste sous la 2e ligne de tuiles.
    */
    .content-wrapper { display: flex; flex-direction: column; }
    .wrapper .content-wrapper { min-height: calc(100vh - 3.5rem - 1px); }
    .content-wrapper > .content { flex: 1 1 auto; display: flex; padding: 0; }
    .content-wrapper > .content > .container-fluid { flex: 1 1 auto; display: flex; padding: 0; }

    .ws-home {
        flex: 1 1 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 12px;
        padding: 12px;
        box-sizing: border-box;
    }

    .ws-tile {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 1rem;
        border-radius: 10px;
        color: #fff;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.22);
        transition: transform 0.08s ease-out, filter 0.08s ease-out;
        -webkit-tap-highlight-color: transparent;
        overflow: hidden;
    }

    .ws-tile:hover,
    .ws-tile:focus { color: #fff; text-decoration: none; filter: brightness(1.06); }

    /* Retour tactile : la tuile s'enfonce sous le doigt. */
    .ws-tile:active { transform: scale(0.97); filter: brightness(0.94); }

    .ws-tile__icon  { font-size: clamp(2.5rem, 7vh, 5rem); margin-bottom: 0.6rem; opacity: 0.95; }
    .ws-tile__title { font-size: clamp(1.25rem, 3.2vh, 2.25rem); font-weight: 700; line-height: 1.15; }
    .ws-tile__text  { font-size: clamp(0.85rem, 1.9vh, 1.15rem); margin-top: 0.35rem; opacity: 0.9; max-width: 90%; }
    .ws-tile__badge { position: absolute; top: 10px; right: 10px; font-size: 0.8rem; }

    .ws-tile--tasks      { background-color: #17a2b8; }
    .ws-tile--production { background-color: #28a745; }
    .ws-tile--stock      { background-color: #dc3545; }
    .ws-tile--reports    { background-color: #ffc107; color: #212529; }
    .ws-tile--reports:hover,
    .ws-tile--reports:focus { color: #212529; }

    /* Téléphone en portrait : on empile plutôt que d'écraser 4 tuiles. */
    @media (max-width: 575px) {
        .ws-home {
            grid-template-columns: 1fr;
            grid-template-rows: repeat(4, minmax(110px, 1fr));
        }
        .ws-tile__text { display: none; }
    }
</style>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
        // Ajoute la classe sidebar-hidden à la balise body dès que la page est chargée
        $("body").addClass("sidebar-hidden");
        });
    </script>
@stop
