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
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-primary">
                <div class="card-header">{{ __('general_content.current_count_task_trans_key') }}</div>
                <div class="card-body">
                    <h3 class="card-title">{{ $tasksInProgress }}</h3>
                </div>
            </div>
        </div>
  
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-info">
                <div class="card-header">{{ __('general_content.goal_task_trans_key') }}</div>
                <div class="card-body">
                  <p class="card-text">{{ __('general_content.open_trans_key') }} : <strong>{{ $tasksOpen }}</strong></p>
                    <p class="card-text">{{ __('general_content.suspended_trans_key') }} : <strong>{{ $tasksPending }}</strong></p>
                    <p class="card-text">{{ __('general_content.supplied_trans_key') }} : <strong>{{ $tasksOngoing }}</strong></p>
                    <p class="card-text">{{ __('general_content.finished_trans_key') }} : <strong>{{ $tasksCompleted }}</strong></p>
                </div>
            </div>
        </div>
  
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-success">
                <div class="card-header">{{ __('general_content.average_time_task_trans_key') }}</div>
                <div class="card-body">
                    <h3 class="card-title">{{ round($averageProcessingTime , 2) }} h</h3>
                </div>
            </div>
        </div>
    </div>
  
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">{{ __('general_content.user_productivity_trans_key') }}</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.user_trans_key') }}</th>
                                <th>{{ __('general_content.task_count_trans_key') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($userProductivity as $user)
                              @if($user->tasks_count > 0)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->tasks_count }}</td>
                                </tr>
                              @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">{{ __('general_content.total_hours_per_resource_trans_key') }}</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.ressource_trans_key') }}</th>
                                <th>{{ __('general_content.total_time_trans_key') }} h</th>
                            </tr>
                        </thead>
                        <tbody>
                          @foreach($resourceHours as $resourceName => $totalTime)
                          <tr>
                              <td>{{ $resourceName }}</td>
                              <td>{{ round($totalTime, 2) }} h</td>
                          </tr>
                      @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  @else
    @if(is_null($Task->order_lines_id))
      <x-adminlte-alert theme="info" title="Info">{{ __('general_content.quote_task_trans_key') }}</x-adminlte-alert>
    @else
      <div class="row">
        <div class="col-6 mb-3">
          @if ($previousTask)
            <button wire:click="goToTask({{ $previousTask->id }})" class="btn btn-primary btn-lg btn-block">
                <i class="fas fa-arrow-left"></i> {{ $previousTask->ordre }} - {{ $previousTask->label }}
            </button>
          @endif
        </div>
        <div class="col-6 mb-3">
          @if ($nextTask)
            <button wire:click="goToTask({{ $nextTask->id }})" class="btn btn-primary btn-lg btn-block">
              {{ $nextTask->ordre }} - {{ $nextTask->label }} <i class="fas fa-arrow-right"></i>
            </button>
          @endif
        </div>
      </div>
      <x-adminlte-card title=" #{{ $Task->id }} {{ __('general_content.task_detail_trans_key') }}  {{ $Task->label }}" theme="teal" maximizable>
          @include('include.alert-result')
          <div class="row">
            <div class="col-md-2">
              <h4>{{ __('general_content.informations_trans_key') }}</h4> 
              <div class="row md-2">
                <x-OrderButton id="{{ $Task->OrderLines->orders_id }}" code="{{ $Task->OrderLines->order->code }} #{{ __('general_content.line_trans_key') }} {{ $Task->OrderLines->label }}"  /> 
                  @if( $Task->OrderLines->OrderLineDetails->picture)
                    <a class="btn btn-info" href="{{ asset('images/order-lines/'. $Task->OrderLines->OrderLineDetails->picture) }}" target="_blank"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                  @endif
              </div>
              <div class="row md-2">
                <p>
                  @if( $Task->OrderLines->product_id && $Task->OrderLines->product->drawing_file)
                  <a class="btn btn-info" href="{{ asset('drawing/'. $Task->OrderLines->product->drawing_file) }}" target="_blank"><i class="fas fa-barcode "></i>{{ $Task->OrderLines->product->label }}</a>
                  @endif
                </p>
              </div>
              @if($Task->service->picture )
              <div class="row">
                  <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'. $Task->service->picture) }}">
              </div>
              @endif
              @if($Task->service->type == 1)
              <div class="row">
                <x-adminlte-info-box title="{{ __('general_content.total_time_trans_key') }}" text="{{ $Task->getTotalLogTime() }} h" icon="fa fa-stopwatch" theme="warning"/>
              </div>
              @endif
              <div class="row">
                <x-adminlte-info-box title="{{ __('general_content.finish_part_qty_trans_key') }}" text="{{ $Task->getTotalLogGoodQt() }} item(s)" icon="fa fa-database" theme="success"/>
              </div>
              <div class="row">
                <x-adminlte-info-box title="{{ __('general_content.bad_part_qty_trans_key') }}" text="{{ $Task->getTotalLogBadQt() }} item(s)" icon="fa fa-arrow-down" theme="danger "/>
              </div>
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
                @if($Task->service->type == 1)
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
                    <x-adminlte-progress theme="teal" value="100" animated with-label/>
                  @else
                    <x-adminlte-progress theme="teal" value="{{ $Task->progress() }}" animated with-label/>
                  @endif
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="row">
                <div class="col-12">
                  <h4>{{ __('general_content.logs_activity_trans_key') }}</h4>   
                  @if($Task->service->type == 1 )
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
            <div class="col-md-4">
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
              <div class="row">
                @if($Task->service->type == 1 )
                  @if($lastTaskActivities)
                    <div class="form-group col-md-2 ">
                      <a class="btn btn-app bg-success @if($lastTaskActivities->type == 1 || $lastTaskActivities->type == 3) disabled @endif " wire:click="StartTimeTask({{$Task->id}})">
                        <i class="fas fa-{{ __('general_content.play_trans_key') }}"></i> {{ __('general_content.play_trans_key') }}
                      </a>
                    </div>
                    <div class="form-group col-md-2 ">
                      <a class="btn btn-app bg-warning @if($lastTaskActivities->type == 2 || $lastTaskActivities->type == 3) disabled @endif " wire:click="EndTimeTask({{$Task->id}})">
                        <i class="fas fa-{{ __('general_content.pause_trans_key') }}"></i> {{ __('general_content.pause_trans_key') }}
                      </a>
                    </div>
                    <div class="form-group col-md-2 ">
                      <a class="btn btn-app bg-danger @if($lastTaskActivities->type == 3) disabled @endif " wire:click="EndTask({{$Task->id}})">
                        <i class="fas fa-stop"></i> {{ __('general_content.end_trans_key') }}
                      </a>
                    </div>
                  @else
                  <div class="form-group col-md-2 ">
                      <a class="btn btn-app bg-success" wire:click="StartTimeTask({{$Task->id}})">
                        <i class="fas fa-{{ __('general_content.play_trans_key') }}"></i> {{ __('general_content.play_trans_key') }}
                      </a>
                    </div>
                  @endif
                @else
                <div class="form-group col-md-2 ">
                  <a class="btn btn-app bg-success" href="{{ route('purchases.request') }}" >{{ __('general_content.new_purchase_document_trans_key') }}</a>
                </div>
                
                <div class="form-group col-md-2 ">
                  <a class="btn btn-app bg-danger " wire:click="EndTask({{$Task->id}})">
                    <i class="fas fa-stop"></i> {{ __('general_content.end_trans_key') }}
                  </a>
                </div>
                @endif
              </div>
              @if( $Task->component_id)
              <hr>
              <div class="row">
                <div class="col-12 ">
                  <form wire:submit.prevent="addGoodQtFromStock({{ $Task->component_id }},{{ $Task->id }})">
                    <label for="addGoodQt">{{ __('general_content.remove_from_stock_trans_key') }} :</label>
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
              @endif
              <hr>
              <div class="row">
                <div class="col-12 ">
                  <form wire:submit.prevent="addGoodQtFromUser">
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
                <div class="col-md-2 text-muted">
                  <p class="text-sm">{{ __('general_content.end_date_trans_key') }}  
                    <b class="d-block">{{ $Task->getFormattedEndDateAttribute() }}</b>
                  </p>
                </div>
                <div class="col-md-2 text-muted">
                  <div class="form-group">
                    <label for="not_recalculate">Not Recalculate</label>
                    <input type="checkbox" id="not_recalculate" wire:model.live="not_recalculate" style=" display:flex; align-items:center;">
                </div>
                </div>
                <div class="col-md-8 text-muted">
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
              <div class="row">
                <div class="col-12">
                  <a href="#" class="dropdown-item " wire:click="createNC({{$Task->id}}, {{$Task->OrderLines->order->companies_id}}, {{$Task->methods_services_id}})" ><span class="text-warning"><i class="fa fa-light fa-fw  fa-exclamation"></i>{{ __('general_content.new_non_conformitie_trans_key') }}</span></a>
                </div>
              </div>
            </div>
          </div>
      </x-adminlte-card>

      @if($StockLocationsProducts && ($Task->service->type != 1 || $Task->service->type != 7))
        <x-adminlte-card title="{{ __('general_content.stock_location_product_list_trans_key') }}" theme="primary" maximizable>
          @include('include.table-stock-locations-products')
        </x-adminlte-card>
      @endif
    @endif
  @endempty
  
</div>
