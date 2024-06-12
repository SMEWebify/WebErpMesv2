@extends('adminlte::page')

@section('title', __('general_content.human_resources_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.human_resources_trans_key') }}</h1>
@stop

@section('content')
@include('include.alert-result')
<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#user" data-toggle="tab">{{ __('general_content.users_list_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#ExpenseValidate" data-toggle="tab">{{ __('general_content.expense_validation_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#ExpenseCat" data-toggle="tab">{{ __('general_content.expense_categories_trans_key') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="user">
                <div class="table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('general_content.name_trans_key') }}</th>
                                <th>{{ __('general_content.email_trans_key') }}</th>
                                <th>{{ __('general_content.employment_statu_trans_key') }}</th>
                                <th>{{ __('general_content.job_title_trans_key') }}</th>
                                <th>{{ __('general_content.role_trans_key') }}</th>
                                <th>{{ __('general_content.gender_trans_key') }}</th>
                                <th>{{ __('general_content.born_date_trans_key') }}</th>
                                <th>{{__('general_content.status_trans_key') }}</th>
                                <th></th>
                                <th>{{__('general_content.created_at_trans_key') }}</th>
                                <th>{{__('general_content.blocked_unti_trans_key') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Users as $User)
                            <tr>
                                <td>
                                    @if(Cache::has('user-is-online-' . $User->id))
                                        <span class="badge badge-success">{{__('general_content.online_trans_key') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{__('general_content.offline_trans_key') }}</span>
                                    @endif 
                                </td>
                                <td>
                                    {{ $User->name }}</td>
                                <td>{{ $User->email }}</td>
                                <td>
                                    @if(1 == $User->employment_status )   <span class="badge badge-danger">{{__('general_content.undefined_trans_key') }}</span>@endif
                                    @if(2 == $User->employment_status )  <span class="badge badge-success">{{__('general_content.worker_trans_key') }}</span>@endif
                                    @if(3 == $User->employment_status )  <span class="badge badge-warning">{{__('general_content.employee_trans_key') }}</span>@endif
                                    @if(4 == $User->employment_status )  <span class="badge badge-info">{{__('general_content.self_employed_trans_key') }}</span>@endif
                                </td>
                                <td>{{ $User->job_title ?? __('general_content.undefined_trans_key')}}</td>
                                <td>
                                    @if(!empty($User->getRoleNames()))
                                    @foreach($User->getRoleNames() as $v)
                                        <label class="badge badge-success">{{ $v }}</label>
                                    @endforeach
                                    @endif
                                </td>
                                <td>
                                    @if(1 == $User->gender ) {{__('general_content.male_trans_key') }} 
                                    @elseif(2 == $User->gender ) {{__('general_content.female_trans_key') }}
                                    @elseif(3 == $User->gender ) {{__('general_content.other_trans_key') }} 
                                    @else {{__('general_content.undefined_trans_key') }}
                                    @endif
                                </td>
                                <td>{{ $User->born_date ?? __('general_content.undefined_trans_key') }}</td>
                                <td>
                                    @if(1 == $User->statu )  <span class="badge badge-success">{{__('general_content.active_trans_key') }}</span>@endif
                                    @if(2 == $User->statu )  <span class="badge badge-danger">{{__('general_content.inactive_trans_key') }}</span>@endif
                                </td>
                                <td>
                                    <x-ButtonTextView route="{{ route('human.resources.show.user', ['id' => $User->id])}}" />
                                </td>
                                <td>{{ $User->GetPrettyCreatedAttribute() }}</td>
                                <td>{{ $User->banned_until }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th>{{ __('general_content.name_trans_key') }}</th>
                                <th>{{ __('general_content.email_trans_key') }}</th>
                                <th>{{ __('general_content.employment_statu_trans_key') }}</th>
                                <th>{{ __('general_content.job_title_trans_key') }}</th>
                                <th>{{ __('general_content.role_trans_key') }}</th>
                                <th>{{ __('general_content.gender_trans_key') }}</th>
                                <th>{{ __('general_content.born_date_trans_key') }}</th>
                                <th>{{__('general_content.status_trans_key') }}</th>
                                <th></th>
                                <th>{{__('general_content.created_at_trans_key') }}</th>
                                <th>{{__('general_content.blocked_unti_trans_key') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- /.table-responsive -->
                <div class="row">
                    <div class="col-5">
                        {{ $Users->links() }}
                    </div>
                </div>
                <!-- /.row -->
            </div>
            <!-- /.tab-pane active -->
            <div class="tab-pane " id="ExpenseValidate">
                <div class="table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{__('general_content.date_trans_key') }}</th>
                            <th></th>
                            <th>{{__('general_content.amount_trans_key') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($ExpenseReports as $ExpenseReport)
                        <tr>
                            <td>{{ $ExpenseReport->label }}</td>
                            <td>
                                @if($ExpenseReport->status  == 1){{__('general_content.done_trans_key') }} @endif
                                @if($ExpenseReport->status  == 2){{__('general_content.to_submit_trans_key') }} @endif
                                @if($ExpenseReport->status  == 3){{__('general_content.submitted_trans_key') }} @endif
                                @if($ExpenseReport->status  == 4){{__('general_content.returned_trans_key') }} @endif
                                @if($ExpenseReport->status  == 5){{__('general_content.approved_trans_key') }} @endif
                            </td>
                            <td>{{ $ExpenseReport->date }}</td>
                            <td>{{ $ExpenseReport->expenses()->count() }}</td>
                            <td>{{ $ExpenseReport->getTotalAmountAttribute() }} {{ $Factory->curency }}</td>
                            <td class=" py-0 align-middle">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('human.resources.show.expense', ['id' => $ExpenseReport->id])}}" class="btn bg-primary"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                                </div>
                                <!-- Button Modal -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn bg-teal " data-toggle="modal" data-target="#ExpenseReport{{ $ExpenseReport->id }}">
                                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                                    </button>
                                </div>
                                <!-- Modal {{ $ExpenseReport->id }} -->
                                <x-adminlte-modal id="ExpenseReport{{ $ExpenseReport->id }}" title="Update {{ $ExpenseReport->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                    <form method="POST" action="{{ route('human.resources.valide.expense.report') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="card-body">
                                            <input type="hidden"  name="id"  id="id" value="{{ $ExpenseReport->id }}">
                                            <input type="hidden"  name="label"  id="label" value="{{ $ExpenseReport->label }}">
                                            <input type="hidden"  name="date"  id="date" value="{{ $ExpenseReport->date }}">
                                            
                                            <div class="form-group">
                                                <label for="status">{{ __('general_content.status_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                    </div>
                                                    <select class="form-control" name="status" id="status">
                                                        <option value="4" @if($ExpenseReport->status == 4) Selected @endif>{{__('general_content.returned_trans_key') }}</option>
                                                        <option value="5" @if($ExpenseReport->status == 5) Selected @endif>{{__('general_content.approved_trans_key') }}</option>
                                                    </select>
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
                            <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{{__('general_content.label_trans_key') }}</th>
                                <th>{{__('general_content.status_trans_key') }}</th>
                                <th>{{__('general_content.date_trans_key') }}</th>
                                <th></th>
                                <th>{{__('general_content.amount_trans_key') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- /.row -->
            </div>
            <!-- /.tab-pane active -->
            <div class="tab-pane " id="ExpenseCat">
                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-card title="{{ __('general_content.expense_categories_trans_key') }}" theme="primary" maximizable>
                            <div class="table-responsive p-0">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{__('general_content.label_trans_key') }}</th>
                                            <th>{{__('general_content.description_trans_key') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($UserExpenseCategories as $UserExpenseCategory)
                                        <tr>
                                            <td>{{ $UserExpenseCategory->label }}</td>
                                            <td>{{ $UserExpenseCategory->description }}</td>
                                            <td class=" py-0 align-middle">
                                                <!-- Button Modal -->
                                                <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#UserExpenseCategory{{ $UserExpenseCategory->id }}">
                                                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                                                </button>
                                                <!-- Modal {{ $UserExpenseCategory->id }} -->
                                                <x-adminlte-modal id="UserExpenseCategory{{ $UserExpenseCategory->id }}" title="Update {{ $UserExpenseCategory->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                                    <form method="POST" action="{{ route('human.resources.update.expense.category', ['id' => $UserExpenseCategory->id]) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="card-body">
                                                            <div class="form-group">
                                                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                    </div>
                                                                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $UserExpenseCategory->label }}">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="description">{{__('general_content.description_trans_key') }}</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                    </div>
                                                                    <input type="text" class="form-control" name="description"  id="description" placeholder="{{__('general_content.description_trans_key') }}" value="{{ $UserExpenseCategory->description }}">
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
                                            <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>{{__('general_content.label_trans_key') }}</th>
                                            <th>{{__('general_content.description_trans_key') }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </x-adminlte-card>
                    <!-- /.card secondary -->
                    </div>
                    <div class="col-md-6 card-secondary">
                        <x-adminlte-card title="{{ __('general_content.new_expense_categories_trans_key') }}" theme="secondary" maximizable>
                            <form  method="POST" action="{{ route('human.resources.create.expense.category') }}" class="form-horizontal">
                                @csrf
                                <div class="form-group">
                                    <label for="label">{{__('general_content.label_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">{{__('general_content.description_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="description"  id="description" placeholder="{{__('general_content.description_trans_key') }}">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </div>
                            </form>
                        </x-adminlte-card>
                    </div>
                    <!-- /.card secondary -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.tab-pane active -->
        </div>
        <!-- /.tab-content -->
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('plugins.BootstrapSwitch', true)

@section('css')
@stop

@section('js')
@stop