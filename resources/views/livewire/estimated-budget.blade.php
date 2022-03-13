
<div>
    <div class="card">
        <div class="card-body">
            @if(session()->has('success'))
                <div class="alert alert-success" role="alert">
                    {{ session()->get('success') }}
                </div>
            @endif

            @if(session()->has('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session()->get('error') }}
                </div>
            @endif

            @if($updateLines)
            <form wire:submit.prevent="updateEstimatedBudget">
                <div class="row">
                    <div class="col-2">
                        <label for="title">Year</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control @error('year') is-invalid @enderror" name="year" id="year"  wire:model="year">
                                <option value="" >Select Year</option>
                                <option value="2021" >2021</option>
                                <option value="2022" >2022</option>
                            </select>
                        </div>
                        @error('year') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount1">Amount 1 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount1') is-invalid @enderror" id="amount1" placeholder="amount1" wire:model="amount1">
                        </div>
                        @error('amount1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount2">Amount 2 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount2') is-invalid @enderror" id="amount2" placeholder="amount2" wire:model="amount2">
                        </div>
                        @error('amount2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount3">Amount 3 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount3') is-invalid @enderror" id="amount3" placeholder="amount3" wire:model="amount3">
                        </div>
                        @error('amount3') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount4">Amount 4 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount4" placeholder="amount4" wire:model="amount4">
                        </div>
                        @error('amount4') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount5">Amount 5 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount5') is-invalid @enderror" id="amount5" placeholder="amount5" wire:model="amount5">
                        </div>
                        @error('amount5') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount6">Amount 6 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount6') is-invalid @enderror" id="amount6" placeholder="amount6" wire:model="amount6">
                        </div>
                        @error('amount6') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <br/>
                        <button type="submit" class="btn btn-success btn-block">Update</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">
                    </div>
                    <div class="col-1">
                        <label for="amount7">Amount 7 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount7') is-invalid @enderror" id="amount7" placeholder="amount7" wire:model="amount7">
                        </div>
                        @error('amount7') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount8">Amount 8 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount8') is-invalid @enderror" id="amount8" placeholder="amount8" wire:model="amount8">
                        </div>
                        @error('amount8') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount9">Amount 9 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount9') is-invalid @enderror" id="amount9" placeholder="amount9" wire:model="amount9">
                        </div>
                        @error('amount9') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount10">Amount 10 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount10" placeholder="amount10" wire:model="amount10">
                        </div>
                        @error('amount10') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount11">Amount 11 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount11') is-invalid @enderror" id="amount11" placeholder="amount11" wire:model="amount11">
                        </div>
                        @error('amount11') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount12">Amount 12 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount12') is-invalid @enderror" id="amount12" placeholder="amount12" wire:model="amount12">
                        </div>
                        @error('amount12') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <br/>
                        <button onclick="location.reload();"  class="btn btn-primary btn-block">Refresh Page</button>
                    </div>
                </div>
            </form>
            @else
            <form wire:submit.prevent="storeEstimatedBudget">
                <div class="row">
                    <div class="col-2">
                        <label for="title">Year</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control @error('year') is-invalid @enderror" name="year" id="year"  wire:model="year">
                                <option value="" >Select Year</option>
                                <option value="2021" selected>2021</option>
                                <option value="2022" >2022</option>
                            </select>
                        </div>
                        @error('year') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount1">Amount 1 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount1') is-invalid @enderror" id="amount1" placeholder="amount1" wire:model="amount1">
                        </div>
                        @error('amount1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount2">Amount 2 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount2') is-invalid @enderror" id="amount2" placeholder="amount2" wire:model="amount2">
                        </div>
                        @error('amount2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount3">Amount 3 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount3') is-invalid @enderror" id="amount3" placeholder="amount3" wire:model="amount3">
                        </div>
                        @error('amount3') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount4">Amount 4 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount4" placeholder="amount4" wire:model="amount4">
                        </div>
                        @error('amount4') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount5">Amount 5 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount5') is-invalid @enderror" id="amount5" placeholder="amount5" wire:model="amount5">
                        </div>
                        @error('amount5') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount6">Amount 6 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount6') is-invalid @enderror" id="amount6" placeholder="amount6" wire:model="amount6">
                        </div>
                        @error('amount6') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <br/>
                        <button type="submit" class="btn btn-success btn-block">Add</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">
                    </div>
                    <div class="col-1">
                        <label for="amount7">Amount 7 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount7') is-invalid @enderror" id="amount7" placeholder="amount7" wire:model="amount7">
                        </div>
                        @error('amount7') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount8">Amount 8 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount8') is-invalid @enderror" id="amount8" placeholder="amount8" wire:model="amount8">
                        </div>
                        @error('amount8') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                        <label for="amount9">Amount 9 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount9') is-invalid @enderror" id="amount9" placeholder="amount9" wire:model="amount9">
                        </div>
                        @error('amount9') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount10">Amount 10 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount4') is-invalid @enderror" id="amount10" placeholder="amount10" wire:model="amount10">
                        </div>
                        @error('amount10') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount11">Amount 11 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount11') is-invalid @enderror" id="amount11" placeholder="amount11" wire:model="amount11">
                        </div>
                        @error('amount11') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="amount12">Amount 12 :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('amount12') is-invalid @enderror" id="amount12" placeholder="amount12" wire:model="amount12">
                        </div>
                        @error('amount12') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-1">
                    </div>
                </div>
            </form>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Amount 1</th>
                            <th>Amount 2</th>
                            <th>Amount 3</th>
                            <th>Amount 4</th>
                            <th>Amount 5</th>
                            <th>Amount 6</th>
                            <th>Amount 7</th>
                            <th>Amount 8</th>
                            <th>Amount 9</th>
                            <th>Amount 10</th>
                            <th>Amount 11</th>
                            <th>Amount 12</th>
                            <th>Action</th>
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
                            <x-EmptyDataLine col="14" text="No line found ..."  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Year</th>
                            <th>Amount 1</th>
                            <th>Amount 2</th>
                            <th>Amount 3</th>
                            <th>Amount 4</th>
                            <th>Amount 5</th>
                            <th>Amount 6</th>
                            <th>Amount 7</th>
                            <th>Amount 8</th>
                            <th>Amount 9</th>
                            <th>Amount 10</th>
                            <th>Amount 11</th>
                            <th>Amount 12</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
