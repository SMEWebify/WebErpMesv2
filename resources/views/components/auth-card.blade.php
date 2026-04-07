<div class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-light py-4">
    <div class="mb-3">
        {{ $logo }}
    </div>
    <div class="card shadow-sm" style="width: 100%; max-width: 28rem;">
        <div class="card-body p-4">
            {{ $slot }}
        </div>
    </div>
</div>
