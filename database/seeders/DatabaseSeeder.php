<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Database\Factories\Accounting\AccountingVatFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /**Some table depends on other table. Follow the attached order to seed data
         * 
         */

        //user
        User::factory()->count(50)->create();
        //companies informations
        Companies::factory()->count(500)->create();
        CompaniesContacts::factory()->count(2000)->create();
        CompaniesAddresses::factory()->count(2000)->create();
        //Accounting informations 
        AccountingVatFactory::factory()->count(4)->create();
        AccountingPaymentConditions::factory()->count(5)->create();
        AccountingPaymentMethod::factory()->count(3)->create();
        AccountingDelivery::factory()->count(3)->create();
        /*
        *Accouting allocation
        */

        //Methodes informations 
        /*
        *Services
        *Famillies
        *Section
        *Ressources
        *Location
        *Units
        */

        //Products 

        //Quotes
        
        //Orders 
    }
}
