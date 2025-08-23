@if(isset($Order) && $OrderSite)
    <x-adminlte-card title="{{ __('Implantations') }}" theme="secondary" maximizable>
        <form method="POST" action="{{ route('orders.site.implantation.store', ['order' => $Order->id, 'site' => $OrderSite->id]) }}">
            @csrf
            <div class="row">
                <div class="form-group col-md-5">
                    <input type="text" name="name" class="form-control" placeholder="{{ __('general_content.name_trans_key') }}">
                </div>
                <div class="form-group col-md-5">
                    <input type="text" name="description" class="form-control" placeholder="{{ __('general_content.description_trans_key') }}">
                </div>
                <div class="col-md-2">
                    <x-adminlte-button type="submit" label="{{ __('general_content.add_trans_key') }}" theme="success" icon="fas fa-plus"/>
                </div>
            </div>
        </form>
        <table class="table table-striped mt-2">
            <thead>
                <tr>
                    <th>{{ __('general_content.name_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($OrderSiteImplantations as $implantation)
                    <tr>
                        <td>{{ $implantation->name }}</td>
                        <td>{{ $implantation->description }}</td>
                        <td>
                            <form method="POST" action="{{ route('orders.site.implantation.destroy', ['order' => $Order->id, 'site' => $OrderSite->id, 'implantation' => $implantation->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">{{ __('general_content.no_data_trans_key') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-adminlte-card>
@endif

