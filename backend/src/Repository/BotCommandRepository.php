<?php

namespace App\Repository;

use App\Entity\BotCommand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BotCommandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotCommand::class);
    }

    public function findEnabledByName(string $name): ?BotCommand
    {
        return $this->findOneBy(['name' => $name, 'enabled' => true]);
    }

    public function findAllSorted(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }

    public function findAllEnabled(): array
    {
        return $this->findBy(['enabled' => true], ['name' => 'ASC']);
    }
}
