<form method="GET" action="{{ $route }}" class="d-flex flex-wrap align-items-center gap-1">
    @if(count($layouts) > 1)
    <select name="layout" class="form-select form-select-sm w-auto" aria-label="{{ __('pdf_layouts.menu_layout') }}" title="{{ __('pdf_layouts.menu_layout') }}">
        @foreach($layouts as $key => $layout)
        <option value="{{ $key }}">{{ $layout->label() }}</option>
        @endforeach
    </select>
    @endif
    @if(count($languages) > 1)
    <select name="lang" class="form-select form-select-sm w-auto" aria-label="{{ __('pdf_layouts.menu_language') }}" title="{{ __('pdf_layouts.menu_language') }}">
        @unless(isset($languages[$currentLanguage]))
        {{-- Langue de l'utilisateur hors liste (es, vi…) : la garder par défaut, comme avant. --}}
        <option value="" selected>{{ strtoupper($currentLanguage) }}</option>
        @endunless
        @foreach($languages as $code => $name)
        <option value="{{ $code }}" @selected($code === $currentLanguage)>{{ $name }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="btn btn-info btn-sm">
        <i class="fas fa-file-pdf"></i>
        {{ __('pdf_layouts.menu_print') }}
    </button>
</form>
