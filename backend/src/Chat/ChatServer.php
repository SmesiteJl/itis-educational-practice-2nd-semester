<?php

namespace App\Chat;

use App\Bot\CommandParser;
use App\Bot\PromptBuilder;
use App\Entity\Message;
use App\Entity\MessageType;
use App\Repository\BotCommandRepository;
use App\Service\AsyncLlmClient;
use App\Service\MessageService;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class ChatServer implements MessageComponentInterface
{

    protected \SplObjectStorage $clients;

    private array $nicknames = [];

    private array $waitingForBot = [];

    private const HISTORY_SIZE = 50;

    private const HELP_COMMANDS = ['помощь', 'help'];

    private const ALLOWED_REACTIONS = ['👍', '👎', '❤️', '😂', '😮', '😢', '🔥', '🎉'];

    private MessageService $messageService;
    private CommandParser $commandParser;
    private BotCommandRepository $botCommandRepository;
    private AsyncLlmClient $llmClient;
    private PromptBuilder $promptBuilder;
    private SettingsService $settingsService;
    private EntityManagerInterface $entityManager;
    private NicknameValidator $nicknameValidator;

    public function __construct(
        MessageService $messageService,
        CommandParser $commandParser,
        BotCommandRepository $botCommandRepository,
        AsyncLlmClient $llmClient,
        PromptBuilder $promptBuilder,
        SettingsService $settingsService,
        EntityManagerInterface $entityManager,
        NicknameValidator $nicknameValidator
    ) {
        $this->clients = new \SplObjectStorage();
        $this->messageService = $messageService;
        $this->commandParser = $commandParser;
        $this->botCommandRepository = $botCommandRepository;
        $this->llmClient = $llmClient;
        $this->promptBuilder = $promptBuilder;
        $this->settingsService = $settingsService;
        $this->entityManager = $entityManager;
        $this->nicknameValidator = $nicknameValidator;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "Новое подключение ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (!is_array($data) || !isset($data['type'])) {
            $this->sendError($from, 'Неправильный формат сообщения');
            return;
        }

        $this->entityManager->clear();

        try {
            switch ($data['type']) {
                case 'join':
                    $this->handleJoin($from, $data);
                    break;
                case 'message':
                    $this->handleMessage($from, $data);
                    break;
                case 'reaction':
                    $this->handleReaction($from, $data);
                    break;
                default:
                    $this->sendError($from, 'Неизвестный тип: ' . $data['type']);
            }
        } catch (\Throwable $e) {

            echo "Ошибка при обработке {$data['type']}: {$e->getMessage()}\n";
            $this->messageService->resetEntityManagerIfClosed();
            $this->sendError($from, 'Ошибка на сервере, попробуйте ещё раз');
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        if (isset($this->nicknames[$conn->resourceId])) {
            $nickname = $this->nicknames[$conn->resourceId];
            unset($this->nicknames[$conn->resourceId]);

            try {
                $this->sendMessageToAll(MessageType::SYSTEM, null, $nickname . ' вышел из чата');
            } catch (\Throwable $e) {
                echo "Не удалось сохранить сообщение о выходе: {$e->getMessage()}\n";
                $this->messageService->resetEntityManagerIfClosed();
            }
            $this->broadcastUsers();
        }

        echo "Отключился {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Ошибка: {$e->getMessage()}\n";

        $conn->close();
    }

    private function handleJoin(ConnectionInterface $conn, array $data): void
    {
        if (isset($this->nicknames[$conn->resourceId])) {
            $this->send($conn, ['type' => 'join_error', 'error' => 'Вы уже вошли в чат']);
            return;
        }

        $nickname = trim($data['nickname'] ?? '');

        $error = $this->nicknameValidator->validate($nickname);
        if ($error !== null) {
            $this->send($conn, ['type' => 'join_error', 'error' => $error]);
            return;
        }

        if ($this->isNicknameTaken($nickname)) {
            $this->send($conn, ['type' => 'join_error', 'error' => 'Ник ' . $nickname . ' уже занят, введите другой']);
            return;
        }

        $this->nicknames[$conn->resourceId] = $nickname;

        $history = [];
        foreach ($this->messageService->getLastMessages(self::HISTORY_SIZE) as $message) {
            $history[] = $message->toArray();
        }

        $this->send($conn, [
            'type' => 'join_ok',
            'nickname' => $nickname,
            'users' => $this->getOnlineUsers(),
            'history' => $history,
        ]);

        $this->sendMessageToAll(MessageType::SYSTEM, null, $nickname . ' вошёл в чат');
        $this->broadcastUsers();
    }

    private function handleMessage(ConnectionInterface $conn, array $data): void
    {
        if (!isset($this->nicknames[$conn->resourceId])) {
            $this->sendError($conn, 'Сначала нужно войти в чат');
            return;
        }

        $text = trim($data['text'] ?? '');
        if ($text === '') {
            return;
        }

        $nickname = $this->nicknames[$conn->resourceId];

        $parsed = $this->commandParser->parse($text);
        if ($parsed !== null && in_array($parsed->getName(), self::HELP_COMMANDS, true)) {
            $this->sendBotPrivate($conn, $this->buildHelpText());
            return;
        }

        $message = $this->sendMessageToAll(MessageType::USER, $nickname, $text);

        if ($parsed !== null) {
            $this->handleBotCommand($conn, $message);
        }
    }

    private function buildHelpText(): string
    {
        $lines = ['Команды бота:'];
        foreach ($this->botCommandRepository->findAllEnabled() as $command) {
            $lines[] = '!' . $command->getName() . ' <текст> - ' . $command->getDescription();
        }
        $lines[] = '!помощь - этот список';

        return implode("\n", $lines);
    }

    private function handleBotCommand(ConnectionInterface $conn, Message $message): void
    {
        $parsed = $this->commandParser->parse($message->getText());
        if ($parsed === null) {
            return;
        }

        $command = $this->botCommandRepository->findEnabledByName($parsed->getName());
        if ($command === null) {
            $this->sendBotPrivate($conn, 'Команды !' . $parsed->getName() . ' нет. Список команд: !помощь');
            return;
        }

        if ($this->promptBuilder->needsText($command->getPrompt()) && $parsed->getText() === '') {
            $this->sendBotPrivate($conn, 'Напишите текст после команды, например: !' . $command->getName() . ' привет');
            return;
        }

        $waitingKey = mb_strtolower($message->getNickname());
        if (isset($this->waitingForBot[$waitingKey])) {
            $this->sendBotPrivate($conn, 'Подождите, я ещё отвечаю на вашу прошлую команду');
            return;
        }
        $this->waitingForBot[$waitingKey] = true;

        $prompt = $this->promptBuilder->build($command->getPrompt(), $parsed->getText());
        $systemPrompt = $this->settingsService->getSystemPrompt();

        echo "Запрос к LLM: $prompt\n";

        $commandName = $command->getName();
        $messageId = $message->getId();
        $nickname = $message->getNickname();

        $this->broadcast([
            'type' => 'bot_thinking',
            'id' => $messageId,
            'nickname' => $nickname,
            'command' => $commandName,
        ]);

        $this->llmClient->ask($prompt, $systemPrompt)
            ->then(function (string $answer) use ($commandName, $messageId) {
                $botMessage = $this->messageService->saveBotMessage($answer, $commandName, $messageId);
                $this->broadcast([
                    'type' => 'message',
                    'message' => $botMessage->toArray(),
                ]);
            })
            ->catch(function (\Throwable $e) use ($commandName, $nickname) {
                echo "Ошибка LLM: {$e->getMessage()}\n";
                $this->messageService->resetEntityManagerIfClosed();

                $reason = $this->llmClient->explainError($e);
                $this->sendMessageToAll(
                    MessageType::SYSTEM,
                    null,
                    'Бот не ответил на !' . $commandName . ' от ' . $nickname . ': ' . $reason
                );
            })
            ->finally(function () use ($messageId, $waitingKey) {
                unset($this->waitingForBot[$waitingKey]);
                $this->broadcast(['type' => 'bot_done', 'id' => $messageId]);
            });
    }

    private function handleReaction(ConnectionInterface $conn, array $data): void
    {
        if (!isset($this->nicknames[$conn->resourceId])) {
            $this->sendError($conn, 'Сначала нужно войти в чат');
            return;
        }

        $messageId = (int) ($data['messageId'] ?? 0);
        $emoji = $data['emoji'] ?? '';

        if (!in_array($emoji, self::ALLOWED_REACTIONS, true)) {
            $this->sendError($conn, 'Такую реакцию поставить нельзя');
            return;
        }

        $message = $this->messageService->toggleReaction($messageId, $this->nicknames[$conn->resourceId], $emoji);
        if ($message === null) {
            $this->sendError($conn, 'Сообщение не найдено');
            return;
        }

        $this->broadcast([
            'type' => 'reactions',
            'messageId' => $message->getId(),
            'reactions' => $message->getReactionsSummary(),
        ]);
    }

    private function sendMessageToAll(MessageType $type, ?string $nickname, string $text): Message
    {
        $message = $this->messageService->saveMessage($type, $nickname, $text);

        $this->broadcast([
            'type' => 'message',
            'message' => $message->toArray(),
        ]);

        return $message;
    }

    private function isNicknameTaken(string $nickname): bool
    {
        foreach ($this->nicknames as $existing) {
            if ($this->nicknameValidator->isSame($existing, $nickname)) {
                return true;
            }
        }

        return false;
    }

    private function getOnlineUsers(): array
    {
        $users = array_values($this->nicknames);
        sort($users);

        return $users;
    }

    private function broadcastUsers(): void
    {
        $this->broadcast(['type' => 'users', 'users' => $this->getOnlineUsers()]);
    }

    private function broadcast(array $data): void
    {
        foreach ($this->clients as $client) {
            if (isset($this->nicknames[$client->resourceId])) {
                $this->send($client, $data);
            }
        }
    }

    private function send(ConnectionInterface $conn, array $data): void
    {

        $conn->send(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function sendBotPrivate(ConnectionInterface $conn, string $text): void
    {
        $this->send($conn, ['type' => 'bot_private', 'text' => $text]);
    }

    private function sendError(ConnectionInterface $conn, string $error): void
    {
        $this->send($conn, ['type' => 'error', 'error' => $error]);
    }
}
