{{--
  Catégorie d'affaire (opportunité, devis, commande). Rien n'est affiché tant que le
  référentiel est vide : le champ est alors absent du formulaire et la valeur en base
  n'est pas touchée (règle `sometimes` des requêtes de mise à jour).
--}}
@php($affairCategories = \App\Models\Workflow\AffairCategory::choices($affairCategoryId ?? null))
@if ($affairCategories->isNotEmpty())
<x-adminlte-select name="affair_category_id" label="{{ __('affairs.category.label') }}" label-class="text-primary" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-primary bg-gradient">
            <i class="fas fa-shapes"></i>
        </div>
    </x-slot>
    <option value="">{{ __('affairs.category.none') }}</option>
    @foreach ($affairCategories as $item)
        <option value="{{ $item->id }}" @selected($item->id == ($affairCategoryId ?? null))>{{ $item->label }}@if(! $item->active) ({{ __('affairs.category.inactive') }})@endif</option>
    @endforeach
</x-adminlte-select>
@endif
