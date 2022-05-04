<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Edit profile</h3>
    </div>
    <form wire:submit.prevent="updateProfile">
        <div class="card-body">
            @include('include.alert-result')
            <div class="form-group">
                <label>Name</label>
                <input wire:model="name" type="text" class="form-control" />
                @error('name') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    </div>
                    <input wire:model="email" type="email" class="form-control" />
                </div>
                @error('email') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
            <div class="form-group">
                <label>Password</label>
                <input wire:model="password" type="password" class="form-control" />
                @error('password') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
            <div class="form-group">
                <label>Personnal phone number:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    </div>
                    <input type="text" wire:model="personnal_phone_number"  name="personnal_phone_number" class="form-control">
                </div>
                @error('personnal_phone_number') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
            <div class="form-group">
                <label for="label">Born date</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                    </div>
                    <input type="date" class="form-control" wire:model="born_date"  name="born_date"  id="born_date">
                </div>
                @error('born_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
            <div class="form-group">
                <label>About you</label>
                <textarea class="form-control" wire:model="desc" rows="3" placeholder="Enter ..."></textarea>
                @error('desc') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
            
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
    </form>
</div>