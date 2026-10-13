<div class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-body-tertiary py-4">
    <div class="mb-3">
        {{ $logo }}
    </div>
    <div class="card shadow-sm mb-3" style="width: 100%; max-width: 28rem;">
        <div class="card-body p-4">
            {{ $slot }}
        </div>
    </div>
</div>
