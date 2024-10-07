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
            // Access the email and name attributes from the LDAP entry
            $email = $ldapUser->getFirstAttribute('mail'); // 'mail' est l'attribut LDAP pour l'email
            $firstName = $ldapUser->getFirstAttribute('givenName'); // 'givenName' est souvent l'attribut pour le prénom
    
            // Continue to the next user if email or name is missing
            if (empty($email) || empty($firstName)) {
                $this->warn("Skipped user due to missing email or name.");
                continue;
            }
    
            // Check if the user already exists in the database
            $user = User::where('email', $email)->first();
    
            if (!$user) {
                // Create the user in Laravel if it does not exist
                User::create([
                    'name' => $firstName,
                    'email' => $email,
                    'password' => bcrypt('password'), // Generate a temporary password
                ]);
    
                $this->info("User {$firstName} imported successfully.");
            }
        }
    
        $this->info('Import completed.');
    }
}
