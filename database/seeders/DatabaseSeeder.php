<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Companies\CompaniesContacts;
use Illuminate\Database\Seeder;
use App\Models\Companies\Companies;

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

        //first user
        User::factory()->count(50)->create();
        //companies informations
        Companies::factory()->count(50)->create();
        CompaniesContacts::factory()->count(200)->create();
        //Accounting informations 
        AccountingVatFactory::factory()->count(4)->create();
        /*
        *Payment condition
        *Payment choice
        *Accouting allocation
        *Delevery mode
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
