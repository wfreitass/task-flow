<?php

namespace App\Task\Presentation\Console;

use App\Task\Application\Service\ReindexTasksService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cria e/ou refaz o índice de busca de tarefas a partir do banco.
 *
 *   app:search:reindex               indexa todas as tarefas (cria o índice se não existir; reaproveita se existir)
 *   app:search:reindex --recreate    apaga o índice e o refaz do zero (use depois de mudar o mapeamento)
 *   app:search:reindex --if-missing  só age se o índice não existir (é o que o worker faz ao subir)
 */
#[AsCommand(name: 'app:search:reindex', description: 'Cria e popula o índice de busca de tarefas (OpenSearch)')]
final class ReindexTasksCommand extends Command
{
    public function __construct(private readonly ReindexTasksService $reindex)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('recreate', null, InputOption::VALUE_NONE, 'Apaga o índice antes de refazê-lo')
            ->addOption('if-missing', null, InputOption::VALUE_NONE, 'Só faz algo se o índice ainda não existir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $count = $this->reindex->execute((bool) $input->getOption('recreate'), (bool) $input->getOption('if-missing'));
        } catch (\RuntimeException $e) {
            $output->writeln('<error>Não foi possível indexar: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln(null === $count ? 'O índice já existe; nada a fazer.' : sprintf('Índice pronto: %d tarefa(s) indexada(s).', $count));

        return Command::SUCCESS;
    }
}
