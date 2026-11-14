<?php

namespace App\Shared\Presentation\Console;

use App\Shared\Presentation\WebSocket\PushEndpoint;
use App\Shared\Presentation\WebSocket\RealtimeServer;
use Ratchet\Http\HttpServer;
use Ratchet\Http\OriginCheck;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer as ReactHttpServer;
use React\Socket\SocketServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sobe o servidor de tempo real: um processo que fica no ar (serviço `websocket` do compose) com duas portas.
 *
 *   --ws-port    WebSocket dos navegadores (o Nginx repassa /ws para cá); só aceita as origens de CORS_ALLOWED_ORIGINS
 *   --push-port  HTTP interno, pelo qual a aplicação entrega as mensagens (POST /push) e o healthcheck (/health)
 */
#[AsCommand(name: 'app:realtime:serve', description: 'Sobe o servidor de WebSocket (tempo real)')]
final class ServeRealtimeCommand extends Command
{
    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        private readonly RealtimeServer $realtime,
        private readonly LoopInterface $loop,
        private readonly array $allowedOrigins,
        private readonly string $pushToken,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('ws-port', null, InputOption::VALUE_REQUIRED, 'Porta do WebSocket', '8080')
            ->addOption('push-port', null, InputOption::VALUE_REQUIRED, 'Porta HTTP interna de entrega', '8081');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loop = $this->loop;

        // O navegador envia o cabeçalho Origin no WebSocket (o CORS não vale para ele): conferimos aqui.
        $hosts = array_values(array_filter(array_map(
            static fn (string $origin): ?string => parse_url($origin, \PHP_URL_HOST) ?: null,
            $this->allowedOrigins,
        )));
        $ws = new WsServer($this->realtime);
        $ws->enableKeepAlive($loop, 30);
        $app = new HttpServer(new OriginCheck($ws, $hosts));
        new IoServer($app, new SocketServer('0.0.0.0:'.$input->getOption('ws-port'), [], $loop), $loop);

        $push = new ReactHttpServer(new PushEndpoint($this->realtime, $this->pushToken));
        $push->listen(new SocketServer('0.0.0.0:'.$input->getOption('push-port'), [], $loop));

        $output->writeln(sprintf(
            'Tempo real no ar: WebSocket em :%s (origens: %s), entrega interna em :%s.',
            $input->getOption('ws-port'),
            implode(', ', $hosts),
            $input->getOption('push-port'),
        ));
        $loop->run();

        return Command::SUCCESS;
    }
}
