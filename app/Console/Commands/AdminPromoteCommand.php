<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminPromoteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:promote 
                            {email : The email address of the user} 
                            {--demote : Demote user to regular user instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote or demote a user to/from administrator role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $demote = (bool) $this->option('demote');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found in the database.");
            $this->info("Make sure the user has registered/signed in at least once via Supabase Auth.");
            return self::FAILURE;
        }

        if ($demote) {
            // Safeguard: Check if this is the last admin
            $adminCount = User::where('role', 'admin')->count();
            if ($user->role === 'admin' && $adminCount <= 1) {
                $this->error("Cannot demote '{$email}': This is the last remaining administrator!");
                return self::FAILURE;
            }

            $user->role = 'user';
            $user->save();

            $this->info("Successfully demoted user '{$email}' to regular user.");
            return self::SUCCESS;
        }

        $user->role = 'admin';
        $user->save();

        $this->info("Successfully promoted user '{$email}' (ID: {$user->id}) to Administrator.");
        $this->info("The user can now access /admin upon logging in.");

        return self::SUCCESS;
    }
}
