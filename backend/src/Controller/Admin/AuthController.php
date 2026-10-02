<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{

    private const TOKEN_LIFETIME = '+12 hours';

    #[Route('/api/admin/login', name: 'admin_login', methods: ['POST'])]
    public function login(
        Request $request,
        AdminRepository $adminRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $login = trim($data['login'] ?? '');
        $password = $data['password'] ?? '';

        $admin = $adminRepository->findByLogin($login);

        if ($admin === null || !$passwordHasher->isPasswordValid($admin, $password)) {
            return $this->json(['error' => 'Неверный логин или пароль'], 401);
        }

        $token = bin2hex(random_bytes(32));
        $admin->setApiToken($token);
        $admin->setTokenExpiresAt(new \DateTimeImmutable(self::TOKEN_LIFETIME));
        $entityManager->flush();

        return $this->json([
            'token' => $token,
            'login' => $admin->getLogin(),
        ]);
    }

    #[Route('/api/admin/me', name: 'admin_me', methods: ['GET'])]
    public function me(): JsonResponse
    {

        $admin = $this->getUser();

        return $this->json(['login' => $admin->getLogin()]);
    }

    #[Route('/api/admin/logout', name: 'admin_logout', methods: ['POST'])]
    public function logout(EntityManagerInterface $entityManager): JsonResponse
    {

        $admin = $this->getUser();
        $admin->setApiToken(null);
        $admin->setTokenExpiresAt(null);
        $entityManager->flush();

        return $this->json(['ok' => true]);
    }
}
