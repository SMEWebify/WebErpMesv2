
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            @if($updateLines)
            <form wire:submit.prevent="updateEstimatedBudget">
                <div class="row">
                    <div class="col-2">
                        <label for="title">{{ __('general_content.year_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control @error('year') is-invalid @enderror" name="year" id="year"  wire:model.live="year">
                                <option value="" >{{ __('general_content.select_year_trans_key') }}</option>
                                <option value="2021" >2021</option>
                                <option value="2022" >2022</option>
                                <option value="2023" >2023</option>
                                <option value="2024" >2024</option>
                                <option value="2025" >2025</option>
                                <option value="2026" >2026</option>
                                <option value="2027" >2027</option>
                                <option value="2028" >2028</option>
                                <option value="2029" >2029</option>
                                <option value="2030" >2030</option>
                            </select>
                        </div>
                        @error('year') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount1">{{ __('general_content.amount_trans_key') }} 1 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount1') is-invalid @enderror" id="amount1" placeholder="{{ __('general_content.amount_trans_key') }} 1" wire:model.live="amount1">
                        </div>
                        @error('amount1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount2">{{ __('general_content.amount_trans_key') }} 2 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount2') is-invalid @enderror" id="amount2" placeholder="{{ __('general_content.amount_trans_key') }} 2" wire:model.live="amount2">
                        </div>
                        @error('amount2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount3">{{ __('general_content.amount_trans_key') }} 3 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount3') is-invalid @enderror" id="amount3" placeholder="{{ __('general_content.amount_trans_key') }} 3" wire:model.live="amount3">
                        </div>
                        @error('amount3') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount4">{{ __('general_content.amount_trans_key') }} 4 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount4" placeholder="{{ __('general_content.amount_trans_key') }} 4" wire:model.live="amount4">
                        </div>
                        @error('amount4') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <br/>
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                    </div>
                    <div class="col-2">
                        <label for="amount5">{{ __('general_content.amount_trans_key') }} 5 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount5') is-invalid @enderror" id="amount5" placeholder="{{ __('general_content.amount_trans_key') }} 5" wire:model.live="amount5">
                        </div>
                        @error('amount5') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount6">{{ __('general_content.amount_trans_key') }} 6 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount6') is-invalid @enderror" id="amount6" placeholder="{{ __('general_content.amount_trans_key') }} 6" wire:model.live="amount6">
                        </div>
                        @error('amount6') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount7">{{ __('general_content.amount_trans_key') }} 7 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount7') is-invalid @enderror" id="amount7" placeholder="{{ __('general_content.amount_trans_key') }} 7" wire:model.live="amount7">
                        </div>
                        @error('amount7') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount8">{{ __('general_content.amount_trans_key') }} 8 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount8') is-invalid @enderror" id="amount8" placeholder="{{ __('general_content.amount_trans_key') }} 8" wire:model.live="amount8">
                        </div>
                        @error('amount8') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <br/>
                        <button onclick="location.reload();"  class="btn btn-primary btn-block">{{ __('general_content.refresh_trans_key') }}</button>
                    </div>
                    <div class="col-2">
                        <label for="amount9">{{ __('general_content.amount_trans_key') }} 9 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount9') is-invalid @enderror" id="amount9" placeholder="{{ __('general_content.amount_trans_key') }} 9" wire:model.live="amount9">
                        </div>
                        @error('amount9') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount10">{{ __('general_content.amount_trans_key') }} 10 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount10" placeholder="{{ __('general_content.amount_trans_key') }} 10" wire:model.live="amount10">
                        </div>
                        @error('amount10') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount11">{{ __('general_content.amount_trans_key') }} 11 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount11') is-invalid @enderror" id="amount11" placeholder="{{ __('general_content.amount_trans_key') }} 11" wire:model.live="amount11">
                        </div>
                        @error('amount11') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount12">{{ __('general_content.amount_trans_key') }} 12 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount12') is-invalid @enderror" id="amount12" placeholder="{{ __('general_content.amount_trans_key') }} 12" wire:model.live="amount12">
                        </div>
                        @error('amount12') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
            </form>
            @else
            <form wire:submit.prevent="storeEstimatedBudget">
                <div class="row">
                    <div class="col-2">
                        <label for="title">{{ __('general_content.year_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control @error('year') is-invalid @enderror" name="year" id="year"  wire:model.live="year">
                                <option value="" >{{ __('general_content.select_year_trans_key') }}</option>
                                <option value="2021" >2021</option>
                                <option value="2022" >2022</option>
                                <option value="2023" selected>2023</option>
                                <option value="2024" >2024</option>
                                <option value="2025" >2025</option>
                                <option value="2026" >2026</option>
                                <option value="2027" >2027</option>
                                <option value="2028" >2028</option>
                                <option value="2029" >2029</option>
                                <option value="2030" >2030</option>
                            </select>
                        </div>
                        @error('year') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount1">{{ __('general_content.amount_trans_key') }} 1 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount1') is-invalid @enderror" id="amount1" placeholder="{{ __('general_content.amount_trans_key') }} 1" wire:model.live="amount1">
                        </div>
                        @error('amount1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount2">{{ __('general_content.amount_trans_key') }} 2 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount2') is-invalid @enderror" id="amount2" placeholder="{{ __('general_content.amount_trans_key') }} 2" wire:model.live="amount2">
                        </div>
                        @error('amount2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount3">{{ __('general_content.amount_trans_key') }} 3 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount3') is-invalid @enderror" id="amount3" placeholder="{{ __('general_content.amount_trans_key') }} 3" wire:model.live="amount3">
                        </div>
                        @error('amount3') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount4">{{ __('general_content.amount_trans_key') }} 4 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount4" placeholder="{{ __('general_content.amount_trans_key') }} 4" wire:model.live="amount4">
                        </div>
                        @error('amount4') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <br/>
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                    <div class="col-2">
                        <label for="amount5">{{ __('general_content.amount_trans_key') }} 5 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount5') is-invalid @enderror" id="amount5" placeholder="{{ __('general_content.amount_trans_key') }} 5" wire:model.live="amount5">
                        </div>
                        @error('amount5') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount6">{{ __('general_content.amount_trans_key') }} 6 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount6') is-invalid @enderror" id="amount6" placeholder="{{ __('general_content.amount_trans_key') }} 6" wire:model.live="amount6">
                        </div>
                        @error('amount6') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount7">{{ __('general_content.amount_trans_key') }} 7 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount7') is-invalid @enderror" id="amount7" placeholder="{{ __('general_content.amount_trans_key') }} 7" wire:model.live="amount7">
                        </div>
                        @error('amount7') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount8">{{ __('general_content.amount_trans_key') }} 8 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount8') is-invalid @enderror" id="amount8" placeholder="{{ __('general_content.amount_trans_key') }} 8" wire:model.live="amount8">
                        </div>
                        @error('amount8') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">

                    </div>
                    <div class="col-2">
                        <label for="amount9">{{ __('general_content.amount_trans_key') }} 9 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount9') is-invalid @enderror" id="amount9" placeholder="{{ __('general_content.amount_trans_key') }} 9" wire:model.live="amount9">
                        </div>
                        @error('amount9') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount10">{{ __('general_content.amount_trans_key') }} 10 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount10" placeholder="{{ __('general_content.amount_trans_key') }} 10" wire:model.live="amount10">
                        </div>
                        @error('amount10') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount11">{{ __('general_content.amount_trans_key') }} 11 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount11') is-invalid @enderror" id="amount11" placeholder="{{ __('general_content.amount_trans_key') }} 11" wire:model.live="amount11">
                        </div>
                        @error('amount11') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount12">{{ __('general_content.amount_trans_key') }} 12 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount12') is-invalid @enderror" id="amount12" placeholder="{{ __('general_content.amount_trans_key') }} 12" wire:model.live="amount12">
                        </div>
                        @error('amount12') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
            </form>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.year_trans_key') }}</th>
                            <th>{{ __('general_content.amount_trans_key') }} 1</th>
                            <th>{{ __('general_content.amount_trans_key') }} 2</th>
                            <th>{{ __('general_content.amount_trans_key') }} 3</th>
                            <th>{{ __('general_content.amount_trans_key') }} 4</th>
                            <th>{{ __('general_content.amount_trans_key') }} 5</th>
                            <th>{{ __('general_content.amount_trans_key') }} 6</th>
                            <th>{{ __('general_content.amount_trans_key') }} 7</th>
                            <th>{{ __('general_content.amount_trans_key') }} 8</th>
                            <th>{{ __('general_content.amount_trans_key') }} 9</th>
                            <th>{{ __('general_content.amount_trans_key') }} 10</th>
                            <th>{{ __('general_content.amount_trans_key') }} 11</th>
                            <th>{{ __('general_content.amount_trans_key') }} 12</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($EstimatedBudgetlist as $EstimatedBudget)
                        <tr>
                            <td>{{ $EstimatedBudget->year }}</td>
                            <td>{{ $EstimatedBudget->amount1 }}</td>
                            <td>{{ $EstimatedBudget->amount2 }}</td>
                            <td>{{ $EstimatedBudget->amount3 }}</td>
                            <td>{{ $EstimatedBudget->amount4 }}</td>
                            <td>{{ $EstimatedBudget->amount5 }}</td>
                            <td>{{ $EstimatedBudget->amount6 }}</td>
                            <td>{{ $EstimatedBudget->amount7 }}</td>
                            <td>{{ $EstimatedBudget->amount8 }}</td>
                            <td>{{ $EstimatedBudget->amount9 }}</td>
                            <td>{{ $EstimatedBudget->amount10 }}</td>
                            <td>{{ $EstimatedBudget->amount11 }}</td>
                            <td>{{ $EstimatedBudget->amount12 }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="editEstimatedBudget({{$EstimatedBudget->id}})" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="destroyEstimatedBudget({{$EstimatedBudget->id}})" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="14" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.year_trans_key') }}</th>
                            <th>{{ __('general_content.amount_trans_key') }} 1</th>
                            <th>{{ __('general_content.amount_trans_key') }} 2</th>
                            <th>{{ __('general_content.amount_trans_key') }} 3</th>
                            <th>{{ __('general_content.amount_trans_key') }} 4</th>
                            <th>{{ __('general_content.amount_trans_key') }} 5</th>
                            <th>{{ __('general_content.amount_trans_key') }} 6</th>
                            <th>{{ __('general_content.amount_trans_key') }} 7</th>
                            <th>{{ __('general_content.amount_trans_key') }} 8</th>
                            <th>{{ __('general_content.amount_trans_key') }} 9</th>
                            <th>{{ __('general_content.amount_trans_key') }} 10</th>
                            <th>{{ __('general_content.amount_trans_key') }} 11</th>
                            <th>{{ __('general_content.amount_trans_key') }} 12</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
