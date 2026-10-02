<?php

namespace App\Security;

use App\Repository\AdminRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AdminTokenHandler implements AccessTokenHandlerInterface
{
    private AdminRepository $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $admin = $this->adminRepository->findByApiToken($accessToken);

        if ($admin === null) {
            throw new BadCredentialsException('Неверный токен');
        }

        if ($admin->getTokenExpiresAt() === null || $admin->getTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new BadCredentialsException('Токен истёк, войдите заново');
        }

        return new UserBadge($admin->getUserIdentifier());
    }
}
