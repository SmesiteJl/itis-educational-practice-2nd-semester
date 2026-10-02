<?php

namespace App\Command;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Создаёт администратора для админки')]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private AdminRepository $adminRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdminRepository $adminRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->adminRepository = $adminRepository;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('login', InputArgument::REQUIRED, 'Логин')
            ->addArgument('password', InputArgument::REQUIRED, 'Пароль');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = trim($input->getArgument('login'));
        $password = $input->getArgument('password');

        if ($login === '' || mb_strlen($password) < 4) {
            $output->writeln('<error>Логин не может быть пустым, пароль - минимум 4 символа</error>');
            return Command::FAILURE;
        }

        if ($this->adminRepository->findByLogin($login) !== null) {
            $output->writeln('Администратор ' . $login . ' уже есть');
            return Command::SUCCESS;
        }

        $admin = new Admin();
        $admin->setLogin($login);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln('<info>Администратор ' . $login . ' создан</info>');

        return Command::SUCCESS;
    }
}
