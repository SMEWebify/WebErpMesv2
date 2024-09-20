<?php

namespace App\Rules;

use App\Services\CompanyService;
use Illuminate\Contracts\Validation\Rule;

class ValidVatNumber implements Rule
{
    protected $vatService;

    public function __construct(CompanyService $vatService)
    {
        $this->vatService = $vatService;
    }

    public function passes($attribute, $value)
    {
 
        if (empty($value)) {
            return true; // Pass if the VAT number is empty (nullable case)
        }

        $countryCode = substr($value, 0, 2);
        $vatNumber = substr($value, 2);

        return $this->vatService->validateVatNumber($countryCode, $vatNumber);
    }

    public function message()
    {
        return 'The VAT number is invalid.';
    }
}
