<?php

namespace App\Controller;

use App\Repository\BotCommandRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CommandListController extends AbstractController
{
    #[Route('/api/commands', name: 'command_list', methods: ['GET'])]
    public function list(BotCommandRepository $botCommandRepository): JsonResponse
    {
        $result = [];
        foreach ($botCommandRepository->findAllEnabled() as $command) {
            $result[] = [
                'name' => $command->getName(),
                'description' => $command->getDescription(),
            ];
        }

        return $this->json($result);
    }
}
