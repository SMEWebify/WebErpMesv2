<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use App\Models\User;

class ImportLdapUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ldap:import-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import LDAP users into Laravel database';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve all LDAP users
        $ldapUsers = LdapUser::get();

        foreach ($ldapUsers as $ldapUser) {
            // Check if the user already exists in the database
            $user = User::where('email', $ldapUser->getEmail())->first();

            if (!$user) {
                // Create the user in Laravel if it does not exist
                User::create([
                    'name' => $ldapUser->getFirstName(),
                    'email' => $ldapUser->getEmail(),
                    'password' => bcrypt('password'), // Generate a temporary password
                ]);

                $this->info("User {$ldapUser->getFirstName()} imported successfully.");
            }
        }

        $this->info('Import completed.');
    }
}
