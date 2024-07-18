@extends('adminlte::page')

@section('title', __('general_content.amdec_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.amdec_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

    @include('include.alert-result')
    <x-adminlte-card theme="primary" theme-mode="outline">
        <div class="table-responsive p-0">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>{{ __('general_content.product_trans_key') }}</th>
                    <th></th>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{ __('general_content.failure_mode_trans_key') }}</th>
                    <th>{{ __('general_content.effect_trans_key') }}</th>
                    <th>{{ __('general_content.cause_trans_key') }}</th>
                    <th>{{ __('general_content.severity_trans_key') }}</th>
                    <th>{{ __('general_content.occurrence_trans_key') }}</th>
                    <th></th>
                    <th>{{ __('general_content.detection_trans_key') }}</th>
                    <th>{{ __('RPN') }}</th>
                    <th>{{ __('general_content.current_control_trans_key') }}</th>
                    <th>{{ __('general_content.recommended_action_trans_key') }}</th>
                    <th>{{ __('general_content.created_at_trans_key') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($QualityAmdecs as $QualityAmdec)
                    <tr>
                        <td>{{ $QualityAmdec->Product->label }} </td>
                        <td><x-ButtonTextView route="{{ route('products.show', ['id' => $QualityAmdec->product_id])}}" /></td>
                        <td><img src="{{ Avatar::create($QualityAmdec->UserManagement['name'])->toBase64() }}" /></td>
                        <td>{{ $QualityAmdec->failure_mode }}</td>
                        <td>{{ $QualityAmdec->effect }}</td>
                        <td>{{ $QualityAmdec->cause }}</td>
                        <td>{{ $QualityAmdec->severity }}</td>
                        <td>{{ $QualityAmdec->occurrence }}</td>
                        <td class="
                            @if ($QualityAmdec->categorizeCriticality() === 'High')
                                bg-danger
                            @elseif ($QualityAmdec->categorizeCriticality() === 'Medium')
                                bg-warning
                            @elseif ($QualityAmdec->categorizeCriticality() === 'Low')
                                bg-success
                            @endif
                        ">
                            {{ $QualityAmdec->categorizeCriticality() }}
                        </td>
                        <td>{{ $QualityAmdec->detection }}</td>
                        <td>{{ $QualityAmdec->calculateRPN() }}</td>
                        <td>{{ $QualityAmdec->current_control }}</td>
                        <td>{{ $QualityAmdec->recommended_action }}</td>
                        <td>{{ $QualityAmdec->created_at->diffForHumans() }}</td>
                        <td class="py-0 align-middle">
                            <!-- Button Modal -->
                            <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityAmdecView{{ $QualityAmdec->id }}">
                                <i class="fa fa-lg fa-fw fa-eye"></i>
                            </button>
                            <!-- Modal {{ $QualityAmdec->id }} -->
                            <x-adminlte-modal id="QualityAmdecView{{ $QualityAmdec->id }}" title="Info {{ $QualityAmdec->failure_mode }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                                <div class="row">
                                    <strong>{{ __('general_content.failure_mode_trans_key') }} : </strong>
                                    {{ $QualityAmdec->failure_mode }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.effect_trans_key') }} :</strong>
                                    {{ $QualityAmdec->effect }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.cause_trans_key') }} :</strong>
                                    {{ $QualityAmdec->cause }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.severity_trans_key') }} :</strong>
                                    {{ $QualityAmdec->severity }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.occurrence_trans_key') }} :</strong>
                                    {{ $QualityAmdec->occurrence }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.detection_trans_key') }} :</strong>
                                    {{ $QualityAmdec->detection }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.current_control_trans_key') }} :</strong>
                                    {{ $QualityAmdec->current_control }}
                                </div>
                                <div class="row">
                                    <strong>{{ __('general_content.recommended_action_trans_key') }} :</strong>
                                    {{ $QualityAmdec->recommended_action }}
                                </div>
                            </x-adminlte-modal>
        
                            <!-- Button Modal -->
                            <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityAmdec{{ $QualityAmdec->id }}">
                                <i class="fa fa-lg fa-fw fa-edit"></i>
                            </button>
                            <!-- Modal {{ $QualityAmdec->id }} -->
                            <x-adminlte-modal id="QualityAmdec{{ $QualityAmdec->id }}" title="Update {{ $QualityAmdec->failure_mode }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                <form method="POST" action="{{ route('quality.amdec.update', ['id' => $QualityAmdec->id]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="failure_mode">{{ __('general_content.failure_mode_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="failure_mode" id="failure_mode" placeholder="{{ __('general_content.failure_mode_trans_key') }}" value="{{ $QualityAmdec->failure_mode }}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="effect">{{ __('general_content.effect_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="effect" id="effect" placeholder="{{ __('general_content.effect_trans_key') }}" value="{{ $QualityAmdec->effect }}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="cause">{{ __('general_content.cause_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="cause" id="cause" placeholder="{{ __('general_content.cause_trans_key') }}" value="{{ $QualityAmdec->cause }}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="severity">{{ __('general_content.severity_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                                </div>
                                                <input type="number" class="form-control" name="severity" id="severity" placeholder="{{ __('general_content.severity_trans_key') }}" value="{{ $QualityAmdec->severity }}" min="1" max="10">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="occurrence">{{ __('general_content.occurrence_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                                </div>
                                                <input type="number" class="form-control" name="occurrence" id="occurrence" placeholder="{{ __('general_content.occurrence_trans_key') }}" value="{{ $QualityAmdec->occurrence }}" min="1" max="10">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="detection">{{ __('general_content.detection_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                                </div>
                                                <input type="number" class="form-control" name="detection" id="detection" placeholder="{{ __('general_content.detection_trans_key') }}" value="{{ $QualityAmdec->detection }}" min="1" max="10">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="current_control">{{ __('general_content.current_control_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="current_control" id="current_control" placeholder="{{ __('general_content.current_control_trans_key') }}" value="{{ $QualityAmdec->current_control }}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="recommended_action">{{ __('general_content.recommended_action_trans_key') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="recommended_action" id="recommended_action" placeholder="{{ __('general_content.recommended_action_trans_key') }}" value="{{ $QualityAmdec->recommended_action }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                    </div>
                                </form>
                            </x-adminlte-modal>
                        </td>
                    </tr>
                @empty
                    <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"/>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <th>{{ __('general_content.product_trans_key') }}</th>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th></th>
                    <th>{{ __('general_content.failure_mode_trans_key') }}</th>
                    <th>{{ __('general_content.effect_trans_key') }}</th>
                    <th>{{ __('general_content.cause_trans_key') }}</th>
                    <th>{{ __('general_content.severity_trans_key') }}</th>
                    <th>{{ __('general_content.occurrence_trans_key') }}</th>
                    <th></th>
                    <th>{{ __('general_content.detection_trans_key') }}</th>
                    <th>{{ __('RPN') }}</th>
                    <th>{{ __('general_content.current_control_trans_key') }}</th>
                    <th>{{ __('general_content.recommended_action_trans_key') }}</th>
                    <th>{{ __('general_content.created_at_trans_key') }}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
            <!-- /.row -->
        </div>
        <div class="row">
            <div class="form-group col-md-5">
                {{ $QualityAmdecs->links() }}
            </div>
            <!-- /.row -->
        </div>
    </x-adminlte-card>

    <form method="POST" action="{{ route('quality.amdec.create') }}">
        <x-adminlte-card title="{{ __('general_content.new_amdec_trans_key') }}" theme="secondary" maximizable>
            @csrf

            
            <div class="form-row">
                <div class="form-group col-md-4">
                    <x-adminlte-select2 name="product_id" id="product_id" label="{{ __('general_content.product_trans_key') }}" label-class="text-lightblue"
                        igroup-size="lg" data-placeholder="Select an product...">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-barcode"></i>
                            </div>
                        </x-slot>
                        @foreach ($ProductSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                        @endforeach
                    </x-adminlte-select2>
                </div>
                <div class="form-group col-md-4">
                    <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                    <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <select class="form-control" name="user_id" id="user_id">
                        @foreach ($userSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="failure_mode">{{ __('general_content.failure_mode_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation-circle"></i></span>
                        </div>
                        <input type="text" class="form-control" name="failure_mode" id="failure_mode" placeholder="{{ __('general_content.failure_mode_trans_key') }}">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="effect">{{ __('general_content.effect_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                        </div>
                        <input type="text" class="form-control" name="effect" id="effect" placeholder="{{ __('general_content.effect_trans_key') }}">
                    </div>
                </div>
                
                <div class="form-group col-md-4">
                    <label for="cause">{{ __('general_content.cause_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-question-circle"></i></span>
                        </div>
                        <input type="text" class="form-control" name="cause" id="cause" placeholder="{{ __('general_content.cause_trans_key') }}">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="severity">{{ __('general_content.severity_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                        </div>
                        <input type="number" class="form-control" name="severity" id="severity" placeholder="{{ __('general_content.severity_trans_key') }}" min="1" max="10">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="occurrence">{{ __('general_content.occurrence_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                        <input type="number" class="form-control" name="occurrence" id="occurrence" placeholder="{{ __('general_content.occurrence_trans_key') }}" min="1" max="10">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="detection">{{ __('general_content.detection_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-eye"></i></span>
                        </div>
                        <input type="number" class="form-control" name="detection" id="detection" placeholder="{{ __('general_content.detection_trans_key') }}" min="1" max="10">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                <label for="current_control">{{ __('general_content.current_control_trans_key') }}</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                    </div>
                    <input type="text" class="form-control" name="current_control" id="current_control" placeholder="{{ __('general_content.current_control_trans_key') }}">
                </div>
            </div>
                <div class="form-group col-md-8">
                    <label for="recommended_action">{{ __('general_content.recommended_action_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="recommended_action" id="recommended_action" placeholder="{{ __('general_content.recommended_action_trans_key') }}"></textarea>
                </div>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            </x-slot>
        </x-adminlte-card>
    </form>
    
@stop

@section('css')
@stop

@section('js')
@stop