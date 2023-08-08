
            <div class="col-md-9">
                <form wire:submit.prevent="updateProfile">
                    @include('include.alert-result')
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Account setup</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input wire:model="name" type="text" class="form-control" />
                                @error('name') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input wire:model="email" type="email" class="form-control" />
                                </div>
                                @error('email') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="password">Password (only for confirm changes)</label>
                                <input wire:model="password" type="password" class="form-control" />
                                @error('password') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                        </div>
                    </div>
                </form>

                <form wire:submit.prevent="updateInformation">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Personnal information</h3>
                        </div>
                        <div class="card-body">

                            <div class="row">
                                <div class="col-3">
                                    <label for="personnal_phone_number">Personnal phone number:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" wire:model="personnal_phone_number"  name="personnal_phone_number" class="form-control">
                                    </div>
                                    @error('personnal_phone_number') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="private_email">Personnal email:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="text" wire:model="private_email"  name="private_email" class="form-control">
                                    </div>
                                    @error('private_email') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="born_date">Born date</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control" wire:model="born_date"  name="born_date"  id="born_date">
                                    </div>
                                    @error('born_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="nationality">Nationality :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                        </div>
                                        <input type="text" wire:model="nationality"  name="nationality" class="form-control">
                                    </div>
                                    @error('nationality') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-3">
                                    <label for="gender">Gender</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" wire:model="gender" name="gender" id="gender">
                                            <option value="">Select gender</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                            <option value="3">Other</option>
                                        </select>
                                    </div>
                                    @error('gender') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>

                                <div class="col-3">
                                    <label for="marital_status">Marital status</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" wire:model="marital_status" name="marital_status" id="marital_status">
                                            <option value="">Select marital  status</option>
                                            <option value="1">Married</option>
                                            <option value="2">Single</option>
                                            <option value="3">Divorced</option>
                                            <option value="4">Widowed</option>
                                            <option value="5">Other</option>
                                        </select>
                                    </div>
                                    @error('marital_status') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-3">
                                    <label for="password">Driving license :</label>
                                    <input wire:model="driving_license" type="text" class="form-control" />
                                    @error('driving_license') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="driving_license_exp_date">Driving license exp date</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control" wire:model="driving_license_exp_date"  name="driving_license_exp_date"  id="driving_license_exp_date">
                                    </div>
                                    @error('driving_license_exp_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label>SSN num:</label>
                                    <input wire:model="ssn_num" type="text" class="form-control" />
                                    @error('ssn_num') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label>NIC num</label>
                                    <input wire:model="nic_num" type="text" class="form-control" />
                                    @error('nic_num') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">Adress section</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <label for="address1">Address 1 :</label>
                                    <input type="text" wire:model="address1"  name="address1" class="form-control">
                                    @error('address1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-6">
                                    <label for="address2">Address 2 :</label>
                                    <input type="text" wire:model="address2"  name="address2" class="form-control">
                                    @error('address2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3">
                                    <label for="city">City :</label>
                                    <input type="text" wire:model="city"  name="city" class="form-control">
                                    @error('city') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="postal_code">Postal code :</label>
                                    <input type="text" wire:model="postal_code"  name="postal_code" class="form-control">
                                    @error('postal_code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="province">Province :</label>
                                    <input type="text" wire:model="province"  name="province" class="form-control">
                                    @error('province') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="country">Country:</label>
                                    <input type="text" wire:model="country"  name="country" class="form-control">
                                    @error('country') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">Custom section</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-3">
                                    <label for="custom1">Custom 1 :</label>
                                    <input wire:model="custom1" type="text" class="form-control" />
                                    @error('custom1') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="custom2">Custom 2:</label>
                                    <input wire:model="custom2" type="text" class="form-control" />
                                    @error('custom2') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="custom3">Custom 3:</label>
                                    <input wire:model="custom3" type="text" class="form-control" />
                                    @error('custom3') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="custom4">Custom 4</label>
                                    <input wire:model="custom4" type="text" class="form-control" />
                                    @error('custom4') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label>About you</label>
                                    <textarea class="form-control" wire:model="desc" rows="3" placeholder="Enter ..."></textarea>
                                    @error('desc') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div> 
                        <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                        </div>
                    </div>
                </form>
            </div>
