<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email    = trim((string) $request->request->get('_username', ''));
        $password = (string) $request->request->get('_password', '');

        if ($email === '' || $password === '') {
            throw new CustomUserMessageAuthenticationException('Email et mot de passe requis.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('Email invalide.');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user instanceof User) {
            if ($user->isBanned()) {
                $request->getSession()->set('reactivation_email', $email);
                throw new CustomUserMessageAuthenticationException('Votre compte est banni. Contactez l\'administrateur.');
            }

            if ($user->isSuspended()) {
                throw new CustomUserMessageAuthenticationException('Votre compte est suspendu.');
            }
        }

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $this->removeTargetPath($request->getSession(), $firewallName);
        $request->getSession()->remove('reactivation_email');

        $user  = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        if (in_array('ROLE_HOST', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('host_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
