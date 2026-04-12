@if(count($ancestors) > 0 || count($descendants) > 0)
<div class="px-1 pt-2 pb-1">
  <div class="d-flex align-items-center flex-wrap" style="gap: 5px; font-size: 0.8rem; line-height: 1;">

    {{-- Ancêtres --}}
    @foreach($ancestors as $node)
      <a href="{{ $node['url'] }}"
         class="badge badge-{{ $node['color'] }} py-1 px-2"
         style="text-decoration: none; font-weight: 500; opacity: 0.85;">
        <i class="{{ $node['icon'] }} mr-1"></i>{{ $node['label'] }}
      </a>
      <span class="text-muted" style="font-size: 0.65rem;">
        <i class="fas fa-chevron-right"></i>
      </span>
    @endforeach

    {{-- Entité courante --}}
    <span class="badge badge-{{ $current['color'] }} py-1 px-2"
          style="font-size: 0.82rem; font-weight: 600; box-shadow: 0 0 0 2px rgba(0,0,0,0.18);">
      <i class="{{ $current['icon'] }} mr-1"></i>{{ $current['label'] }}
    </span>

    {{-- Descendants --}}
    @if(count($descendants) > 0)
      <span class="text-muted" style="font-size: 0.65rem;">
        <i class="fas fa-chevron-right"></i>
      </span>
      @foreach($descendants as $node)
        <a href="{{ $node['url'] }}"
           class="badge badge-{{ $node['color'] }} py-1 px-2"
           style="text-decoration: none; font-weight: 500; opacity: 0.85;">
          <i class="{{ $node['icon'] }} mr-1"></i>{{ $node['label'] }}
        </a>
      @endforeach
    @endif

  </div>
</div>
@endif
