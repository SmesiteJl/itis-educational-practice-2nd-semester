<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class SettingsService
{
    private SettingRepository $settingRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(SettingRepository $settingRepository, EntityManagerInterface $entityManager)
    {
        $this->settingRepository = $settingRepository;
        $this->entityManager = $entityManager;
    }

    public function getSystemPrompt(): string
    {
        $setting = $this->settingRepository->find(Setting::SYSTEM_PROMPT);
        if ($setting === null) {
            return '';
        }

        return $setting->getValue();
    }

    public function setSystemPrompt(string $systemPrompt): void
    {
        $setting = $this->settingRepository->find(Setting::SYSTEM_PROMPT);
        if ($setting === null) {
            $setting = new Setting();
            $setting->setName(Setting::SYSTEM_PROMPT);
            $this->entityManager->persist($setting);
        }

        $setting->setValue($systemPrompt);
        $this->entityManager->flush();
    }
}
