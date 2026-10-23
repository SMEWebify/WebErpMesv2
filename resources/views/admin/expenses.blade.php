@extends('adminlte::page')

@section('title', __('general_content.expense_report_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.expense_report_trans_key') }} {{ $UserExpenseReports->label }}</h1>
@stop

@section('content')
@include('include.alert-result')
<div class="row ">
    @if($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
    <div class="col-md-8">
    @else
    <div class="col-md-12">
    @endif
    <x-adminlte-card title="{{ __('general_content.expense_report_trans_key') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>{{__('general_content.categories_trans_key') }}</th>
                    <th>{{__('general_content.date_trans_key') }}</th>
                    <th>{{__('general_content.location_expense_trans_key') }}</th>
                    <th>{{__('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.amount_trans_key') }}</th>
                    <th>{{__('general_content.payer_trans_key') }}</th>
                    <th>{{__('general_content.tax_trans_key') }}</th>
                    <th>{{__('general_content.order_trans_key') }}</th>
                    <th>{{__('general_content.scan_file_trans_key') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($UserExpenseReports->expenses as $Expense)
                <tr>
                    <td>{{ $Expense->category->label }}</td>
                    <td>{{ $Expense->expense_date }}</td>
                    <td>{{ $Expense->location }}</td>
                    <td>{{ $Expense->description }}</td>
                    <td>{{ $Expense->amount }} {{ $Factory->curency }}</td>
                    <td>{{ $Expense->payer->name }}</td>
                    <td>{{ $Expense->tax }}</td>
                    <td>
                        @if($Expense->order_id)
                        <x-OrderButton id="{{ $Expense->order_id }}" code="{{ $Expense->order->code }}"  />
                        @endif
                    </td>
                    <td>
                        @if($Expense->receipt)
                        <a href="{{ route('files.raw', ['file' => $Expense->file_id]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="{{ $Expense->receipt->original_file_name ?: $Expense->receipt->name }}">
                            <i class="fas fa-receipt"></i>
                        </a>
                        @elseif($Expense->scan_file)
                        <div class="btn-group btn-group-sm">
                            <x-button-text-view :route="route('human.resources.expense.file', ['filename' => $Expense->scan_file])" :downloadFile="$Expense->scan_file" />
                        </div>
                        @endif
                    </td>
                    <td class=" py-0 align-middle">
                        @if($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
                        <!-- Button Modal -->
                        <div class="btn-group btn-group-sm">
                            <x-ButtonTextEdit :modalTarget="'Expense' . $Expense->id" />
                        </div>
                        <!-- Modal {{ $Expense->id }} -->
                        <x-adminlte-modal id="Expense{{ $Expense->id }}" title="Update {{ $Expense->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                            <form method="POST" action="{{ route('human.resources.update.expense.line', ['id' => $Expense->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="category_id">{{ __('general_content.categories_trans_key') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        <select class="form-select" name="category_id" id="category_id">
                                            
                                            <option value="" >{{ __('general_content.select_categorie_trans_key') }}</option>
                                            @foreach ($UserExpenseCategoriesSelect as $item)
                                            <option value="{{ $item->id }}" @if($Expense->category_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="expense_date">{{ __('general_content.date_trans_key') }}</label>
                                    <input type="date" class="form-control" name="expense_date"  id="expense_date" value="{{ $Expense->expense_date }}">
                                </div>
                                <div class="mb-3">
                                    <label for="location">{{__('general_content.location_expense_trans_key') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        <input type="text" class="form-control" name="location"  id="location" placeholder="{{__('general_content.location_expense_trans_key') }}" value="{{ $Expense->location }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description">{{__('general_content.description_trans_key') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        <input type="text" class="form-control" name="description"  id="description" placeholder="{{__('general_content.description_trans_key') }}" value="{{ $Expense->description }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="amount">{{__('general_content.amount_trans_key') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $Factory->curency }}</span>
                                        <input type="number" class="form-control" name="amount"  id="amount" placeholder="{{__('general_content.amount_trans_key') }}" step=".001" value="{{ $Expense->amount }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="payer_id">{{ __('general_content.payer_trans_key') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <select class="form-select" name="payer_id" id="payer_id">
                                            @foreach ($userSelect as $item)
                                            <option value="{{ $item->id }}" @if($Expense->payer_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="amount">{{__('general_content.tax_trans_key') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">%</span>
                                        <input type="number" class="form-control" name="tax"  id="tax" placeholder="{{__('general_content.tax_trans_key') }}" step=".001" value="{{ $Expense->tax }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <x-adminlte-select-bs name="order_id" id="order_id" label="{{ __('general_content.select_order_trans_key') }}" label-class="text-secondary"
                                        igroup-size="s" data-placeholder="{{ __('general_content.select_order_line_trans_key') }}">
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text text-bg-secondary bg-gradient">
                                                <i class="fas fa-list"></i>
                                            </div>
                                        </x-slot>
                                        <option value="null">{{ __('general_content.select_order_trans_key') }}</option>
                                        @foreach ($OrderLineList as $item)
                                        <option value="{{ $item->id }}" @if($Expense->order_id == $item->id  ) Selected @endif>#{{ $item->id }} - {{ $item->code }}</option>
                                        @endforeach
                                    </x-adminlte-select-bs>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                </div>
                            </form>
                        </x-adminlte-modal>
                        @endif
                    </td>
                </tr>
                @empty
                    <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.categories_trans_key') }}</th>
                        <th>{{__('general_content.date_trans_key') }}</th>
                        <th>{{__('general_content.location_expense_trans_key') }}</th>
                        <th>{{__('general_content.description_trans_key') }}</th>
                        <th>{{__('general_content.amount_trans_key') }}</th>
                        <th>{{__('general_content.payer_trans_key') }}</th>
                        <th>{{__('general_content.tax_trans_key') }}</th>
                        <th>{{__('general_content.order_trans_key') }}</th>
                        <th>{{__('general_content.scan_file_trans_key') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-adminlte-card>
    <!-- /.card secondary -->
    @unless($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
        @include('admin.partials.expense-receipts', ['canEdit' => false])
    @endunless
    </div>
    @if($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
    <div class="col-md-4">
        @include('admin.partials.expense-receipts', ['canEdit' => true])
        <x-adminlte-card title="{{ __('general_content.new_expense_report_trans_key') }}" theme="secondary" maximizable>
            <form  method="POST" action="{{ route('human.resources.create.expense.line', ['report_id' => $UserExpenseReports->id])  }}" class="form-horizontal" enctype="multipart/form-data"
                   id="expense-line-form"
                   data-receipts-url="{{ route('human.resources.expense.receipts.json', ['report_id' => $UserExpenseReports->id]) }}"
                   data-read-url="{{ route('human.resources.expense.receipts.read', ['report_id' => $UserExpenseReports->id, 'file_id' => '__ID__']) }}">
                @csrf
                <div class="mb-3">
                    <label for="receipt_file_id">{{ __('general_content.expense_receipt_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-receipt"></i></span>
                        <select class="form-select" name="file_id" id="receipt_file_id">
                            <option value="">{{ __('general_content.expense_receipt_none_trans_key') }}</option>
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="receipt-read" disabled>
                            <i class="fas fa-wand-magic-sparkles"></i> {{ __('general_content.expense_receipt_read_trans_key') }}
                        </button>
                    </div>
                    <div class="form-text">{{ __('general_content.expense_receipt_ai_notice_trans_key') }}</div>
                    <div id="receipt-feedback" class="small mt-1" role="status"></div>
                </div>
                <div class="mb-3">
                    <label for="category_id">{{ __('general_content.categories_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        <select class="form-select" name="category_id" id="category_id">
                            
                            <option value="" >{{ __('general_content.select_categorie_trans_key') }}</option>
                            @foreach ($UserExpenseCategoriesSelect as $item)
                            <option value="{{ $item->id }}" >{{ $item->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="expense_date">{{ __('general_content.date_trans_key') }}</label>
                    <input type="date" class="form-control" name="expense_date"  id="expense_date" value="{{ old('expense_date', now()->toDateString()) }}">
                </div>
                <div class="mb-3">
                    <label for="location">{{__('general_content.location_expense_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        <input type="text" class="form-control" name="location"  id="location" placeholder="{{__('general_content.location_expense_trans_key') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description">{{__('general_content.description_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        <input type="text" class="form-control" name="description"  id="description" placeholder="{{__('general_content.description_trans_key') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="amount">{{__('general_content.amount_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ $Factory->curency }}</span>
                        <input type="number" class="form-control" name="amount"  id="amount" placeholder="{{__('general_content.amount_trans_key') }}" step=".001">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="payer_id">{{ __('general_content.payer_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <select class="form-select" name="payer_id" id="payer_id">
                            @foreach ($userSelect as $item)
                            <option value="{{ $item->id }}" >{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="tax">{{__('general_content.tax_trans_key') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">%</span>
                        <input type="number" class="form-control" name="tax"  id="tax" placeholder="{{__('general_content.tax_trans_key') }}" step=".001">
                    </div>
                </div>
                
                <div class="mb-3">
                    <x-adminlte-select-bs :config="['allowEmptyOption' => true]" name="order_id" id="order_id" label="{{ __('general_content.select_order_trans_key') }}" label-class="text-secondary"
                        igroup-size="s" data-placeholder="{{ __('general_content.select_order_line_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text text-bg-secondary bg-gradient">
                                <i class="fas fa-list"></i>
                            </div>
                        </x-slot>
                        <option value="">{{ __('general_content.select_order_trans_key') }}</option>
                        @foreach ($OrderLineList as $item)
                        <option value="{{ $item->id }}">#{{ $item->id }} - {{ $item->code }}</option>
                        @endforeach
                    </x-adminlte-select-bs>
                </div>
                <div class="card-footer">
                    <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
            </form>
        </x-adminlte-card>
    </div>
    @endif
    <!-- /.card secondary -->
  </div>
  <!-- /.row -->
</div>
<!-- /.card -->
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
<script>
(() => {
    const form = document.getElementById('expense-line-form');
    if (!form) return;

    const select = document.getElementById('receipt_file_id');
    const button = document.getElementById('receipt-read');
    const feedback = document.getElementById('receipt-feedback');
    const labels = {
        read: @json(__('general_content.expense_receipt_read_trans_key')),
        reading: @json(__('general_content.expense_receipt_reading_trans_key')),
        done: @json(__('general_content.expense_receipt_read_done_trans_key')),
        failed: @json(__('general_content.expense_receipt_failed_trans_key')),
    };

    const say = (text, tone) => {
        feedback.textContent = text || '';
        feedback.className = 'small mt-1 ' + (tone ? 'text-' + tone : '');
    };

    // Les justificatifs se déposent dans la carte voisine sans recharger la
    // page : la liste est relue à chaque ouverture du menu.
    const refresh = async () => {
        try {
            const response = await fetch(form.dataset.receiptsUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const { receipts } = await response.json();
            const current = select.value;
            [...select.options].slice(1).forEach((option) => option.remove());
            receipts.forEach(({ id, name }) => select.add(new Option(name, id)));
            select.value = receipts.some((r) => String(r.id) === current) ? current : '';
            button.disabled = select.value === '';
        } catch (e) { /* liste inchangée */ }
    };

    const fill = (name, value) => {
        const field = form.elements[name];
        if (field && value !== null && value !== undefined && value !== '') {
            field.value = value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    select.addEventListener('focus', refresh);
    select.addEventListener('change', () => { button.disabled = select.value === ''; say(''); });

    button.addEventListener('click', async () => {
        if (!select.value) return;
        button.disabled = true;
        button.lastChild.textContent = ' ' + labels.reading;
        say('');
        try {
            const response = await fetch(form.dataset.readUrl.replace('__ID__', select.value), {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                say(data.message || labels.failed, 'danger');
                return;
            }
            const expense = data.expense || {};
            ['expense_date', 'amount', 'tax', 'location', 'description', 'category_id']
                .forEach((name) => fill(name, expense[name]));
            say(labels.done, 'success');
        } catch (e) {
            say(labels.failed, 'danger');
        } finally {
            button.disabled = select.value === '';
            button.lastChild.textContent = ' ' + labels.read;
        }
    });

    refresh();
})();
</script>
@stop