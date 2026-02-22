<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FaceVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $session = $this->requestStack->getSession();
        $reactivationEmail = $session?->get('reactivation_email');

        return $this->render('front/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'reactivation_email' => $reactivationEmail,
        ]);
    }

    #[Route('/account/request-reactivation', name: 'app_request_reactivation', methods: ['POST'])]
    public function requestReactivation(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('request-reactivation', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_login');
        }

        $email = trim((string) $request->request->get('email', ''));
        $note = trim((string) $request->request->get('note', ''));
        $sent = false;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user instanceof User && ($user->isDeactivatedByUser() || $user->isBanned())) {
                $user->requestReactivation($note);
                $entityManager->flush();
                $sent = true;
            }
        }

        $request->getSession()->set('reactivation_email', $email);
        if ($sent) {
            $this->addFlash('success', 'Demande envoyee. Un administrateur va examiner votre demande.');
        } else {
            $this->addFlash('error', 'Impossible d\'envoyer la demande pour cet email.');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/login/face', name: 'app_login_face', methods: ['POST'])]
    public function loginWithFace(
        Request $request,
        UserRepository $userRepository,
        FaceVerificationService $faceVerificationService,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('face-login', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_login');
        }

        /** @var UploadedFile|null $faceLoginImage */
        $faceLoginImage = $request->files->get('faceLoginImage');
        if (!$faceLoginImage instanceof UploadedFile) {
            $this->addFlash('error', 'Veuillez capturer ou choisir une photo de votre visage.');
            return $this->redirectToRoute('app_login');
        }

        $tempDir = $this->getParameter('kernel.project_dir') . '/var/face-login';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $tempFile = $tempDir . '/face-login-' . uniqid('', true) . '.jpg';
        try {
            $faceLoginImage->move($tempDir, basename($tempFile));
        } catch (\Exception) {
            $this->addFlash('error', 'Impossible de traiter la photo de connexion faciale.');
            return $this->redirectToRoute('app_login');
        }

        $candidates = $userRepository->findFaceLoginCandidates();
        if ($candidates === []) {
            @unlink($tempFile);
            $this->addFlash('error', 'Aucun compte eligible a la connexion faciale.');
            return $this->redirectToRoute('app_login');
        }

        $candidatePayload = [];
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof User || $candidate->getSelfieImage() === null) {
                continue;
            }
            $candidatePayload[] = [
                'id' => (int) $candidate->getId(),
                'path' => $this->getParameter('kernel.project_dir') . '/public' . $candidate->getSelfieImage(),
            ];
        }

        $identifyResult = $faceVerificationService->identifyBestUser(
            $tempFile,
            $candidatePayload,
            $this->getFaceMatchThreshold()
        );
        @unlink($tempFile);

        if (!$identifyResult['success']) {
            $this->addFlash('error', $identifyResult['error'] ?? 'Verification faciale indisponible.');
            return $this->redirectToRoute('app_login');
        }

        $bestUserId = $identifyResult['userId'];
        if ($bestUserId === null) {
            $this->addFlash('error', 'Visage non reconnu. Regardez la camera de face, sans contre-jour, puis reessayez.');
            return $this->redirectToRoute('app_login');
        }

        $bestUser = $userRepository->find($bestUserId);
        if (!$bestUser instanceof User) {
            $this->addFlash('error', 'Compte introuvable apres reconnaissance faciale.');
            return $this->redirectToRoute('app_login');
        }

        if ($bestUser->isDeactivatedByUser() || $bestUser->isBanned() || $bestUser->isSuspended()) {
            $this->addFlash('error', 'Compte reconnu mais non autorise a se connecter.');
            return $this->redirectToRoute('app_login');
        }

        $response = $security->login($bestUser, \App\Security\LoginFormAuthenticator::class, 'main');

        return $response ?? $this->redirectToRoute($this->resolveRouteByRole($bestUser));
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    private function resolveRouteByRole(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'admin_dashboard';
        }
        if (in_array('ROLE_HOST', $roles, true)) {
            return 'host_dashboard';
        }

        return 'app_profile';
    }

    private function getFaceMatchThreshold(): float
    {
        $raw = $_ENV['FACE_LOGIN_THRESHOLD']
            ?? $_SERVER['FACE_LOGIN_THRESHOLD']
            ?? $_ENV['FACE_MATCH_THRESHOLD']
            ?? $_SERVER['FACE_MATCH_THRESHOLD']
            ?? '0.66';
        $value = is_numeric($raw) ? (float) $raw : 0.60;
        if ($value <= 0.0 || $value > 1.5) {
            return 0.66;
        }

        return $value;
    }
}
