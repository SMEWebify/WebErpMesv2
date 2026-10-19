{{--
    Sélecteur société → recharge les adresses et contacts de la société choisie.
    Paramètres : $url (route *.company-ac), $companyId, $addressId, $contactId (id des <select>).
    JavaScript natif : les sélecteurs sont des <x-adminlte-select-bs> (Tom Select),
    qui déclenchent l'événement natif « change » sur le <select> d'origine.
--}}
<script>
(function () {
    var company = document.getElementById(@json($companyId));
    if (!company) return;
    if (!document.getElementById(@json($addressId)) && !document.getElementById(@json($contactId))) return;

    var url = @json($url);

    // La première option reste sélectionnée, comme avec l'ancien Select2.
    function fill(id, items) {
        var select = document.getElementById(id);
        if (!select) return;

        var ts = select.tomselect;
        if (ts) {
            ts.clear(true);
            ts.clearOptions();
        }

        select.replaceChildren();
        items.forEach(function (item) {
            select.add(new Option(item.label, item.id));
        });

        if (ts) ts.sync();
        select.dispatchEvent(new Event('change'));
    }

    company.addEventListener('change', function () {
        var query = new URLSearchParams({ company_id: company.value });

        fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + query, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                if (!response.ok) throw new Error(response.status);
                return response.json();
            })
            .then(function (data) {
                fill(@json($addressId), data.addresses || []);
                fill(@json($contactId), data.contacts || []);
            })
            .catch(function (error) {
                console.error('company-ac', error);
            });
    });
})();
</script>
