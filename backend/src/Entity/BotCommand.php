<?php

namespace App\Entity;

use App\Repository\BotCommandRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BotCommandRepository::class)]

#[UniqueEntity(fields: ['name'], message: 'Команда с таким именем уже есть')]
class BotCommand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Укажите имя команды')]
    #[Assert\Length(max: 50, maxMessage: 'Имя команды длиннее {{ limit }} символов')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}_-]+$/u',
        message: 'В имени команды можно только буквы, цифры, _ и - (без ! и пробелов)'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Укажите описание')]
    #[Assert\Length(max: 255, maxMessage: 'Описание длиннее {{ limit }} символов')]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Промпт не может быть пустым')]
    #[Assert\Length(max: 4000, maxMessage: 'Промпт длиннее {{ limit }} символов')]
    private ?string $prompt = null;

    #[ORM\Column]
    private bool $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'prompt' => $this->prompt,
            'enabled' => $this->enabled,
        ];
    }
}
