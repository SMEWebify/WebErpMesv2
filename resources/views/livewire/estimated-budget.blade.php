
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            @php $months = __('general_content.chart_months_trans_key'); @endphp

            @if($updateLines)
            <form wire:submit.prevent="updateEstimatedBudget">
                <div class="row">
                    <div class="col-2">
                        <label for="title">{{ __('general_content.year_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control @error('year') is-invalid @enderror" name="year" id="year"  wire:model.lazy="year">
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
                    </div>
                    @foreach($months as $i => $month)
                    <div class="col-2">
                        <label for="amount{{ $i+1 }}">{{ $month }} :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount'.($i+1)) is-invalid @enderror" id="amount{{ $i+1 }}" placeholder="{{ $month }}" wire:model.lazy="amount{{ $i+1 }}">
                        </div>
                    </div>
                    @if(($i+1) % 4 === 0 && $i+1 < 12)
                        </div><div class="row">
                        <div class="col-2">
                            <br/>
                            @if($i+1 === 4)
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                            @elseif($i+1 === 8)
                                <button onclick="location.reload();"  class="btn btn-primary btn-block">{{ __('general_content.refresh_trans_key') }}</button>
                            @endif
                        </div>
                    @endif
                    @endforeach
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
                            <select class="form-control @error('year') is-invalid @enderror" name="year" id="year"  wire:model.lazy="year">
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
                    </div>
                    @foreach($months as $i => $month)
                    <div class="col-2">
                        <label for="amount{{ $i+1 }}">{{ $month }} :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('amount'.($i+1)) is-invalid @enderror" id="amount{{ $i+1 }}" placeholder="{{ $month }}" wire:model.lazy="amount{{ $i+1 }}">
                        </div>
                    </div>
                    @if(($i+1) % 4 === 0 && $i+1 < 12)
                        </div><div class="row">
                        <div class="col-2">
                            <br/>
                            @if($i+1 === 4)
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                            @endif
                        </div>
                    @endif
                    @endforeach
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
                            @foreach($months as $month)
                                <th>{{ $month }}</th>
                            @endforeach
                            <th>{{ __('general_content.total_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($EstimatedBudgetlist as $EstimatedBudget)
                        @php
                            $total = $EstimatedBudget->amount1 + $EstimatedBudget->amount2 + $EstimatedBudget->amount3
                                   + $EstimatedBudget->amount4 + $EstimatedBudget->amount5 + $EstimatedBudget->amount6
                                   + $EstimatedBudget->amount7 + $EstimatedBudget->amount8 + $EstimatedBudget->amount9
                                   + $EstimatedBudget->amount10 + $EstimatedBudget->amount11 + $EstimatedBudget->amount12;
                        @endphp
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
                            <td><strong>{{ number_format($total, 0, ',', ' ') }} {{ $Factory->curency }}</strong></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="editEstimatedBudget({{$EstimatedBudget->id}})" class="btn btn-warning"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="destroyEstimatedBudget({{$EstimatedBudget->id}})" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="15" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.year_trans_key') }}</th>
                            @foreach($months as $month)
                                <th>{{ $month }}</th>
                            @endforeach
                            <th>{{ __('general_content.total_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
