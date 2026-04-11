@extends('adminlte::page')

@section('css')
  @vite(['resources/sass/app.scss'])
@stop

@section('title', __('general_content.update_detail_information_for_trans_key', ['label' => $line->label]))

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="h4 mb-0">
            <i class="fas fa-info-circle text-teal mr-2"></i>
            {{ __('general_content.update_detail_information_for_trans_key', ['label' => $line->label]) }}
        </h1>
        <a href="{{ route('orders.show', ['id' => $idOrder]) }}#Lines" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Retour à la commande
        </a>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    @include('include.alert-result')

    <form method="POST" action="{{ route('orders.update.detail.line', ['idOrder' => $idOrder, 'id' => $line->OrderLineDetails->id]) }}" enctype="multipart/form-data">
        @csrf

        <div class="accordion" id="orderLineDetailAccordion">

            {{-- Main features --}}
            <div class="card card-outline card-success mb-2">
                <div class="card-header">
                    <button class="btn btn-link text-start w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseMainFeatures" aria-expanded="true">
                        <span><i class="fas fa-stream text-success mr-2"></i> {{ __('general_content.main_features_trans_key') }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseMainFeatures" class="collapse show" data-parent="#orderLineDetailAccordion">
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>{{ __('general_content.material_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-mdb"></i></span></div>
                                    <input type="text" class="form-control" name="material" value="{{ $line->OrderLineDetails->material }}" placeholder="{{ __('general_content.material_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ __('general_content.thickness_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span></div>
                                    <input type="number" class="form-control" name="thickness" value="{{ $line->OrderLineDetails->thickness }}" step=".001">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ __('general_content.weight_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-weight-hanging"></i></span></div>
                                    <input type="number" class="form-control" name="weight" value="{{ $line->OrderLineDetails->weight }}" step=".001">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dimensions --}}
            <div class="card card-outline card-primary mb-2">
                <div class="card-header">
                    <button class="btn btn-link text-start w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseDimensions" aria-expanded="false">
                        <span><i class="fas fa-ruler-combined text-primary mr-2"></i> {{ __('general_content.dimensions_xyz_trans_key') }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseDimensions" class="collapse" data-parent="#orderLineDetailAccordion">
                    <div class="card-body">
                        <div class="row">
                            @foreach(['x_size' => 'X', 'y_size' => 'Y', 'z_size' => 'Z'] as $field => $axis)
                            <div class="form-group col-md-4">
                                <label>{{ $axis }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-ruler-combined"></i></span></div>
                                    <input type="number" class="form-control" name="{{ $field }}" value="{{ $line->OrderLineDetails->$field }}" step=".001">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="row">
                            @foreach(['x_oversize' => 'X oversize', 'y_oversize' => 'Y oversize', 'z_oversize' => 'Z oversize'] as $field => $label)
                            <div class="form-group col-md-4">
                                <label>{{ $label }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-ruler-combined"></i></span></div>
                                    <input type="number" class="form-control" name="{{ $field }}" value="{{ $line->OrderLineDetails->$field }}" step=".001">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Circular specs --}}
            <div class="card card-outline card-warning mb-2">
                <div class="card-header">
                    <button class="btn btn-link text-start w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseCircular" aria-expanded="false">
                        <span><i class="fas fa-circle-notch text-warning mr-2"></i> {{ __('general_content.circular_specs_trans_key') }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseCircular" class="collapse" data-parent="#orderLineDetailAccordion">
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-ruler-combined"></i></span></div>
                                    <input type="number" class="form-control" name="diameter" value="{{ $line->OrderLineDetails->diameter }}" placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-ruler-combined"></i></span></div>
                                    <input type="number" class="form-control" name="diameter_oversize" value="{{ $line->OrderLineDetails->diameter_oversize }}" placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hashtag"></i></span></div>
                                    <input type="number" class="form-control" name="bend_count" value="{{ $line->OrderLineDetails->bend_count }}" placeholder="{{ __('general_content.bend_count_trans_key') }}" step="1" min="0">
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-percentage"></i></span></div>
                                    <input type="number" class="form-control" name="material_loss_rate" value="{{ $line->OrderLineDetails->material_loss_rate }}" placeholder="{{ __('general_content.material_loss_rate_trans_key') }}" step=".001">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Files --}}
            <div class="card card-outline card-success mb-2">
                <div class="card-header">
                    <button class="btn btn-link text-start w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseFiles" aria-expanded="false">
                        <span><i class="fas fa-paperclip text-success mr-2"></i> {{ __('general_content.files_trans_key') }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseFiles" class="collapse" data-parent="#orderLineDetailAccordion">
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('general_content.cad_file_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-drafting-compass"></i></span></div>
                                    <input type="text" class="form-control" name="cad_file" value="{{ $line->OrderLineDetails->cad_file }}" placeholder="{{ __('general_content.cad_file_name_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('general_content.cam_file_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-cogs"></i></span></div>
                                    <input type="text" class="form-control" name="cam_file" value="{{ $line->OrderLineDetails->cam_file }}" placeholder="{{ __('general_content.cam_file_name_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('general_content.cad_file_path_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-folder-open"></i></span></div>
                                    <input type="text" class="form-control" name="cad_file_path" value="{{ $line->OrderLineDetails->cad_file_path }}" placeholder="{{ __('general_content.cad_file_path_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('general_content.cam_file_path_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-folder-open"></i></span></div>
                                    <input type="text" class="form-control" name="cam_file_path" value="{{ $line->OrderLineDetails->cam_file_path }}" placeholder="{{ __('general_content.cam_file_path_trans_key') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Custom requirements --}}
            <div class="card card-outline card-danger mb-2">
                <div class="card-header">
                    <button class="btn btn-link text-start w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseCustomReq" aria-expanded="false">
                        <span><i class="fas fa-tasks text-danger mr-2"></i> {{ __('general_content.custom_requirements_trans_key') }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseCustomReq" class="collapse" data-parent="#orderLineDetailAccordion">
                    <div class="card-body">
                        @php $requirements = $line->OrderLineDetails->custom_requirements ?? []; @endphp
                        @forelse($requirements as $index => $requirement)
                            <div class="form-row align-items-end mb-2">
                                <div class="form-group col-md-5 mb-0">
                                    <label>{{ __('general_content.label_trans_key') }}</label>
                                    <input type="text" class="form-control" name="custom_requirements[{{ $index }}][label]" value="{{ $requirement['label'] ?? '' }}" placeholder="{{ __('general_content.label_trans_key') }}">
                                </div>
                                <div class="form-group col-md-5 mb-0">
                                    <label>{{ __('general_content.value_trans_key') }}</label>
                                    <input type="text" class="form-control" name="custom_requirements[{{ $index }}][value]" value="{{ $requirement['value'] ?? '' }}" placeholder="{{ __('general_content.value_trans_key') }}">
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">{{ __('general_content.no_custom_requirement_trans_key') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card card-outline card-secondary mb-2">
                <div class="card-header">
                    <button class="btn btn-link text-start w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseComments" aria-expanded="false">
                        <span><i class="fas fa-comments text-secondary mr-2"></i> {{ __('general_content.comment_trans_key') }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseComments" class="collapse" data-parent="#orderLineDetailAccordion">
                    <div class="card-body">
                        <div class="row">
                            <x-FormTextareaComment label="{{ __('general_content.internal_comment_trans_key') }}" name="internal_comment" comment="{{ $line->OrderLineDetails->internal_comment }}" />
                        </div>
                        <div class="row mt-3">
                            <x-FormTextareaComment label="{{ __('general_content.external_comment_trans_key') }}" name="external_comment" comment="{{ $line->OrderLineDetails->external_comment }}" />
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end accordion --}}

        <div class="mt-3">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            <a href="{{ route('orders.show', ['id' => $idOrder]) }}#Lines" class="btn btn-outline-secondary ml-2">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </form>

    {{-- Image upload --}}
    <div class="card card-outline card-success mt-4">
        <div class="card-header py-2">
            <h6 class="card-title mb-0">
                <i class="fas fa-paperclip text-success mr-2"></i>
                {{ __('general_content.attached_file_trans_key') }}
            </h6>
        </div>
        <div class="card-body">
            <form action="{{ route('orders.update.detail.picture', ['idOrder' => $idOrder, 'id' => $line->OrderLineDetails->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label for="picture">{{ __('general_content.picture_file_trans_key') }} (jpeg,png,jpg,gif,svg | max: 10240 Ko)</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-image"></i></span></div>
                    <div class="custom-file">
                        <input type="hidden" name="id" value="{{ $line->id }}">
                        <input type="file" class="custom-file-input" name="picture" id="picture">
                        <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                    </div>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
