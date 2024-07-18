@extends('adminlte::page')

@section('title', __('general_content.roles_and_permissions_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.roles_and_permissions_trans_key') }}</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills"><li class="nav-item"><a class="nav-link active" href="#Role" data-toggle="tab">{{ __('general_content.roles_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#Permissions" data-toggle="tab">{{ __('general_content.permissions_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#RoleInPermissions" data-toggle="tab">{{ __('general_content.role_in_permissions_trans_key') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="Role">
                @include('include.alert-result')
                <form method="POST" action="{{ route('admin.factory.role.store') }}" enctype="multipart/form-data">
                    @csrf
                    <x-adminlte-card title="{{ __('general_content.make_new_role_trans_key') }}" theme="primary" maximizable>
                        <div class="row">
                            <label for="label" >{{__('general_content.role_trans_key') }} :</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="Text" class="form-control" id="name" name="name" placeholder="{{__('general_content.role_trans_key') }}" >
                            </div>
                        </div>
                        <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                        </x-slot>
                    </x-adminlte-card>
                </form>
                
                <x-adminlte-card title="{{ __('general_content.role_trans_key') }}" theme="secondary" maximizable>
                    <div class="table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="text-capitalize">{{__('general_content.role_trans_key') }}</th>
                                    <th class="text-capitalize">{{__('general_content.permissions_trans_key') }}</th>
                                    <th class="text-capitalize">{{ __('general_content.created_at_trans_key') }}</th>
                                    <th class="text-capitalize text-right" >{{ __('general_content.action_trans_key') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($Roles as $Role)
                                <tr>
                                    <td>{{ $Role->name }}</td>
                                    <td>
                                        <div class="row">
                                            @forelse ($Role->permissions->pluck('name') as $RolePermission)
                                            <div class="col-2">
                                                <button type="button" class="btn btn-block btn-outline-success  btn-sm disabled">{{ $RolePermission}}</button>
                                            </div>
                                            @empty
                                            <button type="button" class="btn btn-block btn-outline-danger  btn-sm disabled">{{__('general_content.no_permissions_trans_key') }}</button>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>{{ $Role->created_at }}</td>
                                    <td class="text-right">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Role{{ $Role->id }}">
                                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                                        </button>
                                        <!-- Modal {{ $Role->id }} -->
                                        <x-adminlte-modal id="Role{{ $Role->id }}" title="Update {{ $Role->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                            <form method="POST" action="{{ route('admin.factory.role.update', ['id' => $Role->id]) }}" enctype="multipart/form-data">
                                                @csrf
                                                
                                                <div class="row">
                                                    <label for="label" >{{__('general_content.role_trans_key') }} :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                        </div>
                                                        <input type="Text" class="form-control" id="name" name="name" placeholder="Role Name" value="{{ $Role->name }}">
                                                    </div>
                                                </div>
                                                @forelse ($Permissions as $Permission)
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="custom-control custom-checkbox">
                                                            <input class="custom-control-input" type="checkbox" name="permission[]" id="Role{{ $Role->id }}checkDefault{{ $Permission->id }}" @if($Role->permissions->contains($Permission)) checked @endif value="{{ $Permission->id }}">
                                                            <label for="Role{{ $Role->id }}checkDefault{{ $Permission->id }}" class="custom-control-label">{{ $Permission->name }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                @empty
                                                <div class="row">
                                                    <p>{{ __('general_content.no_permissions_trans_key') }}</p>
                                                </div>
                                                @endforelse
                                                <div class="card-footer">
                                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                                </div>
                                            </form>
                                        </x-adminlte-modal>
                                        <x-ButtonTextDelete route="{{ route('admin.factory.role.destroy', ['role' => $Role->id]) }}" />
                                    </td>
                                </tr>
                                @empty
                                    <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-capitalize">{{__('general_content.role_trans_key') }}</th>
                                    <th class="text-capitalize">{{__('general_content.permissions_trans_key') }}</th>
                                    <th class="text-capitalize">{{ __('general_content.created_at_trans_key') }}</th>
                                    <th class="text-capitalize text-right" >{{ __('general_content.action_trans_key') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
            <div class="tab-pane " id="Permissions">
                @include('include.alert-result')
                <form method="POST" action="{{ route('admin.factory.permissions.store') }}" enctype="multipart/form-data">
                    @csrf
                    <x-adminlte-card title="{{ __('general_content.make_new_permissions_trans_key') }}" theme="primary" maximizable>
                        <div class="row">
                            <!-- /.form-group -->
                            <div class="form-group">
                                <label for="role">{{ __('general_content.permission_trans_key') }} :</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <select class="form-control" name="name" id="name">
                                        <option value="companies-menu">companies-menu</option>
                                        <option value="leads-menu">leads-menu</option>
                                        <option value="opportunities-menu">opportunities-menu</option>
                                        <option value="quotes-menu">quotes-menu</option>
                                        <option value="orders-menu">orders-menu</option>
                                        <option value="scheduling-menu">scheduling-menu</option>
                                        <option value="deliverys-menu">deliverys-menu</option>
                                        <option value="invoices-menu">invoices-menu</option>
                                        <option value="products-menu">products-menu</option>
                                        <option value="purchases-menu">purchases-menu</option>
                                        <option value="quality-menu">quality-menu</option>
                                        <option value="settings-time-menu">settings-time-menu</option>
                                        <option value="methods-menu">methods-menu</option>
                                        <option value="accounting-menu">accounting-menu</option>
                                        <option value="human-resources-menu">human-resources-menu</option>
                                        <option value="your-company-menu">your-company-menu</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                        </x-slot>
                    </x-adminlte-card>
                </form>

                <x-adminlte-card title="{{ __('general_content.permission_trans_key') }}" theme="secondary" maximizable>
                    <div class="table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="text-capitalize">{{ __('general_content.permission_trans_key') }}</th>
                                    <!--<th class="text-capitalize">Groupe Name</th>-->
                                    <th class="text-capitalize">{{ __('general_content.created_at_trans_key') }}</th>
                                    <th class="text-capitalize text-right" >{{ __('general_content.action_trans_key') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($Permissions as $Permission)
                                <tr>
                                    <td>{{ $Permission->name }}</td>
                                    <!--<td>{{ $Permission->groupe_name }}</td>-->
                                    <td>{{ $Permission->created_at }}</td>
                                    <td class="text-right">
                                        <x-ButtonTextDelete route="{{ route('admin.factory.permissions.destroy', ['permission' => $Permission->id]) }}" />
                                    </td>
                                </tr>
                                @empty
                                    <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-capitalize">{{ __('general_content.permission_trans_key') }}</th>
                                    <!--<th class="text-capitalize">Groupe Name</th>-->
                                    <th class="text-capitalize">{{ __('general_content.created_at_trans_key') }}</th>
                                    <th class="text-capitalize text-right" >{{ __('general_content.action_trans_key') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
            <div class="tab-pane" id="RoleInPermissions">
                @include('include.alert-result')
                <form method="POST" action="{{ route('admin.factory.rolepermissions.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <x-adminlte-card title="{{ __('general_content.add_role_permission_trans_key') }}" theme="primary" maximizable>
                        <div class="row">
                            <!-- /.form-group -->
                            <div class="form-group">
                                <label for="role">{{__('general_content.role_trans_key') }} :</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <select class="form-control" name="role_id" id="role_id">
                                        @forelse ($Roles as $Role)
                                            <option value="{{ $Role->id }}">{{ $Role->name }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_role_trans_key') }}</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                        </div>
                        @forelse ($Permissions as $Permission)
                        <div class="row">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" name="permission[]" id="checkDefault{{ $Permission->id }}" value="{{ $Permission->id }}">
                                    <label for="checkDefault{{ $Permission->id }}" class="custom-control-label">{{ $Permission->name }}</label>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="card-footer">
                            <p>{{ __('general_content.no_permissions_trans_key') }}</p>
                        </div>
                        @endforelse

                        <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                        </x-slot>
                    </x-adminlte-card>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('plugins.BootstrapSwitch', true)

@section('css')
@stop

@section('js')
@stop