<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: MessageType::class)]
    private ?MessageType $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $command = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Message $replyTo = null;

    #[ORM\OneToMany(targetEntity: Reaction::class, mappedBy: 'message')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $reactions;

    public function __construct()
    {
        $this->reactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?MessageType
    {
        return $this->type;
    }

    public function setType(MessageType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(?string $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getReplyTo(): ?Message
    {
        return $this->replyTo;
    }

    public function setReplyTo(?Message $replyTo): static
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(Reaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setMessage($this);
        }

        return $this;
    }

    public function removeReaction(Reaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {

            if ($reaction->getMessage() === $this) {
                $reaction->setMessage(null);
            }
        }

        return $this;
    }

    public function getReactionsSummary(): array
    {

        $grouped = [];
        foreach ($this->reactions as $reaction) {
            $grouped[$reaction->getEmoji()][] = $reaction->getNickname();
        }

        $result = [];
        foreach ($grouped as $emoji => $users) {

            $result[] = ['emoji' => (string) $emoji, 'users' => $users];
        }

        return $result;
    }

    public function toArray(): array
    {
        $replyTo = null;
        if ($this->replyTo !== null) {
            $replyTo = [
                'id' => $this->replyTo->getId(),
                'nickname' => $this->replyTo->getNickname(),
                'text' => $this->replyTo->getText(),
            ];
        }

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'nickname' => $this->nickname,
            'text' => $this->text,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'command' => $this->command,
            'replyTo' => $replyTo,
            'reactions' => $this->getReactionsSummary(),
        ];
    }
}
