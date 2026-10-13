{{-- Navbar theme widget --}}
{{-- Surcharge du widget de mode d'AdminLTE : il fait tourner clair -> sombre -> pro --}}
{{-- au lieu de proposer clair / sombre / auto. Le mode "pro" garde un fond clair --}}
{{-- mais neutralise le chrome coloré (voir public/css/theme-pro.css). --}}
{{-- La préférence vit en session (App\Support\ThemeMode) : AdminLTE v4 en déduit --}}
{{-- l'attribut data-bs-theme de <html> au rendu suivant. --}}

@php
    $themeModes = [
        \App\Support\ThemeMode::LIGHT => ['icon' => 'far fa-moon',   'label' => __('general_content.theme_light_trans_key')],
        \App\Support\ThemeMode::DARK  => ['icon' => 'fas fa-moon',   'label' => __('general_content.theme_dark_trans_key')],
        \App\Support\ThemeMode::PRO   => ['icon' => 'fas fa-adjust', 'label' => __('general_content.theme_pro_trans_key')],
    ];

    $themeMode = \App\Support\ThemeMode::current();
@endphp

<li class="nav-item adminlte-darkmode-widget" data-theme-mode="{{ $themeMode }}">

    <a class="nav-link" href="#" role="button" title="{{ $themeModes[$themeMode]['label'] }}"
       aria-label="{{ $themeModes[$themeMode]['label'] }}">
        <i class="{{ $themeModes[$themeMode]['icon'] }}"></i>
    </a>

</li>

{{-- Add Javascript listener for the click event --}}

@once
@push('js')
<script>

    (function () {

        const widget = document.querySelector('li.adminlte-darkmode-widget');

        if (! widget) {
            return;
        }

        const link = widget.querySelector('a.nav-link');
        const icon = widget.querySelector('i');
        const modes = @json($themeModes);
        const order = @json(array_keys($themeModes));

        // Applique le mode au document courant et à ceux des iframes (mode IFrame).

        const applyMode = (mode) => {

            widget.dataset.themeMode = mode;
            icon.className = modes[mode].icon;
            link.setAttribute('title', modes[mode].label);
            link.setAttribute('aria-label', modes[mode].label);

            const documents = [document];

            document.querySelectorAll('div.iframe-mode iframe').forEach((frame) => {
                if (frame.contentDocument) {
                    documents.push(frame.contentDocument);
                }
            });

            documents.forEach((doc) => {
                const colorMode = mode === 'dark' ? 'dark' : 'light';

                doc.documentElement.setAttribute('data-bs-theme', colorMode);
                doc.documentElement.style.colorScheme = colorMode;

                if (doc.body) {
                    doc.body.classList.toggle('theme-pro', mode === 'pro');
                }
            });
        };

        widget.addEventListener('click', (event) => {

            event.preventDefault();

            const next = order[(order.indexOf(widget.dataset.themeMode) + 1) % order.length];

            applyMode(next);

            // Notify the server, which persists the preference over requests.

            fetch("{{ route('theme.mode.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({mode: next}),
            })
            .catch((error) => {
                console.log('Failed to notify server that the theme mode changed', error);
            });
        });
    })();

</script>
@endpush
@endonce
