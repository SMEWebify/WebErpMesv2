<div>
    
  <div class="card-body" >
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
        </div>
        <input type="text" class="form-control" wire:model="search" placeholder="Search Task">
    </div>
  </div>

  @empty($Task)
  <h1> No task call </h1>
  @else
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">TASK #{{ $Task->id }} Detail</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
            <i class="fas fa-minus"></i>
          </button>
          <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <div class="card-body">
        @include('include.alert-result')
        <div class="row">
          <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
            <div class="row">
              <div class="col-12 col-sm-4">
                <x-adminlte-info-box title="Total time" text="{{ $Task->getTotalLogTime() }} h" icon="fa fa-stopwatch" theme="warning"/>
              </div>
              <div class="col-12 col-sm-4">
                <x-adminlte-info-box title="Finish part Qty" text=" item(s)" icon="fa fa-database" theme="success"/>
              </div>
              <div class="col-12 col-sm-4">
                <x-adminlte-info-box title="Bad part qty" text=" item(s)" icon="fa fa-arrow-down" theme="danger "/>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <h4>Logs Activity</h4> 
                @forelse ($taskActivities as $taskActivitie)
                <div class="post">
                    <span class="username">
                      {{ $taskActivitie->user->name }}
                    </span>
                    <span class="description">
                      @if($taskActivitie->type == 1)
                      Task set to Start time
                    @elseif ($taskActivitie->type == 2)
                      Task set to End time
                    @elseif ($taskActivitie->type == 3)
                      Task set to Finish Task
                    @elseif ($taskActivitie->type == 4)
                      Declare a finished part quantity
                    @elseif ($taskActivitie->type == 5)
                      Declare a refuse  part quantity
                    @endif
                    - {{ $taskActivitie->GetPrettyCreatedAttribute() }} 
                  </span>
                </div>
                @empty
                <div class="post">
                  <p>
                    No activities.
                  </p>
                </div>
                @endforelse
                <!-- /.row -->
              </div>
            </div>
          </div>
          <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
            <h3 class="text-primary">
              <x-OrderButton id="{{ $Task->OrderLines->orders_id }}" code="{{ $Task->OrderLines->order->code }}"  />
                TASK #{{ $Task->id }} {{ $Task->service['label'] }}</h3>
            <div class="row">
              @if($Task->service->picture )
              <div class="col-2 ">
                <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'. $Task->service->picture) }}">
              </div>
              @endif
              @if($lastTaskActivities)
              <div class="col-2 ">
                <a class="btn btn-app bg-success @if($lastTaskActivities->type == 1 || $lastTaskActivities->type == 3) disabled @endif " wire:click="StartTimeTask({{$Task->id}})">
                  <i class="fas fa-play"></i> Play
                </a>
              </div>
              <div class="col-2 ">
                <a class="btn btn-app bg-warning @if($lastTaskActivities->type == 2 || $lastTaskActivities->type == 3) disabled @endif " wire:click="EndTimeTask({{$Task->id}})">
                  <i class="fas fa-pause"></i> Pause
                </a>
              </div>
              <div class="col-2">
                <a class="btn btn-app bg-danger @if($lastTaskActivities->type == 3) disabled @endif " wire:click="EndTask({{$Task->id}})">
                  <i class="fas fa-stop"></i> End
                </a>
              </div>
            @else
            <div class="col-2 ">
              <a class="btn btn-app bg-success" wire:click="StartTimeTask({{$Task->id}})">
                <i class="fas fa-play"></i> Play
              </a>
            </div>
            @endif
            </div>
            <div class="text-muted">
              
              <p class="text-sm">Statu
                <b class="d-block">{{ $Task->status['title'] }}</b>
              </p>
              <p class="text-sm">Service
                <b class="d-block">{{ $Task->service['label'] }}</b>
              </p>
              <p class="text-sm">Unit cost
                <b class="d-block">{{ $Task->unit_cost }} {{ $Factory->curency }}</b>
              </p>
              <p class="text-sm">Unit price
                <b class="d-block">{{ $Task->unit_price }} {{ $Factory->curency }}</b>
              </p>
              <p class="text-sm">Margin
                <b class="d-block">{{ $Task->margin() }} %</b>
              </p>
              <p class="text-sm">setting time
                <b class="d-block">{{ $Task->seting_time }} s</b>
              </p>
              <p class="text-sm">Unit time
                <b class="d-block">{{ $Task->unit_time }} s</b>
              </p>
              <p class="text-sm">Total time
                <b class="d-block">{{ $Task->TotalTime() }} h</b>
              </p>
            </div>
            <div class="row">
              <div class="col-12">
                <x-adminlte-progress theme="teal" value="{{ $Task->progress() }}" animated with-label/>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
      
  @endempty
  
</div>
