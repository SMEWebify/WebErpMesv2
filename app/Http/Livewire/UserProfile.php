<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class UserProfile extends Component
{
    public $userId;
    public $name;
    public $email;
    public $password;

    public $current_hashed_password;

    public $prevName;
    public $prevEmail;
    
    // Validation Rules
    protected $rules = [
        'name' =>'required',
        'email'=>'required|email',
        'password' => 'required'
    ];

    public function mount()
    {
        $this->userId = auth()->user()->id;
        $model = User::find($this->userId);
        $this->name = $model->name;
        $this->email = $model->email;
        $this->current_hashed_password = $model->password;
    }

    public function render()
    {
        return view('livewire.user-profile');
    }

    public function updateProfile()
    {
            // Validate request
            $this->validate();
            User::find($this->userId)->fill([
                'name'=>$this->name,
                'email'=>$this->email,
            ])->save();

        session()->flash('success','Profile updated Successfully');
    }
}