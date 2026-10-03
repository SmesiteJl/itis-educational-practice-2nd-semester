<?php

namespace App\Controller\Admin;

use App\Bot\PromptBuilder;
use App\Entity\BotCommand;
use App\Repository\BotCommandRepository;
use App\Service\LlmClient;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/commands')]
class CommandController extends AbstractController
{

    private const RESERVED_NAMES = ['помощь', 'help'];

    private BotCommandRepository $botCommandRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(
        BotCommandRepository $botCommandRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->botCommandRepository = $botCommandRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('', name: 'admin_commands_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $result = [];
        foreach ($this->botCommandRepository->findAllSorted() as $command) {
            $result[] = $command->toArray();
        }

        return $this->json($result);
    }

    #[Route('', name: 'admin_commands_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $command = new BotCommand();
        $this->fillFromRequest($command, $request);

        $errors = $this->validateCommand($command);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }

        $this->entityManager->persist($command);
        $this->entityManager->flush();

        return $this->json($command->toArray(), 201);
    }

    #[Route('/test', name: 'admin_commands_test', methods: ['POST'])]
    public function test(
        Request $request,
        PromptBuilder $promptBuilder,
        SettingsService $settingsService,
        LlmClient $llmClient
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $template = trim((string) ($data['prompt'] ?? ''));
        $text = trim((string) ($data['text'] ?? ''));

        if ($template === '') {
            return $this->json(['error' => 'Промпт пустой'], 400);
        }

        $prompt = $promptBuilder->build($template, $text);

        try {
            $answer = $llmClient->ask($prompt, $settingsService->getSystemPrompt());
        } catch (\Throwable $e) {
            return $this->json(['error' => 'LLM не ответила: ' . $e->getMessage(), 'prompt' => $prompt], 502);
        }

        return $this->json([
            'prompt' => $prompt,
            'answer' => $answer,
        ]);
    }

    #[Route('/{id}', name: 'admin_commands_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $command = $this->botCommandRepository->find($id);
        if ($command === null) {
            return $this->json(['error' => 'Команда не найдена'], 404);
        }

        $this->fillFromRequest($command, $request);

        $errors = $this->validateCommand($command);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }

        $this->entityManager->flush();

        return $this->json($command->toArray());
    }

    #[Route('/{id}', name: 'admin_commands_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $command = $this->botCommandRepository->find($id);
        if ($command === null) {
            return $this->json(['error' => 'Команда не найдена'], 404);
        }

        $this->entityManager->remove($command);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    private function fillFromRequest(BotCommand $command, Request $request): void
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }

        $name = mb_strtolower(trim((string) ($data['name'] ?? '')));
        $name = ltrim($name, '!');

        $command->setName($name);
        $command->setDescription(trim((string) ($data['description'] ?? '')));
        $command->setPrompt(trim((string) ($data['prompt'] ?? '')));
        $command->setEnabled((bool) ($data['enabled'] ?? true));
    }

    private function validateCommand(BotCommand $command): array
    {
        $errors = [];

        foreach ($this->validator->validate($command) as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        if (in_array($command->getName(), self::RESERVED_NAMES, true)) {
            $errors['name'] = 'Имя !' . $command->getName() . ' занято встроенной командой';
        }

        return $errors;
    }
}
