<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\MessageType;
use App\Entity\Reaction;
use App\Repository\MessageRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class MessageService
{
    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;
    private ReactionRepository $reactionRepository;
    private ManagerRegistry $doctrine;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository,
        ReactionRepository $reactionRepository,
        ManagerRegistry $doctrine
    ) {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->reactionRepository = $reactionRepository;
        $this->doctrine = $doctrine;
    }

    public function saveMessage(MessageType $type, ?string $nickname, string $text): Message
    {
        $message = new Message();
        $message->setType($type);
        $message->setNickname($nickname);
        $message->setText($text);
        $message->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function saveBotMessage(string $text, string $command, int $replyToId): Message
    {

        $replyTo = $this->entityManager->getReference(Message::class, $replyToId);

        $message = new Message();
        $message->setType(MessageType::BOT);
        $message->setText($text);
        $message->setCommand($command);
        $message->setReplyTo($replyTo);
        $message->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function getLastMessages(int $limit): array
    {
        $messages = $this->messageRepository->findBy([], ['id' => 'DESC'], $limit);

        return array_reverse($messages);
    }

    public function toggleReaction(int $messageId, string $nickname, string $emoji): ?Message
    {
        $message = $this->messageRepository->find($messageId);
        if ($message === null) {
            return null;
        }

        $existing = $this->reactionRepository->findOneBy([
            'message' => $message,
            'nickname' => $nickname,
            'emoji' => $emoji,
        ]);

        if ($existing !== null) {
            $message->removeReaction($existing);
            $this->entityManager->remove($existing);
        } else {
            $reaction = new Reaction();
            $reaction->setNickname($nickname);
            $reaction->setEmoji($emoji);
            $reaction->setCreatedAt(new \DateTimeImmutable());
            $message->addReaction($reaction);
            $this->entityManager->persist($reaction);
        }

        $this->entityManager->flush();

        return $message;
    }

    public function resetEntityManagerIfClosed(): void
    {
        if (!$this->entityManager->isOpen()) {
            $this->doctrine->resetManager();
        }
    }
}
