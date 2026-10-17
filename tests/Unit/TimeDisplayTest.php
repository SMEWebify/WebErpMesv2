<?php

namespace Tests\Unit;

use App\Support\TimeDisplay;
use Tests\TestCase;

class TimeDisplayTest extends TestCase
{
    public function test_saisir_30_minutes_stocke_0_5_heure(): void
    {
        $this->assertSame(0.5, TimeDisplay::minutesToHours(30));
        $this->assertSame(0.008, TimeDisplay::minutesToHours('0.5'));
    }

    public function test_affichage_en_minutes_arrondi_a_une_decimale(): void
    {
        $this->assertSame('30 min', TimeDisplay::format(0.5, TimeDisplay::MINUTES));
        $this->assertSame('1.5 min', TimeDisplay::format('0.025', TimeDisplay::MINUTES));
        $this->assertSame('0 min', TimeDisplay::format(0, TimeDisplay::MINUTES));
    }

    public function test_le_defaut_est_heures_et_le_rendu_historique(): void
    {
        $this->assertSame(TimeDisplay::HOURS, TimeDisplay::unit());
        $this->assertFalse(TimeDisplay::isMinutes());
        $this->assertSame('0.500 h', TimeDisplay::format('0.500'));
    }

    public function test_le_reglage_societe_bascule_en_minutes_sans_toucher_au_stockage(): void
    {
        app(\App\Services\Settings\SettingsService::class)->set(TimeDisplay::SETTING_KEY, 'minutes');

        $this->assertTrue(TimeDisplay::isMinutes());
        $this->assertSame('30 min', TimeDisplay::format(0.5));

        app(\App\Services\Settings\SettingsService::class)->set(TimeDisplay::SETTING_KEY, 'weeks');
        $this->assertSame(TimeDisplay::HOURS, TimeDisplay::unit());
    }
}
