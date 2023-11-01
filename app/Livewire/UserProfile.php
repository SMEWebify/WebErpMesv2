<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class UserProfile extends Component
{
    public $userId;
    public $name;
    public $email;
    public $password;
    public $personnal_phone_number;
    public $born_date;
    public $desc;
    public $nationality;
    public $gender;
    public $marital_status;
    public $ssn_num;
    public $nic_num;
    public $driving_license;
    public $driving_license_exp_date;
    public $address1;
    public $address2;
    public $city;
    public $country;
    public $province;
    public $postal_code;
    public $home_phone;
    public $mobile_phone;
    public $private_email;
    public $custom1;
    public $custom2;
    public $custom3;
    public $custom4;
    
    public $current_hashed_password;

    public $prevName;
    public $prevEmail;
    
    // Validation Rules
    protected $rules = [
        'name' =>'required',
        'email'=>'required|email',
        'password' => 'required',
    ];

    public function mount()
    {
        $this->userId = auth()->user()->id;
        $User = User::find($this->userId);
        $this->name = $User->name;
        $this->email = $User->email;
        $this->current_hashed_password = $User->password;
        $this->personnal_phone_number = $User->personnal_phone_number;
        $this->born_date = $User->born_date;
        $this->desc = $User->desc;
        $this->nationality = $User->nationality;
        $this->gender = $User->gender;
        $this->marital_status = $User->marital_status;
        $this->ssn_num = $User->ssn_num;
        $this->nic_num = $User->nic_num;
        $this->driving_license = $User->driving_license;
        $this->driving_license_exp_date = $User->driving_license_exp_date;
        $this->address1 = $User->address1;
        $this->address2 = $User->address2;
        $this->city = $User->city;
        $this->country = $User->country;
        $this->province = $User->province;
        $this->postal_code = $User->postal_code;
        $this->home_phone = $User->home_phone;
        $this->mobile_phone = $User->mobile_phone;
        $this->private_email = $User->private_email;
        $this->custom1 = $User->custom1;
        $this->custom2 = $User->custom2;
        $this->custom3 = $User->custom3;
        $this->custom4 = $User->custom4;
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

    
    public function updateInformation()
    {
            // Validate request
            User::find($this->userId)->fill([
                'personnal_phone_number'=>$this->personnal_phone_number,
                'born_date'=>$this->born_date,
                'desc'=>$this->desc,
                'personnal_phone_number'=>$this->personnal_phone_number,
                'nationality'=> $this->nationality,
                'gender'=> $this->gender,
                'marital_status'=> $this->marital_status,
                'ssn_num'=> $this->ssn_num,
                'nic_num'=> $this->nic_num,
                'driving_license'=> $this->driving_license,
                'driving_license_exp_date'=> $this->driving_license_exp_date,
                'address1'=> $this->address1,
                'address2'=> $this->address2,
                'city'=> $this->city,
                'country'=> $this->country,
                'province'=> $this->province,
                'postal_code'=> $this->postal_code,
                'home_phone'=> $this->home_phone,
                'mobile_phone'=> $this->mobile_phone,
                'private_email'=> $this->private_email,
                'custom1'=> $this->custom1,
                'custom2'=> $this->custom2,
                'custom3'=> $this->custom3,
                'custom4'=> $this->custom4,
            ])->save();

        session()->flash('success','Profile updated Successfully');
    }
}