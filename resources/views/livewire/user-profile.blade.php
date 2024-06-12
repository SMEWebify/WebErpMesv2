
            <div class="col-md-9">
                <form wire:submit.prevent="updateProfile">
                    @include('include.alert-result')
                    <x-adminlte-card title="{{ __('general_content.about_setup_trans_key') }}" theme="primary" maximizable>

                            <div class="form-group">
                                <label for="name">{{ __('general_content.name_trans_key') }}</label>
                                <input wire:model.live="name" type="text" class="form-control" />
                                @error('name') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="email">{{ __('general_content.email_trans_key') }}</label> 
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input wire:model.live="email" type="email" class="form-control" />
                                </div>
                                @error('email') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="password">{{ __('general_content.password_account_trans_key') }}</label>
                                <input wire:model.live="password" type="password" class="form-control" />
                                @error('password') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                        </x-slot>
                    </x-adminlte-card>
                </form>

                <form wire:submit.prevent="updateInformation">
                    <x-adminlte-card title="{{ __('general_content.personnal_information_trans_key') }}" theme="warning" maximizable>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="personnal_phone_number">{{__('general_content.personnal_phone_trans_key') }}:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" wire:model.live="personnal_phone_number"  name="personnal_phone_number" class="form-control">
                                    </div>
                                    @error('personnal_phone_number') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="private_email">{{ __('general_content.personnal_email_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="text" wire:model.live="private_email"  name="private_email" class="form-control">
                                    </div>
                                    @error('private_email') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="born_date">{{ __('general_content.born_date_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control" wire:model.live="born_date"  name="born_date"  id="born_date">
                                    </div>
                                    @error('born_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="nationality">{{__('general_content.nationality_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                        </div>
                                        <input type="text" wire:model.live="nationality"  name="nationality" class="form-control">
                                    </div>
                                    @error('nationality') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="gender">{{ __('general_content.gender_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="gender" name="gender" id="gender">
                                            <option value="">{{ __('general_content.select_gender_trans_key') }}</option>
                                            <option value="1">{{ __('general_content.male_trans_key') }}</option>
                                            <option value="2">{{ __('general_content.female_trans_key') }}</option>
                                            <option value="3">{{ __('general_content.other_trans_key') }}</option>
                                        </select>
                                    </div>
                                    @error('gender') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="marital_status">{{ __('general_content.marital_status_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="marital_status" name="marital_status" id="marital_status">
                                            <option value="">{{ __('general_content.select_marital_status_trans_key') }}</option>
                                            <option value="1">{{ __('general_content.married_trans_key') }}</option>
                                            <option value="2">{{ __('general_content.single_trans_key') }}</option>
                                            <option value="3">{{ __('general_content.divorced_trans_key') }}</option>
                                            <option value="4">{{ __('general_content.widowed_trans_key') }}</option>
                                            <option value="5">{{ __('general_content.other_trans_key') }}</option>
                                        </select>
                                    </div>
                                    @error('marital_status') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="password">{{ __('general_content.driving_license_trans_key') }} :</label>
                                    <input wire:model.live="driving_license" type="text" class="form-control" />
                                    @error('driving_license') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="driving_license_exp_date">{{ __('general_content.driving_license_exp_date_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control" wire:model.live="driving_license_exp_date"  name="driving_license_exp_date"  id="driving_license_exp_date">
                                    </div>
                                    @error('driving_license_exp_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label>{{ __('general_content.ssn_num_trans_key') }} :</label>
                                    <input wire:model.live="ssn_num" type="text" class="form-control" />
                                    @error('ssn_num') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label>{{ __('general_content.nic_num_trans_key') }} :</label>
                                    <input wire:model.live="nic_num" type="text" class="form-control" />
                                    @error('nic_num') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <label for="InputWebSite">{{ __('general_content.adress_section_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="address1">{{ __('general_content.adress_trans_key') }} 1 :</label>
                                    <input type="text" wire:model.live="address1"  name="address1" class="form-control">
                                    @error('address1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="address2">{{ __('general_content.adress_trans_key') }} 2 :</label>
                                    <input type="text" wire:model.live="address2"  name="address2" class="form-control">
                                    @error('address2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="city">{{ __('general_content.city_trans_key') }} :</label>
                                    <input type="text" wire:model.live="city"  name="city" class="form-control">
                                    @error('city') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="postal_code">{{ __('general_content.postal_code_trans_key') }} :</label>
                                    <input type="text" wire:model.live="postal_code"  name="postal_code" class="form-control">
                                    @error('postal_code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="province">{{ __('general_content.province_trans_key') }}  :</label>
                                    <input type="text" wire:model.live="province"  name="province" class="form-control">
                                    @error('province') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="country">{{ __('general_content.country_trans_key') }} :</label>
                                    <input type="text" wire:model.live="country"  name="country" class="form-control">
                                    @error('country') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <label for="InputWebSite">{{ __('general_content.custom_section_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="custom1">{{ __('general_content.custom_trans_key') }} 1 :</label>
                                    <input wire:model.live="custom1" type="text" class="form-control" />
                                    @error('custom1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="custom2">{{ __('general_content.custom_trans_key') }} 2:</label>
                                    <input wire:model.live="custom2" type="text" class="form-control" />
                                    @error('custom2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="custom3">{{ __('general_content.custom_trans_key') }} 3:</label>
                                    <input wire:model.live="custom3" type="text" class="form-control" />
                                    @error('custom3') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="custom4">{{ __('general_content.custom_trans_key') }} 4</label>
                                    <input wire:model.live="custom4" type="text" class="form-control" />
                                    @error('custom4') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label>{{ __('general_content.about_you_trans_key') }}</label>
                                    <textarea class="form-control" wire:model.live="desc" rows="3" placeholder="..."></textarea>
                                    @error('desc') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                        </x-slot>
                    </x-adminlte-card>
                </form>
            </div>
