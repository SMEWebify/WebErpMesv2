<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ChatSeeder;
use Database\Seeders\LeadsSeeder;
use Database\Seeders\StocksSeeder;
use Database\Seeders\AllocationSeeder;
use Database\Seeders\CreateTaskSeeder;
use Database\Seeders\OrderTableSeeder;
use Database\Seeders\QuotesTableSeeder;
use Database\Seeders\OpportunitiesSeeder;
use Database\Seeders\StockLocationSeeder;
use Database\Seeders\CompaniesTableSeeder;
use Database\Seeders\CreateAdminUserSeeder;
use Database\Seeders\MethodsFamiliesSeeder;
use Database\Seeders\OrderLinesTableSeeder;
use Database\Seeders\PermissionTableSeeder;
use Database\Seeders\AssetRolePermissionSeeder;
use Database\Seeders\QuoteLinesTableSeeder;
use Database\Seeders\EstimatedBudgetsSeeder;
use Database\Seeders\MethodsUnitTableSeeder;
use App\Models\Accounting\AccountingDelivery;
use Database\Seeders\MethodsRessourcesSeeder;
use Database\Seeders\AccountingVatTableSeeder;
use Database\Seeders\MethodsSectionTableSeeder;
use Database\Seeders\MethodsServicesTableSeeder;
use App\Models\Accounting\AccountingPaymentMethod;
use Database\Seeders\CompaniesContactsTableSeeder;
use Database\Seeders\CompaniesAddressesTableSeeder;
use Database\Seeders\OpportunitiesEventsLogsSeeder;
use App\Models\Accounting\AccountingPaymentConditions;
use Database\Seeders\OpportunitiesActivitiesLogsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Some table depends on other table. Follow the attached order to seed data
         */

        //Methodes informations 
        $this->call([
            MethodsServicesTableSeeder::class,
            MethodsUnitTableSeeder::class,
            MethodsSectionTableSeeder::class,
            MethodsRessourcesSeeder::class,
            MethodsFamiliesSeeder::class,
            PermissionTableSeeder::class,
            AssetRolePermissionSeeder::class,
            CreateAdminUserSeeder::class,
            AllocationSeeder::class,
        ]);

        //factory
        $this->call(EstimatedBudgetsSeeder::class);

        //companies informations
        
        $this->call(CompaniesTableSeeder::class);
        $this->call(CompaniesContactsTableSeeder::class);
        $this->call(CompaniesAddressesTableSeeder::class);

        //Accounting informations 
        /* 
        * not lunch this seeder if you already use AllocationSeeder
        * $this->call(AccountingVatTableSeeder::class);
        */

        AccountingPaymentConditions::factory()->count(5)->create();
        AccountingPaymentMethod::factory()->count(3)->create();
        AccountingDelivery::factory()->count(3)->create();
        
        /*
        *Location  seeder
        */

        //Products 
        $this->call(CreateProductsSeeder::class);
        $this->call(StocksSeeder::class);
        $this->call(StockLocationSeeder::class);
        //$this->call(StockLocationProductsSeeder::class);
        //Leads
        //$this->call(LeadsSeeder::class);
        //Oppotunities 
        //$this->call(OpportunitiesSeeder::class);
        $this->call(OpportunitiesActivitiesLogsSeeder::class);
        $this->call(OpportunitiesEventsLogsSeeder::class);
        //Quotes
        $this->call(QuotesTableSeeder::class);
        $this->call(QuoteLinesTableSeeder::class);
        //Orders 
        $this->call(OrderTableSeeder::class);
        $this->call(OrderLinesTableSeeder::class);
        //Task
        //$this->call(CreateTaskSeeder::class);
        //Guest chat
        //$this->call(ChatSeeder::class);
    }
}
