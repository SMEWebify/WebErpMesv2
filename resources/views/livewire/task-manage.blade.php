<div>
    @php
        $detail = $Line->OrderLineDetails ?? $Line->QuoteLineDetails ?? null;
        $customRequirementsForDisplay = $detail ? collect($detail->custom_requirements ?? [])->filter(function ($requirement) {
            return !empty($requirement['label'] ?? null) || !empty($requirement['value'] ?? null);
        }) : collect();
    @endphp

    @if($detail)
        <x-adminlte-card title="{{ __('Custom requirements') }}" theme="secondary" icon="fas fa-swatchbook" class="mb-3">
            @if($customRequirementsForDisplay->isNotEmpty())
                <ul class="list-unstyled mb-0">
                    @foreach($customRequirementsForDisplay as $requirement)
                        <li>
                            <strong>{{ $requirement['label'] ?? __('Requirement') }}:</strong>
                            <span>{{ $requirement['value'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <span class="text-muted">{{ __('No custom requirement added yet.') }}</span>
            @endif
        </x-adminlte-card>
    @endif

    @if($statu == 1 || $idType == "nomenclature_lines_id")
        @include('include.alert-result')
        @if($updateLines)
        <form wire:submit.prevent="updateTask">
        @else
        <form wire:submit.prevent="storeTask({{ $Line->id }})">
        @endif
            <div class="card card-body">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="companies_id">{{ __('general_content.task_type_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <select class="form-control" wire:click.prevent="ChangeTaskType()" wire:model.live="TaskType" name="TaskType" id="TaskType">
                                <option value="">{{ __('general_content.select_task_type_trans_key') }}</option>
                                <option value="TechCut">{{__('general_content.technical_cut_trans_key') }}</option>
                                <option value="BOM">{{__('general_content.bom_trans_key') }}</option>
                            </select>
                        </div>
                        @error('document_type') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="ordre">{{ __('general_content.sort_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            </div>
                            <input type="number" class="form-control @error('ordre') is-invalid @enderror" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" min="0" wire:model.live="ordre">
                            
                            <input type="hidden" name="{{ $idType }}" value="{{ $Line->id   }}">
                        </div>
                        @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control @error('methods_services_id') is-invalid @enderror" wire:click.prevent="ChangeCodelabel()" name="methods_services_id" id="methods_services_id" wire:change="changeInputValues" wire:model.live="methods_services_id" required>
                            <option>{{ __('general_content.select_service_trans_key') }}</option>
                                @foreach ($ServicesSelect as $item)
                                <option value="{{ $item->id }}-{{ $item->type }}" data-txt="{{ $item->label }}">{{ $item->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('methods_services_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="label">{{__('general_content.label_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control @error('label') is-invalid @enderror"  name="label"  id="LABEL_TechnicalCut" placeholder="{{__('general_content.label_trans_key') }}" wire:model.live="label">
                        </div>
                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="methods_tools_id">{{ __('general_content.tools_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tools"></i></span>
                            </div>
                            <select class="form-control @error('methods_tools_id') is-invalid @enderror" name="methods_tools_id" id="methods_tools_id" wire:model.live="methods_tools_id">
                                <option value="">{{ __('general_content.select_trans_key') }}</option>
                                @foreach ($ToolsSelect as $tool)
                                <option value="{{ $tool->id }}">{{ $tool->code }} - {{ $tool->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('methods_tools_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        @if($TaskType == 'BOM') 
                        <label for="component_id">{{__('general_content.component_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            </div>
                            <select class="form-control @error('component_id') is-invalid @enderror" name="component_id" id="component_id"  wire:change="componentCost" wire:model.live="component_id" >
                                <option>{{ __('general_content.select_component_trans_key') }}</option>
                                @foreach ($ProductSelect as $item)
                                <option value="{{ $item->id }}" class="{{ $item->methods_services_id }}">{{ $item->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('component_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @endif 
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-2">
                        @if(count($ToolsSelect) > 0)
                        <label for="methods_tools_id">{{ __('general_content.tools_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tools"></i></span>
                            </div>
                            <select class="form-control" name="methods_tools_id" id="methods_tools_id" wire:model.live="methods_tools_id">
                                <option value="">{{ __('general_content.select_tool_trans_key') }}</option>
                                @foreach ($ToolsSelect as $tool)
                                <option value="{{ $tool->id }}">{{ $tool->code }} - {{ $tool->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                    <div class="form-group col-md-2">
                        @if($TaskType == 'TechCut')
                        <label for="seting_time">{{ __('general_content.setting_time_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-stopwatch"> {{__('general_content.hour_trans_key') }}</i></span>
                            </div>
                            <input type="number" class="form-control @error('seting_time') is-invalid @enderror" name="seting_time"  id="seting_time" placeholder="{{ __('general_content.setting_time_trans_key') }}" value="0" step=".001"  min="0" wire:change="changeInputValues"  wire:model.defer="seting_time" >
                        </div>
                        @error('seting_time') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @else 
                        <label for="qty">{{ __('general_content.qty_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                            <input type="number" class="form-control @error('qty') is-invalid @enderror" name="qty"  id="qty" value="{{ $Line->qty  }}" placeholder="{{ __('general_content.qty_trans_key') }}" step=".001"  min="0" wire:model.live="qty">
                        </div>
                        @error('qty') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @endif
                    </div>
                    <div class="form-group col-md-2">
                        @if($TaskType == 'TechCut')
                        <label for="unit_time">{{ __('general_content.unit_time_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-stopwatch"> {{__('general_content.hour_trans_key') }}</i></span>
                            </div>
                            <input type="number" class="form-control @error('unit_time') is-invalid @enderror" name="unit_time"  id="unit_time" placeholder="{{ __('general_content.unit_time_trans_key') }}" value="0" step=".001"  min="0" wire:change="changeInputValues" wire:model.defer="unit_time" >
                        </div>
                        @error('unit_time') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @endif
                    </div>
                    <div class="form-group col-md-2">
                        <label for="start_date">{{ __('general_content.start_date_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            </div>
                            <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" name="start_date" id="start_date" wire:model.live="start_date">
                        </div>
                        @error('start_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                            </div>
                            <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" name="end_date" id="end_date" wire:model.live="end_date">
                        </div>
                        @error('end_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="unit_cost">{{ __('general_content.cost_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('unit_cost') is-invalid @enderror" name="unit_cost"  id="unit_cost" placeholder="{{ __('general_content.cost_trans_key') }}" value="0" step=".001" min="0" wire:model.defer="unit_cost">
                        </div> 
                        <p>({{ $seting_time  }} h /{{ $Line->qty  }} + {{ $unit_time }} h) x {{ $methods_services_hourly_rate }} {{ $Factory->curency }} / h = {{ ((float)$seting_time / (float)$Line->qty + (float)$unit_time) * (float)$methods_services_hourly_rate }}</p>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="unit_price">{{ __('general_content.price_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('unit_price') is-invalid @enderror" name="unit_price"  id="unit_price" placeholder="{{ __('general_content.unit_time_trans_key') }}" value="0" step=".001" min="0" wire:model.live="unit_price">
                        </div>
                        <p>{{ $unit_cost  }} {{ $Factory->curency }} x {{ $methods_services_margin }} %  = {{ round( (float)$unit_cost*(1+((float)$methods_services_margin/100)),2) }}</p>
                        @error('unit_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        @if($TaskType == 'BOM' or $TaskType == 'TechCut')
                            @if($updateLines)
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                            @else
                            <x-adminlte-button class="btn-flat" type="submit" label="Add Task" theme="success" icon="fas fa-lg fa-save"/>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </form>
        
        @if($TaskType == 'BOM' && $idType != "nomenclature_lines_id")
        <div class="card card-body">
            @if (session()->has('errors'))
                <ul>
                    @foreach (session('errors') as $errorMessage)
                        <li class="bg-danger">{{ $errorMessage }}</li>
                    @endforeach
                </ul>
            @endif
        
            <form wire:submit.prevent="importBOMCSV" class="form-inline">
                <div class="form-group mb-2">
                    <input type="hidden" wire:model="idLine" value="{{ $Line->id }}">
                    <div class="custom-file">
                        <input type="file" wire:model="csvFile" class="custom-file-input" id="csvFileInput" data-toggle="tooltip" data-placement="top" title="Model: ordre,service_code,label,component_code,unit_code,qty,unit_cost,unit_price">
                        <label class="custom-file-label" for="csvFileInput">Choose file</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mb-2">{{__('general_content.lines_import_trans_key') }}</button>
            </form>
        </div>
        @endif
    @endif

    @if($Line->id ?? null)
        <x-adminlte-card title="{{ __('general_content.technical_cut_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{__('general_content.id_trans_key') }} </th>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.service_trans_key') }}</th>
                            <th>{{ __('general_content.tools_trans_key') }}</th>
                            <th>{{ __('general_content.setting_time_trans_key') }}</th>
                            <th>{{ __('general_content.unit_time_trans_key') }}</th>
                            <th>{{ __('general_content.start_date_trans_key') }}</th>
                            <th>{{ __('general_content.end_date_trans_key') }}</th>
                            <th>{{ __('general_content.total_time_trans_key') }}</th>
                            <th>{{ __('general_content.progress_trans_key') }}</th>
                            <th>{{ __('general_content.cost_trans_key') }}</th>
                            <th>{{ __('general_content.margin_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                            <th>{{__('general_content.end_date_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Line->TechnicalCut as $TechLine)
                        <tr>
                            <td>
                                @if($idType == "order_lines_id")
                                    <a href="{{ route('production.task.statu.id', ['id' => $TechLine->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a> #{{ $TechLine->id }}</a>
                                @else
                                    #{{ $TechLine->id }}
                                @endif
                            </td>
                            <td>{{ $TechLine->ordre }}</td>
                            <td>{{ $TechLine->label }}</td>
                            <td @if($TechLine->methods_services_id ) style="background-color: {{ $TechLine->service['color'] }};" @endif >
                                @if($TechLine->methods_services_id )
                                    @if( $TechLine->service['picture'])
                                        <p data-toggle="tooltip" data-html="true" title="<img alt='Service' class='profile-user-img img-fluid img-circle' src='{{ asset('/images/methods/'. $TechLine->service['picture']) }}'>">
                                            <span>{{ $TechLine->service['label'] }}</span>
                                        </p>
                                    @else
                                        {{ $TechLine->service['label'] }}
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($TechLine->MethodsTools)
                                    {{ $TechLine->MethodsTools->code }} - {{ $TechLine->MethodsTools->label }}
                                @else
                                    {{ __('general_content.not_available_trans_key') }}
                                @endif
                            </td>
                            <td>{{ $TechLine->seting_time }} h</td>
                            <td>{{ $TechLine->unit_time }} h</td>
                            <td>{{ $TechLine->start_date }}</td>
                            <td>{{ $TechLine->end_date }}</td>
                            <td>{{ $TechLine->TotalTime() }} h</td>
                            <td><x-adminlte-progress theme="teal" value="{{ $TechLine->progress() }}" with-label animated/></td>
                            <td>{{ $TechLine->formatted_unit_cost }}</td>
                            <td>{{ $TechLine->Margin() }} %</td>
                            <td>{{ $TechLine->formatted_unit_price }}</td>
                            <td>
                            @if($TechLine->order_lines_id)
                                {{ $TechLine->status['title'] }}
                            @else
                                {{__('general_content.not_this_page_trans_key') }}
                            @endif
                            </td>
                            <td class=" py-0 align-middle">
                                <div class="input-group-prepend">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <div class="dropdown-menu">
                                        <a href="#" class="dropdown-item " wire:click="duplicateLine({{$TechLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> {{ __('general_content.copie_line_trans_key') }}</span></a>
                                        <a href="#" class="dropdown-item" wire:click="editTaskLine({{$TechLine->id}})"><span class="text-warning"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                        <a href="#" class="dropdown-item" wire:click="destroyTaskLine({{$TechLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.delete_line_trans_key') }}</span></a>
                                    </div>
                                </div>
                            </td>
                            @if($TechLine->type != 1 & $TechLine->type != 7)
                            <td class="bg-info color-palette">{{ $TechLine->service['label'] }}</td>
                            @elseif($todayDate->format("Y-m-d") > $TechLine->getFormattedEndDateAttribute() )
                            <td class="bg-danger color-palette">{{ $TechLine->formatted_end_date }}</td> 
                            @elseif($todayDate->format("Y-m-d") == $TechLine->getFormattedEndDateAttribute() )
                            <td class="bg-orange color-palette">{{ $TechLine->formatted_end_date }}</td> 
                            @else
                            <td class="bg-primary color-palette">{{ $TechLine->formatted_end_date }}</td>
                            @endif 
                        </tr>
                        @empty
                        <x-EmptyDataLine col="16" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th>{{ __('general_content.total_trans_key') }} :</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{ $Line->getTechnicalCutTotalSettingTimeAttribute() }} h</th>
                            <th>{{ $Line->getTechnicalCutTotalUnitTimeAttribute() }} h</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{ number_format( $Line->getTechnicalCutTotalUnitCostAttribute(), 2, '.', ',') }} {{ $Factory->curency }}</th>
                            <th>{{ $Line->getTechnicalCutTMarginAttribute() }} %</th>
                            <th>{{ number_format( $Line->getTechnicalCutTotalUnitPricettribute(), 2, '.', ',') }} {{ $Factory->curency }}</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
        
        <x-adminlte-card title="{{ __('general_content.bill_of_materials_trans_key') }}" theme="info" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.id_trans_key') }} </th>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{ __('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.service_trans_key') }}</th>
                            <th>{{ __('general_content.tools_trans_key') }}</th>
                            <th>{{ __('general_content.component_trans_key') }}</th>
                            <th></th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.cost_trans_key') }}</th>
                            <th>{{ __('general_content.margin_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($Line->BOM as $BOMline)
                        <tr>
                            <td>
                                
                                @if($idType == "order_lines_id")
                                    <a href="{{ route('production.task.statu.id', ['id' => $BOMline->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a> #{{ $BOMline->id }}</a>
                                @else
                                    #{{ $BOMline->id }}
                                @endif
                            </td>
                            <td>{{ $BOMline->ordre }}</td>
                            <td>{{ $BOMline->label }}</td>
                            <td @if($BOMline->methods_services_id ) style="background-color: {{ $BOMline->service['color'] }};" @endif >
                                @if($BOMline->methods_services_id )
                                    @if( $BOMline->service['picture'])
                                        <p data-toggle="tooltip" data-html="true" title="<img alt='Service' class='profile-user-img img-fluid img-circle' src='{{ asset('/images/methods/'. $BOMline->service['picture']) }}'>">
                                            <span>{{ $BOMline->service['label'] }}</span>
                                        </p>
                                    @else
                                        {{ $BOMline->service['label'] }}
                                    @endif
                                @endif
                            </td>
                            @if($BOMline->Component)
                                <td class="bg-{{ $BOMline->Component->getColorStockStatu() }} color-palette">{{ $BOMline->Component['code'] }}</td> 
                                <td><x-ButtonTextView route="{{ route('products.show', ['id' => $BOMline->component_id])}}" /></td>
                            @else
                                <td><span>N/A</span></td>
                                <td></td>
                            @endif
                            <td>
                                @if($BOMline->MethodsTools)
                                    {{ $BOMline->MethodsTools->code }} - {{ $BOMline->MethodsTools->label }}
                                @else
                                    {{ __('general_content.not_available_trans_key') }}
                                @endif
                            </td>
                            <td>{{ $BOMline->qty }}</td>
                            <td>{{ $BOMline->formatted_unit_cost }}</td>
                            <td>{{ $BOMline->Margin() }} %</td>
                            <td>{{ $BOMline->formatted_unit_price }}</td>
                            <td>
                                @if($BOMline->order_lines_id)
                                {{ $BOMline->status['title'] }}
                                @else
                                {{__('general_content.not_this_page_trans_key') }}
                                @endif
                            </td>
                            <td class=" py-0 align-middle">
                                <div class="input-group-prepend">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <div class="dropdown-menu">
                                        <a href="#" class="dropdown-item " wire:click="duplicateLine({{$BOMline->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> {{ __('general_content.copie_line_trans_key') }}</span></a>
                                        <a href="#" class="dropdown-item" wire:click="editTaskLine({{$BOMline->id}})"><span class="text-warning"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                        <a href="#" class="dropdown-item" wire:click="destroyTaskLine({{$BOMline->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.delete_line_trans_key') }}</span></a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="13" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th>{{ __('general_content.total_trans_key') }} :</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{ number_format( $Line->getBOMTotalUnitCostAttribute(), 2, '.', ',') }} {{ $Factory->curency }}</th>
                            <th>{{ $Line->getBOMTMarginAttribute() }} %</th>
                            <th>{{ number_format( $Line->getBOMTotalUnitPricettribute(), 2, '.', ',') }} {{ $Factory->curency }}</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
    @endif

    @if($statu == 1 && $idType != "sub_assembly_id")
        @if($updateLines)
        <form wire:submit.prevent="updateSubAssembly">
        @else
        <form wire:submit.prevent="storeSubAssembly({{ $Line->id }})">
        @endif
            <div class="card card-body">
                <div class="row">
                    <div class="form-group col-md-2">
                        <label for="subAssemblyOrdre">{{ __('general_content.sort_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            </div>
                            <input type="number" class="form-control @error('subAssemblyOrdre') is-invalid @enderror" name="subAssemblyOrdre" id="subAssemblyOrdre" placeholder="{{ __('general_content.sort_trans_key') }}" min="0" wire:model.live="subAssemblyOrdre">
                            
                        </div>
                        @error('subAssemblyOrdre') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        <input type="hidden" name="{{ $idType }}" value="{{ $Line->id   }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="subAssemblyComponentId">{{__('general_content.component_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            </div>
                            <select class="form-control @error('subAssemblyComponentId') is-invalid @enderror" name="subAssemblyComponentId" id="subAssemblyComponentId"  wire:click.prevent="ChangeSubAssemblyCodelabel()" wire:model.live="subAssemblyComponentId" >
                                <option>{{ __('general_content.select_component_trans_key') }}</option>
                                @foreach ($ComponentSelect as $item)
                                <option value="{{ $item->id }}" class="{{ $item->methods_services_id }}">{{ $item->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('subAssemblyComponentId') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="subAssemblylabel">{{__('general_content.label_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control @error('subAssemblylabel') is-invalid @enderror"  name="subAssemblylabel"  id="subAssemblylabel" placeholder="{{__('general_content.label_trans_key') }}" wire:model.live="subAssemblylabel" disabled>
                        </div>
                        @error('subAssemblylabel') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="subAssemblyQty">{{ __('general_content.qty_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                            <input type="number" class="form-control @error('subAssemblyQty') is-invalid @enderror" name="subAssemblyQty"  id="subAssemblyQty" value="1" placeholder="{{ __('general_content.qty_trans_key') }}" step="1"  min="0" wire:model.live="subAssemblyQty">
                        </div>
                        @error('subAssemblyQty') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="subAssemblyUnit_price">{{ __('general_content.price_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('subAssemblyUnit_price') is-invalid @enderror" name="subAssemblyUnit_price"  id="subAssemblyUnit_price" placeholder="{{ __('general_content.price_trans_key') }}" value="0" step=".001" min="0" wire:model.live="subAssemblyUnit_price">
                        </div>
                    </div>
                    <div class="form-group col-md-2">
                        @if($updateLines)
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                        @else
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.add_sub_assembly_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    @endif

    @if($Line->id ?? null)
        @if($idType != "sub_assembly_id" && $idType != "nomenclature_lines_id")
        <x-adminlte-card title="{{ __('general_content.sub_assembly_trans_key') }}" theme="secondary" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>{{ __('general_content.id_trans_key') }} </th>
                        <th>{{ __('general_content.sort_trans_key') }}</th>
                        <th>{{ __('general_content.id_trans_key') }}</th>
                        <th>{{ __('general_content.label_trans_key') }}</th>
                        <th></th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.cost_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }} </th>
                        <th>{{ __('general_content.action_trans_key') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                        @forelse ($Line->SubAssembly as $SubAssemblyLine)
                        <tr>
                            <td>#{{ $SubAssemblyLine->id }}</td>
                            <td>{{ $SubAssemblyLine->ordre }}</td>
                            <td>{{ $SubAssemblyLine->Child['code'] }}</td>
                            <td>{{ $SubAssemblyLine->Child['label'] }}</td>
                            <td><x-ButtonTextView route="{{ route('products.show', ['id' => $SubAssemblyLine->child_id])}}" /></td>
                            <td>{{ $SubAssemblyLine->qty }}</td>
                            <td>{{ $SubAssemblyLine->Child['selling_price'] }}  {{ $Factory->curency }}</td>
                            <td>{{ $SubAssemblyLine->unit_price }}  {{ $Factory->curency }}</td>
                            <td>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        @if($SubAssemblyLine->Child->drawing_file)
                                            <!-- Drawing link -->
                                            <x-button-text-view :bankFile="$SubAssemblyLine->Child->drawing_file" />
                                        @endif
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            <a href="#" class="dropdown-item " wire:click="duplicateSubAssemblyLine({{$SubAssemblyLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> {{ __('general_content.copie_line_trans_key') }}</span></a>
                                            <a href="#" class="dropdown-item" wire:click="editSubAssemblyLine({{$SubAssemblyLine->id}})"><span class="text-warning"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                            <a href="#" class="dropdown-item" wire:click="destroySubAssemblyLine({{$SubAssemblyLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.delete_line_trans_key') }}</span></a>
                                            <a href="#" class="dropdown-item" wire:click="breakDown({{$SubAssemblyLine->id}})"><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i>{{ __('general_content.break_down_task_trans_key') }}</span></a>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        @if($idType == "sub_assembly_id")
                                        <a href="{{ route('task.manage', ['id_type'=> 'sub_assembly_id', 'id_page'=>  $SubAssemblyLine->sub_assembly_id, 'id_line' => $SubAssemblyLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $SubAssemblyLine->getAllTaskCountAttribute() }}</span></a></button>
                                        @elseif($idType == "quote_lines_id")
                                        <a href="{{ route('task.manage', ['id_type'=> 'sub_assembly_id', 'id_page'=>  $SubAssemblyLine->QuoteLines->quotes_id, 'id_line' => $SubAssemblyLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $SubAssemblyLine->getAllTaskCountAttribute() }}</span></a></button>
                                        @elseif($idType == "order_lines_id")
                                        <a href="{{ route('task.manage', ['id_type'=> 'sub_assembly_id', 'id_page'=>  $SubAssemblyLine->OrderLines->orders_id, 'id_line' => $SubAssemblyLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $SubAssemblyLine->getAllTaskCountAttribute() }}</span></a></button>
                                        @else
                                        <a href="{{ route('task.manage', ['id_type'=> 'sub_assembly_id', 'id_page'=>  $SubAssemblyLine->products_id, 'id_line' => $SubAssemblyLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $SubAssemblyLine->getAllTaskCountAttribute() }}</span></a></button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
        @endif
    @endif

    @if($statu == 1 || $idType == "nomenclature_lines_id")
        <x-adminlte-card title="{{ __('general_content.standard_bom_trans_key') }}" theme="warning" maximizable>
            <div class="table-responsive p-0 m-4">
                @forelse ($StandardNomenclatures as $StandardNomenclature)
                    <a  wire:click="AddStandardNomenclature({{$StandardNomenclature->id}})" class="btn btn-app bg-primary">
                        <span class="badge bg-success">{{ $StandardNomenclature->getAllTaskCountAttribute() }}</span>
                        <i class="fas fa-list"></i> {{ $StandardNomenclature->label }}
                    </a>
                @empty
                {{ __('general_content.no_data_trans_key') }}
                @endforelse
            </div>
        </x-adminlte-card>
    @endif
</div>
