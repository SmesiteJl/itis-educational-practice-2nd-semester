<?php

namespace App\Command;

use App\Chat\ChatServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:chat-server', description: 'Запускает websocket сервер чата')]
class ChatServerCommand extends Command
{
    private ChatServer $chatServer;

    public function __construct(ChatServer $chatServer)
    {
        parent::__construct();
        $this->chatServer = $chatServer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = 8081;

        $loop = Loop::get();

        $wsServer = new WsServer($this->chatServer);

        $wsServer->enableKeepAlive($loop, 30);

        $server = new IoServer(
            new HttpServer($wsServer),
            new SocketServer('0.0.0.0:' . $port, [], $loop),
            $loop
        );

        $output->writeln('Сервер чата запущен на порту ' . $port);
        $server->run();

        return Command::SUCCESS;
    }
}
