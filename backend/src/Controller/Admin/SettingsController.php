<?php

namespace App\Controller\Admin;

use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    #[Route('/api/admin/settings', name: 'admin_settings_get', methods: ['GET'])]
    public function get(SettingsService $settingsService): JsonResponse
    {
        return $this->json([
            'systemPrompt' => $settingsService->getSystemPrompt(),
        ]);
    }

    #[Route('/api/admin/settings', name: 'admin_settings_save', methods: ['PUT'])]
    public function save(Request $request, SettingsService $settingsService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $systemPrompt = trim((string) ($data['systemPrompt'] ?? ''));

        if (mb_strlen($systemPrompt) > 4000) {
            return $this->json(['errors' => ['systemPrompt' => 'Системный промпт длиннее 4000 символов']], 400);
        }

        $settingsService->setSystemPrompt($systemPrompt);

        return $this->json(['systemPrompt' => $systemPrompt]);
    }
}
