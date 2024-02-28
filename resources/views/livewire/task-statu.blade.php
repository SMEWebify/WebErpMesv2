<div>
    
  <div class="card-body" >
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
        </div>
        <input type="text" class="form-control" wire:model.live="search" placeholder="{{ __('general_content.search_task_trans_key') }}">
    </div>
  </div>

  @empty($Task)
  <h1>{{ __('general_content.no_call_task_trans_key') }}</h1> 
  @else
    @if(is_null($Task->order_lines_id))
      <x-adminlte-alert theme="info" title="Info">{{ __('general_content.quote_task_trans_key') }}</x-adminlte-alert>
    @else
      <div class="card">
        <div class="card-header">
          
          
          <h3 class="card-title">
            <x-OrderButton id="{{ $Task->OrderLines->orders_id }}" code="{{ $Task->OrderLines->order->code }}"  />  
            - #{{ __('general_content.line_trans_key') }} {{ $Task->OrderLines->label }}
            @if( $Task->OrderLines->product_id && $Task->OrderLines->product->drawing_file)
            -  <a class="btn btn-info" href="{{ asset('drawing/'. $Task->Component->drawing_file) }}" target="_blank"><i class="fa fa-lg fa-fw fa-eye"></i></a>
            @endif
            - #{{ $Task->id }} {{ __('general_content.task_detail_trans_key') }}  {{ $Task->label }}
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="{{ __('general_content.collapse_trans_key') }}">
              <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="{{ __('general_content.remove_trans_key') }}">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <div class="card-body">
          @include('include.alert-result')
          <div class="row">
            <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
              <div class="row">
                <div class="col-12 col-sm-1"> 
                  @if($Task->service->picture )
                    <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'. $Task->service->picture) }}">
                  @endif
                </div>
                @if($Task->service->type == 1 or  $Task->service->type == 7)
                <div class="col-12 col-sm-3">
                  <x-adminlte-info-box title="{{ __('general_content.total_time_trans_key') }}" text="{{ $Task->getTotalLogTime() }} h" icon="fa fa-stopwatch" theme="warning"/>
                </div>
                @endif
                <div class="col-12 col-sm-3">
                  <x-adminlte-info-box title="{{ __('general_content.finish_part_qty_trans_key') }}" text="{{ $Task->getTotalLogGoodQt() }} item(s)" icon="fa fa-database" theme="success"/>
                </div>
                <div class="col-12 col-sm-3">
                  <x-adminlte-info-box title="{{ __('general_content.bad_part_qty_trans_key') }}" text="{{ $Task->getTotalLogBadQt() }} item(s)" icon="fa fa-arrow-down" theme="danger "/>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <h4>{{ __('general_content.logs_activity_trans_key') }}</h4>   
                  @if($Task->service->type == 1 or  $Task->service->type == 7)
                    @forelse ($taskActivities as $taskActivitie)
                          @if($taskActivitie->type == 1)
                            <p class="lead">{{ $taskActivitie->user->name }} - {{ __('general_content.set_to_start_trans_key') }} - {{ $taskActivitie->GetPrettyCreatedAttribute() }} </p>
                          @elseif ($taskActivitie->type == 2)
                          <p class="text-primary">{{ $taskActivitie->user->name }} - {{ __('general_content.set_to_end_trans_key') }} - {{ $taskActivitie->GetPrettyCreatedAttribute() }} </p>
                          @elseif ($taskActivitie->type == 3)
                          <p class="text-info">{{ $taskActivitie->user->name }} - {{ __('general_content.set_to_finish_trans_key') }} - {{ $taskActivitie->GetPrettyCreatedAttribute() }} </p>
                          @elseif ($taskActivitie->type == 4)
                            <p class="text-success">{{ $taskActivitie->user->name }} - {{ __('general_content.declare_finish_trans_key') }} <strong>{{ $taskActivitie->good_qt }}</strong> {{ __('general_content.part_trans_key') }} - {{ $taskActivitie->GetPrettyCreatedAttribute() }} </p>
                          @elseif ($taskActivitie->type == 5)
                            <p class="text-danger">{{ $taskActivitie->user->name }} - {{ __('general_content.declare_rejected_trans_key') }} <strong>{{ $taskActivitie->bad_qt }}</strong> {{ __('general_content.part_trans_key') }} - {{ $taskActivitie->GetPrettyCreatedAttribute() }} </p>
                          @endif
                          <hr>
                    @empty
                      <p>
                        {{ __('general_content.no_activity_trans_key') }} 
                      </p>
                    @endforelse
                  @else
                    <div class="timeline timeline-inverse">
                      @php
                          $previousDate = null;
                      @endphp
            
                      @foreach($timelineData as $item)
                        @if ($item['date'] != $previousDate)
                        <div class="time-label">
                            <span class="bg-info">{{ $item['date'] }}</span>
                        </div>
                        @endif
                        <div>
                            <i class="{{ $item['icon'] }}"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="far fa-clock"></i> {{ $item['details'] }}</span>
                                <h3 class="timeline-header">{{ $item['content'] }}</h3>
                            </div>
                        </div>
                        @php
                          $previousDate = $item['date'];
                        @endphp
                    @endforeach
                      <div>
                        <i class="far fa-clock bg-gray"></i>
                      </div>
                    </div>
                  @endif
                  <!-- /.row -->
                </div>
              </div>
            </div>
            <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
              <h3 class="text-primary">
                {{ __('general_content.task_trans_key') }}  #{{ $Task->id }} {{ $Task->service['label'] }} 
              </h3>
              @if( $Task->component_id && $Task->Component->drawing_file)
                <h5 class="text-secondary">
                  {{__('general_content.component_trans_key') }} : {{ $Task->Component->code }} <x-ButtonTextView route="{{ route('products.show', ['id' => $Task->component_id])}}" />
                  <!-- Drawing link -->
                  <a class="btn btn-info" href="{{ asset('drawing/'. $Task->Component->drawing_file) }}" target="_blank"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                </h5>
              @endif
              
              
              <hr>
              <div class="row">
                @if($Task->service->type == 1 or  $Task->service->type == 7)
                  @if($lastTaskActivities)
                    <div class="col-2 ">
                      <a class="btn btn-app bg-success @if($lastTaskActivities->type == 1 || $lastTaskActivities->type == 3) disabled @endif " wire:click="StartTimeTask({{$Task->id}})">
                        <i class="fas fa-{{ __('general_content.play_trans_key') }}"></i> {{ __('general_content.play_trans_key') }}
                      </a>
                    </div>
                    <div class="col-2 ">
                      <a class="btn btn-app bg-warning @if($lastTaskActivities->type == 2 || $lastTaskActivities->type == 3) disabled @endif " wire:click="EndTimeTask({{$Task->id}})">
                        <i class="fas fa-{{ __('general_content.pause_trans_key') }}"></i> {{ __('general_content.pause_trans_key') }}
                      </a>
                    </div>
                    <div class="col-2">
                      <a class="btn btn-app bg-danger @if($lastTaskActivities->type == 3) disabled @endif " wire:click="EndTask({{$Task->id}})">
                        <i class="fas fa-stop"></i> {{ __('general_content.end_trans_key') }}
                      </a>
                    </div>
                  @else
                    <div class="col-2 ">
                      <a class="btn btn-app bg-success" wire:click="StartTimeTask({{$Task->id}})">
                        <i class="fas fa-{{ __('general_content.play_trans_key') }}"></i> {{ __('general_content.play_trans_key') }}
                      </a>
                    </div>
                  @endif
                @else
                <div class="col-2 ">
                  <a class="btn btn-app bg-success" href="{{ route('purchases.request') }}" >{{ __('general_content.new_purchase_document_trans_key') }}</a>
                </div>
                @endif
              </div>
              <hr>
              <div class="row">
                <div class="col-12 ">
                  <form wire:submit.prevent="addGoodQt">
                      <label for="addGoodQt">{{ __('general_content.good_rejected_trans_key') }} :</label>
                      <div class="input-group input-group-sm">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-times"></i></span>
                          </div>
                          <input type="number" class="form-control @error('addGoodQt') is-invalid @enderror" id="addGoodQt" placeholder="{{ __('general_content.good_rejected_trans_key') }}" min="0" wire:model.live="addGoodQt">
                          <span class="input-group-append">
                            <button type="submit" class="btn btn-info btn-flat">Set</button>
                          </span>
                        </div>
                      @error('addGoodQt') <span class="text-danger">{{ $message }}<br/></span>@enderror
                  </form>
                </div>
              </div>
              <div class="row">
                <div class="col-12 ">
                  <form wire:submit.prevent="addRejectedQt">
                    <label for="addBadQt">{{ __('general_content.quantity_rejected_trans_key') }} :</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-times"></i></span>
                        </div>
                        <input type="number" class="form-control @error('addBadQt') is-invalid @enderror" id="addBadQt" placeholder="{{ __('general_content.quantity_rejected_trans_key') }}" min="0" wire:model.live="addBadQt">
                        <span class="input-group-append">
                          <button type="submit" class="btn btn-info btn-flat">Set</button>
                        </span>
                      </div>
                    @error('addBadQt') <span class="text-danger">{{ $message }}<br/></span>@enderror
                  </form>
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-2 text-muted">
                  <p class="text-sm">{{ __('general_content.end_date_trans_key') }}  
                    <b class="d-block">{{ $Task->getFormattedEndDateAttribute() }}</b>
                  </p>
                </div>
                <div class="col-2 text-muted">
                  <div class="form-group">
                    <label for="not_recalculate">Not Recalculate</label>
                    <input type="checkbox" id="not_recalculate" wire:model.live="not_recalculate" style=" display:flex; align-items:center;">
                </div>
                </div>
                <div class="col-8 text-muted">
                  <form wire:submit.prevent="updateDateTask">
                    <label for="end_date">{{ __('general_content.end_date_trans_key') }} :</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        </div>
                        <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" id="end_date"  wire:model.live="end_date">
                        <span class="input-group-append">
                          <button type="submit" class="btn btn-info btn-flat">Set</button>
                        </span>
                      </div>
                    @error('end_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                  </form>
                </div>
              </div>
              <hr>
              <div class="text-muted">
                <p class="text-sm">{{ __('general_content.statu_trans_key') }}  
                  <b class="d-block">{{ $Task->status['title'] }}</b>
                </p>
                <div class="row">
                  <div class="col-4">
                    <p class="text-sm">{{ __('general_content.cost_trans_key') }}
                      <b class="d-block">{{ $Task->unit_cost }} {{ $Factory->curency }}</b>
                    </p>
                  </div>
                  <div class="col-4">
                    <p class="text-sm">{{ __('general_content.margin_trans_key') }}  
                      <b class="d-block">{{ $Task->margin() }} %</b>
                    </p>
                  </div>
                  <div class="col-4">
                    <p class="text-sm">{{ __('general_content.price_trans_key') }}
                      <b class="d-block">{{ $Task->unit_price }} {{ $Factory->curency }}</b>
                    </p>
                  </div>
                </div>
                @if($Task->service->type == 1 or  $Task->service->type == 7)
                <div class="row">
                  <div class="col-4">
                    <p class="text-sm">{{ __('general_content.setting_time_trans_key') }}
                      <b class="d-block">{{ $Task->seting_time }} s</b>
                    </p>
                  </div>
                  <div class="col-4">
                    <p class="text-sm">{{ __('general_content.unit_time_trans_key') }}
                      <b class="d-block">{{ $Task->unit_time }} s</b>
                    </p>
                  </div>
                  <div class="col-4">
                    <p class="text-sm">{{ __('general_content.total_time_trans_key') }}
                      <b class="d-block">{{ $Task->TotalTime() }} h</b>
                    </p>
                  </div>
                </div>
                @endif
              </div>
              <div class="row">
                <div class="col-12">
                  <p class="text-sm">{{ __('general_content.progress_trans_key') }}
                    <b class="d-block">{{ $Task->progress() }} %</b>
                  </p>
                  
                  @if($Task->progress() > 100 )
                    <x-adminlte-progress theme="teal" value="100" animated/>
                  @else
                    <x-adminlte-progress theme="teal" value="{{ $Task->progress() }}" animated/>
                  @endif
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-12">
                  <a href="#" class="dropdown-item " wire:click="createNC({{$Task->id}}, {{$Task->OrderLines->order->companies_id}}, {{$Task->methods_services_id}})" ><span class="text-warning"><i class="fa fa-light fa-fw  fa-exclamation"></i>{{ __('general_content.new_non_conformitie_trans_key') }}</span></a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endempty
  
</div>
