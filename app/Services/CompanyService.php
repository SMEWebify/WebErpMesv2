<?php

namespace App\Services;

use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;
use Illuminate\Support\Facades\Log;

class CompanyService
{
    protected $vies;

    public function __construct()
    {
        $this->vies = new Vies();
    }

    /**
     * Validates a VAT number for a given country code.
     *
     * Returns true when the VIES service confirms the number is valid, or when
     * VIES is temporarily unreachable (rate-limited, soap fault, network error) —
     * blocking a client save because the European service has a hiccup is worse
     * than accepting a number we could not verify server-side.
     * Returns false only when VIES actively responds that the number is invalid.
     */
    public function validateVatNumber($countryCode, $vatNumber)
    {
        if (! env('VAT_VALIDATION_ENABLED', true)) {
            return true;
        }

        try {
            if (! $this->vies->getHeartBeat()->isAlive()) {
                Log::warning('VIES service unreachable (heartbeat), VAT number accepted without check', [
                    'country' => $countryCode,
                    'vat' => $vatNumber,
                ]);
                return true;
            }

            return $this->vies->validateVat($countryCode, $vatNumber)->isValid();
        } catch (ViesException $e) {
            // Malformed input (bad country code, etc.) — genuine invalid.
            return false;
        } catch (ViesServiceException $e) {
            // Backend saturated / SOAP fault (e.g. MS_MAX_CONCURRENT_REQ).
            Log::warning('VIES service unavailable, VAT number accepted without check', [
                'country' => $countryCode,
                'vat' => $vatNumber,
                'error' => $e->getMessage(),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('VIES call failed, VAT number accepted without check', [
                'country' => $countryCode,
                'vat' => $vatNumber,
                'error' => $e->getMessage(),
            ]);
            return true;
        }
    }
}
