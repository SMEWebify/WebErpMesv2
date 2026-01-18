<div class="col-md-12">
    <form wire:submit.prevent="save">
        <x-adminlte-card title="{{ __('general_content.automatic_email_reports_trans_key') }}" theme="info" maximizable>
            <p class="text-muted">{{ __('general_content.automatic_email_reports_help_trans_key') }}</p>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.report_trans_key') }}</th>
                            <th>{{ __('general_content.send_time_trans_key') }}</th>
                            <th class="text-center">{{ __('general_content.enabled_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reportTypes as $type => $label)
                            <tr>
                                <td>{{ __($label) }}</td>
                                <td>
                                    <input type="time" class="form-control" wire:model.defer="reports.{{ $type }}.send_time">
                                    @error("reports.$type.send_time") <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </td>
                                <td class="text-center align-middle">
                                    <input type="checkbox" class="form-check-input" wire:model.defer="reports.{{ $type }}.enabled">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
            </x-slot>
        </x-adminlte-card>
    </form>
</div>
