<?php

namespace App\Console\Commands;

use App\Actions\Access\ManageAccess;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GrantAdministrator extends Command
{
    protected $signature = 'checkmaster:grant-admin {email : Adresse du compte existant}';
    protected $description = 'Attribuer explicitement le groupe Administrateur plateforme à un compte actif.';

    public function handle(ManageAccess $access): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $user = User::where('email', $email)->first();
        if (! $user || ! $user->is_active) {
            $this->error('Aucun compte actif ne correspond à cette adresse.');

            return self::FAILURE;
        }
        $this->line($user->name.' <'.$user->email.'>');
        if (! $this->confirm('Accorder à ce compte les droits d’administrateur plateforme (comptes, accès et dossiers étudiants) ?', false)) {
            $this->info('Aucune modification.');

            return self::SUCCESS;
        }
        try {
            $access->grantAdministrator($user);
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Droits attribués. Le compte doit se reconnecter.');

        return self::SUCCESS;
    }
}
