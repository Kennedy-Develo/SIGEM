<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria o primeiro usuário administrador do SIGEM';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Criação do administrador do SIGEM');

        $name = trim((string) $this->ask('Nome completo'));
        $email = mb_strtolower(trim((string) $this->ask('E-mail')));
        $password = (string) $this->secret('Senha');
        $passwordConfirmation = (string) $this->secret('Confirme a senha');

        if ($password !== $passwordConfirmation) {
            $this->components->error('As senhas informadas não são iguais.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => [
                    'required',
                    'string',
                    Password::min(12)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],
            ],
            [
                'name.required' => 'O nome é obrigatório.',
                'email.required' => 'O e-mail é obrigatório.',
                'email.email' => 'Informe um endereço de e-mail válido.',
                'email.unique' => 'Já existe um usuário com este e-mail.',
                'password.required' => 'A senha é obrigatória.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $administrator = new User;

        $administrator->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Administrator,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ])->save();

        $this->components->success(
            "Administrador {$administrator->name} criado com sucesso.",
        );

        return self::SUCCESS;
    }
}
