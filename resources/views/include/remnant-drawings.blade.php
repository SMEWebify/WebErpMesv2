{{-- Dessins DXF / SVG d'une chute (GED du mouvement d'entrée, rôle vectoriel). $drawings : [['id' => …, 'name' => …], …] --}}
@foreach ($drawings as $drawing)
  <a href="{{ route('files.download', ['file' => $drawing['id']]) }}" class="small text-nowrap me-2" title="{{ __('stock_remnants.drawing_download') }}">
    <i class="fa-solid fa-draw-polygon me-1" aria-hidden="true"></i>{{ $drawing['name'] }}
  </a>
@endforeach
