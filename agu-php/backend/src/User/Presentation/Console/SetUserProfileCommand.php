<?php

namespace App\User\Presentation\Console;

use App\User\Application\Service\ChangeUserProfileService;
use App\User\Domain\Exception\LastAdminException;
use App\User\Domain\Exception\ProfileNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Vincula um profile a um usuário pelo e-mail e pelo código do profile. É a forma de criar o primeiro admin (o
 * cadastro sempre cria executores):
 *
 *   php bin/console app:user:set-profile fulano@exemplo.com admin
 */
#[AsCommand(name: 'app:user:set-profile', description: 'Vincula um perfil (pelo código, ex.: admin ou executor) a um usuário (pelo e-mail)')]
final class SetUserProfileCommand extends Command
{
    public function __construct(private readonly ChangeUserProfileService $changeProfile)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail do usuário')
            ->addArgument('profile', InputArgument::REQUIRED, 'Código do perfil (ex.: admin, executor)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $user = $this->changeProfile->byEmail((string) $input->getArgument('email'), (string) $input->getArgument('profile'));
        } catch (UserNotFoundException) {
            $output->writeln('<error>Nenhum usuário com este e-mail.</error>');

            return Command::FAILURE;
        } catch (ProfileNotFoundException) {
            $output->writeln('<error>Nenhum perfil com este código.</error>');

            return Command::FAILURE;
        } catch (LastAdminException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('%s (%s) agora tem o perfil "%s".', $user->username, $user->email, $user->profileCode));

        return Command::SUCCESS;
    }
}
