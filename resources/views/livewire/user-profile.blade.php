<form wire:submit.prevent="updateProfile">
    <div class="row">
        <div class="col-4">
            <label>Name</label>
            <input wire:model="name" type="text" class="form-control" />
            @error('name') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label>Email</label>
            <input wire:model="email" type="email" class="form-control" />
            @error('email') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
    </div>
    <div class="row">
        <div class="col-4">
            <label>Password</label>
            <input wire:model="password" type="password" class="form-control" />
            @error('password') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <br/>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </div>
</form>
