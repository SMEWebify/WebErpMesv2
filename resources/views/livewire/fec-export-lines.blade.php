<div>
    <div class="card">
        <div class="card-body">
            <div class="card-body ">
                <div class="row">
                    <div class="btn-group">
                        <button 
                                class="btn btn-info float-sm-right"
                                type="button"
                                wire:click="export('csv')"
                                wire:loading.attr="disabled"  >
                            CSV
                        </button> 
                    </div>
                    <div class="btn-group">
                        <button 
                                class="btn btn-danger float-sm-right"
                                type="button"
                                wire:click="export('xlsx')"
                                wire:loading.attr="disabled"  >
                            XLS
                        </button> 
                    </div>
                    <div class="btn-group">
                        <button
                                class="btn btn-primary float-sm-right"
                                type="button"
                                wire:click="export('pdf')"
                                wire:loading.attr="disabled"  >
                            PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filtres par journal_code -->
            <div class="row mt-4">
                <div class="form-group col-2">
                    <label>Journaux</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="ACHAT" wire:click="toggleJournalCodeFilter('ACHAT')" @if(in_array('ACHAT', $journal_code_filters)) checked @endif>
                        <label class="form-check-label">ACHAT</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="VENT" wire:click="toggleJournalCodeFilter('VENT')" @if(in_array('VENT', $journal_code_filters)) checked @endif>
                        <label class="form-check-label">VENT</label>
                    </div>
                </div>

                <!-- Filtres par date -->
                <div class="form-group col-2">
                    <label for="start_date">{{ __('general_content.start_date_trans_key') }}  :</label>
                    <input type="date" class="form-control" id="start_date" wire:model="start_date">
                </div>
                <div class="form-group col-2">
                    <label for="end_date">{{ __('general_content.end_date_trans_key') }} :</label>
                    <input type="date" class="form-control" id="end_date" wire:model="end_date">
                </div>
                <div class="form-group col-2 align-self-end">
                    <button class="btn btn-danger" wire:click="applyFilters">{{ __('general_content.submit_trans_key') }}</button>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <tbody>
                        @forelse($FecExportLineslist as $FecExportLines)
                            <tr>
                                <td>{{ $FecExportLines->journal_code }}</td>
                                <td>{{ $FecExportLines->journal_label }}</td>
                                <td>{{ $FecExportLines->sequence_number }}</td>
                                <td>{{ $FecExportLines->accounting_date }}</td>
                                <td>{{ $FecExportLines->account_number }}</td>
                                <td>{{ $FecExportLines->account_label }}</td>
                                <td>{{ $FecExportLines->justification_reference }} </td>
                                <td>{{ $FecExportLines->justification_date }} </td>
                                <td>{{ $FecExportLines->auxiliary_account_number }} </td>
                                <td>{{ $FecExportLines->auxiliary_account_label }} </td>
                                <td>{{ $FecExportLines->document_reference }} </td>
                                <td>{{ $FecExportLines->document_date }} </td>
                                <td>{{ $FecExportLines->entry_label }} </td>
                                <td>{{ $FecExportLines->formatted_debit_amount }}</td>
                                <td>{{ $FecExportLines->formatted_credit_amount }}</td>
                                <td>{{ $FecExportLines->entry_lettering }} </td>
                                <td>{{ $FecExportLines->currency_code }} </td>
                                <td>
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" 
                                                type="checkbox" 
                                                wire:click="toggleSelected({{ $FecExportLines->id }})" 
                                                @if($selectedFecLine->contains($FecExportLines->id)) checked @endif
                                                id="select-{{ $FecExportLines->id }}">
                                        <label for="select-{{ $FecExportLines->id }}" class="custom-control-label">
                                            {{ __('general_content.add_export_trans_key') }}
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<!-- /.card-body -->
</div>