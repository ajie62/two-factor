<?php

namespace App\EventSubscriber;

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class TwoFactorAuthenticationSubscriber implements EventSubscriberInterface
{
    const ROLE_2FA_SUCCEED = "2FA_SUCCEED";
    const FIREWALL_NAME = "main";
    const ROUTE_FOR_2FA = "two-factor";

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }


    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (in_array($event->getRequest()->attributes->get('_route'), ["app_login", self::ROUTE_FOR_2FA])) {
            return;
        }


        if (($currentToken = $this->tokenStorage->getToken()) && $currentToken instanceof PostAuthenticationGuardToken) {
            if ($currentToken->getProviderKey() === self::FIREWALL_NAME) {
                if (!$this->hasRole($currentToken, self::ROLE_2FA_SUCCEED)) {
                    $response = new RedirectResponse($this->router->generate(self::ROUTE_FOR_2FA));
                    $event->setResponse($response);
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }

    private function hasRole(TokenInterface $token, string $role): bool
    {
        foreach ($token->getRoleNames() as $userRole) {
            if ($userRole === $role) {
                return true;
            }
        }
        return false;
    }
}
