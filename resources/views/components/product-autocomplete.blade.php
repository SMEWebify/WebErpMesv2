@props(['name', 'label' => null, 'selected' => null])

@php
    $inputId = $attributes->get('id', $name);
    // Après une erreur de validation, on réaffiche le produit déjà choisi
    $selected ??= old($name)
        ? \App\Models\Products\Products::select('id', 'code', 'label')->find(old($name))
        : null;
@endphp

<div class="product-autocomplete" data-search-url="{{ route('products.json.search') }}" style="position: relative;">
    @if ($label)
        <label for="{{ $inputId }}_search" class="text-lightblue">{{ $label }}</label>
    @endif
    <div class="input-group input-group-sm">
        <div class="input-group-prepend">
            <div class="input-group-text bg-gradient-info"><i class="fas fa-barcode"></i></div>
        </div>
        <input type="text" id="{{ $inputId }}_search" data-role="search" autocomplete="off"
               class="form-control @error($name) is-invalid @enderror"
               placeholder="{{ __('general_content.select_product_trans_key') }}…"
               value="{{ $selected ? $selected->code . ' — ' . $selected->label : '' }}">
    </div>
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" data-role="value" value="{{ $selected?->id }}">
    <div data-role="results" class="border bg-white shadow-sm" hidden
         style="position: absolute; z-index: 1060; left: 0; right: 0; max-height: 200px; overflow-y: auto;"></div>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
<script>
// Recherche serveur des produits (products.json.search) : le catalogue complet n'est
// plus rendu dans la page. JavaScript natif, sans dépendance à select2 ni à jQuery.
document.addEventListener('DOMContentLoaded', () => {
    const emptyLabel = @json(__('general_content.no_results_trans_key'));

    document.querySelectorAll('.product-autocomplete').forEach((root) => {
        const search  = root.querySelector('[data-role="search"]');
        const value   = root.querySelector('[data-role="value"]');
        const results = root.querySelector('[data-role="results"]');
        let timer = null;
        let controller = null;

        const render = (products) => {
            results.replaceChildren();
            if (products.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-2 text-muted small';
                empty.textContent = emptyLabel;
                results.appendChild(empty);
            }
            products.forEach((product) => {
                const item = document.createElement('div');
                item.className = 'px-3 py-2';
                item.style.cursor = 'pointer';
                item.style.fontSize = '0.85rem';
                const code = document.createElement('strong');
                code.textContent = product.code;
                const label = document.createElement('span');
                label.className = 'text-muted ml-2';
                label.textContent = product.label;
                item.append(code, label);
                item.addEventListener('mouseenter', () => { item.style.background = '#f0f4ff'; });
                item.addEventListener('mouseleave', () => { item.style.background = ''; });
                item.addEventListener('mousedown', () => {
                    value.value = product.id;
                    search.value = `${product.code} — ${product.label}`;
                    results.hidden = true;
                });
                results.appendChild(item);
            });
            results.hidden = false;
        };

        const query = () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                controller?.abort();
                controller = new AbortController();
                const url = `${root.dataset.searchUrl}?q=${encodeURIComponent(search.value.trim())}`;
                fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal })
                    .then((r) => (r.ok ? r.json() : { products: [] }))
                    .then((d) => render(d.products ?? []))
                    .catch((e) => { if (e.name !== 'AbortError') results.hidden = true; });
            }, 250);
        };

        // Taper sans choisir dans la liste vide la sélection : la validation serveur le signalera
        search.addEventListener('input', () => { value.value = ''; query(); });
        search.addEventListener('focus', () => { if (!value.value) query(); });
        search.addEventListener('blur', () => setTimeout(() => { results.hidden = true; }, 150));
    });
});
</script>
@endonce
