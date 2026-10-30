{{--
  Historique des modifications (activitylog) d'un enregistrement.
  $entries : sortie de App\Support\ActivityHistory (une entrée par modification).
--}}
<h6 class="mt-3">{{ __('activity_history.title') }}</h6>
<ul class="list-group list-group-flush small border rounded">
  @forelse($entries as $entry)
    <li class="list-group-item">
      <div class="d-flex justify-content-between">
        <span><strong>{{ $entry['causer'] }}</strong> · {{ $entry['event'] }}</span>
        <span class="text-body-secondary">{{ $entry['at'] }}</span>
      </div>
      @foreach($entry['changes'] as $change)
        <div class="text-body-secondary">
          {{ $change['label'] }} :
          @if($change['old'] !== null)<del>{{ $change['old'] }}</del> → @endif
          <span class="text-body">{{ $change['new'] ?? '—' }}</span>
        </div>
      @endforeach
    </li>
  @empty
    <li class="list-group-item text-body-secondary">{{ __('activity_history.empty') }}</li>
  @endforelse
</ul>
