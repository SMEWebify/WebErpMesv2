<?php

namespace App\View\Components;

use Illuminate\View\Component;

class HeaderPrint extends Component
{
    public $factoryName;
    public $factoryAddress;
    public $factoryZipcode;
    public $factoryCity;
    public $factoryPhoneNumber;
    public $factoryMail;

    public $companieLabel;
    public $companieCivility;
    public $companieFirstName;
    public $companieName;
    public $companieAdress;
    public $companieZipcode;
    public $companieCity;
    public $companieCountry;
    public $companieNumber;
    public $companieMail;

    public $documentName;
    public $code;
    public $customerReference;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($factoryName, $factoryAddress,  $factoryZipcode, $factoryCity,  $factoryPhoneNumber,  $factoryMail,
                                $companieLabel, $companieCivility,  $companieFirstName, $companieName, $companieAdress,  $companieZipcode, 
                                $companieCity,$companieCountry,$companieNumber, $companieMail, $documentName, $code ,$customerReference )
    {
        $this->factoryName = $factoryName;
        $this->factoryAddress = $factoryAddress;
        $this->factoryZipcode = $factoryZipcode;
        $this->factoryCity = $factoryCity;
        $this->factoryPhoneNumber = $factoryPhoneNumber;
        $this->factoryMail = $factoryMail;

        $this->companieLabel = $companieLabel;
        $this->companieCivility = $companieCivility;
        $this->companieFirstName = $companieFirstName;
        $this->companieName = $companieName;
        $this->companieAdress = $companieAdress;
        $this->companieZipcode = $companieZipcode;
        $this->companieCity = $companieCity;
        $this->companieCountry = $companieCountry;
        $this->companieNumber = $companieNumber;
        $this->companieMail = $companieMail;
    
        $this->documentName = $documentName;
        $this->code = $code;
        $this->customerReference = $customerReference; 
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.header-print');
    }
}
