<?php

namespace App\Services;

use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;

class CompanyService
{
    protected $vies;

    public function __construct()
    {
        $this->vies = new Vies();
    }

    public function validateVatNumber($countryCode, $vatNumber)
    {      
        // Check if VAT validation is enabled via .env
        if (env('VAT_VALIDATION_ENABLED', true)) {
            try {
            // Checks if the VIES service is active
                if ($this->vies->getHeartBeat()->isAlive()) {
                    // Checks if the VIES service is active
                    $result = $this->vies->validateVat($countryCode, $vatNumber);
                    return $result->isValid();
                } else {
                    // Returns false if the service is not available
                    return false;
                }
            } catch (ViesException $viesException){
                // Handle the exception if there is a validation error (e.g. invalid country code)
                // You can either return false or handle it differently depending on your needs
                return false; 
            }
        }
        return true;
    }
}