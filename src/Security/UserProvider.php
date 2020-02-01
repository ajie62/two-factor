<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername(string $login): ?User
    {
        return $this->loadUserFromDb($login);
    }

    public function loadUserFromDb(string $login): ?User
    {
        return $this->userRepository->findOneBy([
            "login" => $login,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): ?User
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @inheritDoc
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
