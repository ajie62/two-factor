<?php

namespace App\Controller;

use App\Entity\User;
use App\EventSubscriber\TwoFactorAuthenticationSubscriber;
use App\Form\GoogleAuthenticatorType;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Doctrine\ORM\EntityManagerInterface;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class TwoFactorAuthenticationController extends AbstractController
{
    /**
     * @Route("/two-factor", name="two-factor")
     */
    public function twoFactor(
        Request $request,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(GoogleAuthenticatorType::class);

        $google2fa = new Google2FA();

        $svg = null;

        /** @var User $user */
        $user = $this->getUser();
        if (!$user->getGoogleAuthenticatorSecret()) {
            if ($session->get('2fa_secret')) {
                $secret = $session->get('2fa_secret');
            } else {
                $secret = $google2fa->generateSecretKey();
                $request->getSession()->set('2fa_secret', $secret);
            }

            $svg = $this->generateSvgForUser($google2fa, $user, $secret);
        } else {
            $secret = $user->getGoogleAuthenticatorSecret();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->getData()["code"];
            $codeIsValid = $google2fa->verifyKey($secret, $code, 4);
            if ($codeIsValid) {
                if (!$user->getGoogleAuthenticatorSecret()) {
                    $user->setGoogleAuthenticatorSecret($secret);
                    $entityManager->persist($user);
                    $entityManager->flush();
                }

                $this->addRoleTwoFA($tokenStorage, $session);

                return $this->redirectToRoute("app_dashboard");
            }
            $this->addFlash("error", "Invalid verification code");
        }

        return $this->render("security/two-factor.html.twig", [
            "svg" => $svg,
            "form" => $form->createView(),
        ]);
    }

    private function generateSvgForUser(Google2FA $google2FA, User $user, string $secret): string
    {
        $g2faUrl = $google2FA->getQRCodeUrl(
            "My website",
            $user->getLogin(),
            $secret
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new SvgImageBackEnd() // can also user new ImagickImageBackEnd() in order to generate png
            )
        );

        return $writer->writeString($g2faUrl);
    }

    private function addRoleTwoFA(TokenStorageInterface $tokenStorage, SessionInterface $session)
    {
        /** @var PostAuthenticationGuardToken $currentToken */
        $currentToken = $tokenStorage->getToken();
        $roles = array_merge($currentToken->getRoleNames(), [TwoFactorAuthenticationSubscriber::ROLE_2FA_SUCCEED]);

        /** @var User $user */
        $user = $currentToken->getUser();

        $newToken = new PostAuthenticationGuardToken($user, $currentToken->getProviderKey(), $roles);
        $tokenStorage->setToken($newToken);
        $session->set('_security.' . $currentToken->getProviderKey(), \serialize($newToken));
    }
}