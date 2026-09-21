@extends('adminlte::page')

@section('title', 'Délais inter-opérations')

@section('content_header')
    <h1>Délais inter-opérations</h1>
@stop

@section('right-sidebar')

@section('content')

  @include('include.alert-result')
  <div class="tab-pane" id="OperationTransitionDelays">
    <x-InfocalloutComponent note="Tampon appliqué entre deux tâches consécutives d'une même ligne. Une paire non listée retombe sur le défaut atelier ({{ number_format($defaultHours, 2) }} h). Le zéro d'une paire force l'absence de délai (jamais neutre). Écart minimum toujours ajouté : {{ number_format($minGapHours, 2) }} h. Se règle dans <code>config/planning.php</code>." />
    <div class="row">
      <div class="col-md-8">
        <x-adminlte-card title="Paires d'opérations" theme="teal" maximizable>
          <div class="table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Opération de départ</th>
                  <th>Opération d'arrivée</th>
                  <th class="text-right">Délai (heures atelier)</th>
                  <th></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($delays as $delay)
                <tr>
                  <td>
                    <strong>{{ $delay->fromService?->code }}</strong>
                    <span class="text-muted">— {{ $delay->fromService?->label }}</span>
                  </td>
                  <td>
                    <strong>{{ $delay->toService?->code }}</strong>
                    <span class="text-muted">— {{ $delay->toService?->label }}</span>
                  </td>
                  <td class="text-right">{{ number_format((float) $delay->transfer_hours, 2) }}</td>
                  <td class="py-0 align-middle">
                    <x-ButtonTextEdit :modalTarget="'OperationTransitionDelay' . $delay->id" />
                    <x-adminlte-modal id="OperationTransitionDelay{{ $delay->id }}"
                                      title="{{ $delay->fromService?->code }} → {{ $delay->toService?->code }}"
                                      theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                      <form method="POST" action="{{ route('methods.operation-transition-delay.update', ['id' => $delay->id]) }}">
                        @csrf
                        <div class="card-body">
                          <div class="form-group">
                            <label for="transfer_hours_{{ $delay->id }}">Délai (heures atelier)</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                              </div>
                              <input type="number" step="0.25" min="0" max="9999.99"
                                     class="form-control" name="transfer_hours"
                                     id="transfer_hours_{{ $delay->id }}"
                                     value="{{ $delay->transfer_hours }}" required>
                              <div class="input-group-append">
                                <span class="input-group-text">h</span>
                              </div>
                            </div>
                            <small class="form-text text-muted">
                              0 = pas de délai pour cette paire (contourne le défaut atelier).
                            </small>
                          </div>
                        </div>
                        <div class="card-footer">
                          <x-adminlte-button class="btn-flat" type="submit"
                                             label="{{ __('general_content.update_trans_key') }}"
                                             theme="info" icon="fas fa-lg fa-save"/>
                        </div>
                      </form>
                    </x-adminlte-modal>
                  </td>
                  <td class="py-0 align-middle">
                    <form method="POST"
                          action="{{ route('methods.operation-transition-delay.destroy', ['id' => $delay->id]) }}"
                          onsubmit="return confirm('Supprimer ce délai ?');">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('general_content.delete_trans_key') }}">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
                @empty
                <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
              </tbody>
            </table>
          </div>
        </x-adminlte-card>
      </div>
      <div class="col-md-4">
        <form method="POST" action="{{ route('methods.operation-transition-delay.create') }}" class="form-horizontal">
          <x-adminlte-card title="Nouvelle paire" theme="secondary" maximizable>
            @csrf
            <div class="form-group">
              <label for="from_service_id">Opération de départ</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-arrow-right"></i></span>
                </div>
                <select class="form-control" name="from_service_id" id="from_service_id" required>
                  <option value="">— {{ __('general_content.service_trans_key') }} —</option>
                  @foreach ($services as $service)
                    <option value="{{ $service->id }}">{{ $service->code }} — {{ $service->label }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="to_service_id">Opération d'arrivée</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-arrow-right"></i></span>
                </div>
                <select class="form-control" name="to_service_id" id="to_service_id" required>
                  <option value="">— {{ __('general_content.service_trans_key') }} —</option>
                  @foreach ($services as $service)
                    <option value="{{ $service->id }}">{{ $service->code }} — {{ $service->label }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="transfer_hours">Délai (heures atelier)</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                </div>
                <input type="number" step="0.25" min="0" max="9999.99"
                       class="form-control" name="transfer_hours" id="transfer_hours"
                       placeholder="ex. 2" required>
                <div class="input-group-append">
                  <span class="input-group-text">h</span>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <x-adminlte-button class="btn-flat" type="submit"
                                 label="{{ __('general_content.submit_trans_key') }}"
                                 theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          </x-adminlte-card>
        </form>
      </div>
    </div>
  </div>
@stop

@section('css')
@stop

@section('js')
@stop
