<x-adminlte-card title="{{ __('general_content.assets_trans_key') }}" theme="primary" maximizable>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ __('general_content.name_trans_key') }}</th>
                    <th>Category</th>
                    <th>{{ __('general_content.ressource_trans_key') }}</th>
                    <th>Acquisition value</th>
                    <th>Acquisition date</th>
                    <th>Depreciation duration (months)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                    <tr>
                        <td><a href="{{ route('assets.show', $asset->id) }}">{{ $asset->name }}</a></td>
                        <td>{{ $asset->category }}</td>
                        <td>{{ $asset->methodsRessource?->label ?? __('general_content.no_data_trans_key') }}</td>
                        <td>{{ $asset->acquisition_value }}</td>
                        <td>{{ $asset->acquisition_date->format('Y-m-d') }}</td>
                        <td>{{ $asset->depreciation_duration }}</td>
                        <td class="text-right">
                            <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Edit">
                                <i class="fa fa-lg fa-fw fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">{{ __('general_content.no_data_trans_key') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <a href="{{ route('assets.create') }}" class="btn btn-primary">{{ __('general_content.new_trans_key') }} {{ __('general_content.asset_trans_key') }}</a>
        {{ $assets->links() }}
    </div>
</x-adminlte-card>
