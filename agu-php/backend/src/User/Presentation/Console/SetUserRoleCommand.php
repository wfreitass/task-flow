<?php

namespace App\User\Presentation\Console;

use App\User\Application\Service\ChangeUserRoleService;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Exception\LastAdminException;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Define o perfil de um usuário pelo e-mail. É a forma de criar o primeiro admin (o cadastro sempre cria
 * executores):
 *
 *   php bin/console app:user:set-role fulano@exemplo.com admin
 */
#[AsCommand(name: 'app:user:set-role', description: 'Define o perfil (admin ou executor) de um usuário pelo e-mail')]
final class SetUserRoleCommand extends Command
{
    public function __construct(private readonly ChangeUserRoleService $changeRole)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail do usuário')
            ->addArgument('role', InputArgument::REQUIRED, 'Perfil: '.implode(' ou ', UserRole::values()));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = UserRole::tryFrom((string) $input->getArgument('role'));
        if (null === $role) {
            $output->writeln('<error>Perfil inválido. Use: '.implode(' ou ', UserRole::values()).'.</error>');

            return Command::INVALID;
        }

        try {
            $user = $this->changeRole->byEmail((string) $input->getArgument('email'), $role);
        } catch (UserNotFoundException) {
            $output->writeln('<error>Nenhum usuário com este e-mail.</error>');

            return Command::FAILURE;
        } catch (LastAdminException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('%s (%s) agora é %s.', $user->username, $user->email, $user->role));

        return Command::SUCCESS;
    }
}
