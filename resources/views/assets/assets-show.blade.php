@extends('adminlte::page')

@section('title', $asset->name)

@section('content_header')
    <h1>{{ $asset->name }}</h1>
@stop

@section('content')
    <x-adminlte-card title="{{ $asset->name }}" theme="primary" maximizable>
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>Category</dt>
                    <dd>{{ $asset->category }}</dd>
                    <dt>Acquisition value</dt>
                    <dd>{{ $asset->acquisition_value }}</dd>
                    <dt>Acquisition date</dt>
                    <dd>{{ $asset->acquisition_date->format('Y-m-d') }}</dd>
                    <dt>Depreciation duration</dt>
                    <dd>{{ $asset->depreciation_duration }} months</dd>
                </dl>
                <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-info">{{ __('general_content.edit_trans_key') }}</a>
                <form method="POST" action="{{ route('assets.destroy', $asset->id) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('general_content.delete_trans_key') }}</button>
                </form>
            </div>
        </div>
        <h2>{{ __('general_content.accounting_trans_key') }}</h2>
        <ul>
            @foreach($asset->accountingEntries as $entry)
                <li>{{ $entry->entry_label }} - {{ $entry->debit_amount }} / {{ $entry->credit_amount }}</li>
            @endforeach
        </ul>
    </x-adminlte-card>
@stop
