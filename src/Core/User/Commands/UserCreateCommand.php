<?php

declare(strict_types=1);

namespace App\Core\User\Commands;

use App\Core\Role\RoleRepository;
use App\Core\User\UserEntity;
use App\Core\User\UserRepository;
use App\Shared\Params\AuthParams;
use App\Shared\Services\PasswordHasher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use function base64_encode;
use function date;
use function filter_var;
use function random_bytes;
use function rtrim;
use function strtr;

#[AsCommand(
    name: 'user:create',
    description: 'Crea un utente attivo (default: ruolo ADMIN), pensato per il primo bootstrap',
)]
final class UserCreateCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly AuthParams $authParams,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email di login')
            ->addArgument('name', InputArgument::REQUIRED, 'Nome visualizzato')
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'Password; se omessa ne viene generata una robusta, stampata una sola volta',
            )
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Codice del ruolo da assegnare', 'ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $name = (string) $input->getArgument('name');
        $roleCode = (string) $input->getOption('role');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $output->writeln("<error>Email non valida: $email</error>");

            return ExitCode::DATAERR;
        }

        if ($this->userRepository->emailExists($email)) {
            $output->writeln("<error>Esiste già un utente con email $email.</error>");

            return ExitCode::DATAERR;
        }

        $roleId = $this->roleRepository->findIdByCode($roleCode);

        if ($roleId === null) {
            $output->writeln(
                "<error>Ruolo '$roleCode' inesistente: eseguire prima le migration (./yii migrate:up).</error>",
            );

            return ExitCode::DATAERR;
        }

        /** @var string|null $password */
        $password = $input->getOption('password');
        $generated = false;

        if ($password === null) {
            $password = $this->generatePassword();
            $generated = true;
        }

        $now = date('Y-m-d H:i:s');
        $user = new UserEntity(
            email: $email,
            passwordHash: $this->passwordHasher->hash($password),
            name: $name,
            status: UserEntity::STATUS_ACTIVE,
            passwordChangedAt: $now,
            passwordExpiresAt: $this->authParams->passwordExpiresAt($now),
        );

        $id = $this->userRepository->createWithRoles($user, [$roleId]);

        $output->writeln("<info>Utente #$id creato: $email (ruolo $roleCode).</info>");

        if ($generated) {
            $output->writeln("Password generata (mostrata solo ora): <comment>$password</comment>");
        }

        return ExitCode::OK;
    }

    private function generatePassword(): string
    {
        // 18 byte casuali → 24 caratteri base64url, senza simboli ambigui.
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }
}
