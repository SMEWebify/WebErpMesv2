<?php

namespace Database\Seeders;

use App\Models\User;

use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Seeder;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\CompaniesContacts;
use Database\Seeders\CreateAdminUserSeeder;
use App\Models\Companies\CompaniesAddresses;
use Database\Seeders\MethodsUnitTableSeeder;

use App\Models\Accounting\AccountingDelivery;
use Database\Seeders\MethodsSectionTableSeeder;
use Database\Seeders\MethodsServicesTableSeeder;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

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
        //Methodes informations 
        $this->call([
            MethodsServicesTableSeeder::class,
            MethodsUnitTableSeeder::class,
            MethodsSectionTableSeeder::class,
            CreateAdminUserSeeder::class,
        ]);

        //companies informations
        Companies::factory()->count(50)->create();
        CompaniesContacts::factory()->count(200)->create();
        CompaniesAddresses::factory()->count(200)->create();
        //Accounting informations 
        AccountingVat::factory()->count(4)->create();
        AccountingPaymentConditions::factory()->count(5)->create();
        AccountingPaymentMethod::factory()->count(3)->create();
        AccountingDelivery::factory()->count(3)->create();
        /*
        *Accounting allocation seeder
        */

        
        /*
        *Famillies  seeder
        *Section  seeder
        *Ressources  seeder
        *Location  seeder
        */

        //Products 
        /*          
        *Products  seeder
        */

        //Quotes
        Quotes::factory()->count(50)->create();
        QuoteLines::factory()->count(500)->create();
        //Orders 
        Orders::factory()->count(100)->create();
        OrderLines::factory()->count(500)->create();

        //Task
    }
}
