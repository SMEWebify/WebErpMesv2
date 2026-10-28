{{-- Notation rapide du fournisseur (React : components/purchases/ReceiptSupplierRating.jsx) --}}
<x-adminlte-card title="{{ $supplierRating['texts']['card_title'] }}" theme="warning" theme-mode="outline" icon="fas fa-star-half-alt">
  <div
    id="receipt-supplier-rating-app"
    data-endpoint="{{ $supplierRating['endpoint'] }}"
    data-auto-open="{{ $supplierRating['autoOpen'] ? '1' : '0' }}"
    data-state="{{ json_encode($supplierRating['state']) }}"
    data-texts="{{ json_encode($supplierRating['texts']) }}"
  ></div>
</x-adminlte-card>
