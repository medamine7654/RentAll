<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FaceVerificationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('front/profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/update', name: 'app_profile_update', methods: ['POST'])]
    public function update(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        FaceVerificationService $faceVerificationService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('profile-update', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $email = trim((string) $request->request->get('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Veuillez entrer un email valide.');
            return $this->redirectToRoute('app_profile');
        }

        $existing = $userRepository->findOneBy(['email' => $email]);
        if ($existing instanceof User && $existing->getId() !== $user->getId()) {
            $this->addFlash('error', 'Cet email est deja utilise.');
            return $this->redirectToRoute('app_profile');
        }

        /** @var UploadedFile|null $avatarFile */
        $avatarFile = $request->files->get('avatarFile');
        /** @var UploadedFile|null $selfieFile */
        $selfieFile = $request->files->get('selfieFile');
        /** @var UploadedFile|null $identityDocumentFile */
        $identityDocumentFile = $request->files->get('identityDocumentFile');

        $avatarPath = $this->uploadImage($avatarFile, 'avatars', $slugger, 'avatar');
        if ($avatarPath === false) {
            return $this->redirectToRoute('app_profile');
        }
        if (is_string($avatarPath)) {
            $user->setAvatar($avatarPath);
        }

        $selfiePath = $this->uploadImage($selfieFile, 'verification/selfies', $slugger, 'selfie');
        if ($selfiePath === false) {
            return $this->redirectToRoute('app_profile');
        }
        if (is_string($selfiePath)) {
            $user->setSelfieImage($selfiePath);
        }

        $identityPath = $this->uploadImage($identityDocumentFile, 'verification/identity', $slugger, 'piece-identite');
        if ($identityPath === false) {
            return $this->redirectToRoute('app_profile');
        }
        if (is_string($identityPath)) {
            $user->setIdentityDocumentImage($identityPath);
        }

        $user
            ->setFirstName($request->request->get('first_name') !== null ? (string) $request->request->get('first_name') : null)
            ->setLastName($request->request->get('last_name') !== null ? (string) $request->request->get('last_name') : null)
            ->setPhone($request->request->get('phone') !== null ? (string) $request->request->get('phone') : null)
            ->setEmail($email);

        $faceVerificationAttempted = false;
        $faceVerificationMatched = null;

        if ($selfiePath !== null || $identityPath !== null) {
            $faceVerificationAttempted = true;
            if ($user->getSelfieImage() !== null && $user->getIdentityDocumentImage() !== null) {
                $selfieAbsolutePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getSelfieImage();
                $identityAbsolutePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getIdentityDocumentImage();
                $faceResult = $faceVerificationService->verifyFaces($selfieAbsolutePath, $identityAbsolutePath);

                if ($faceResult['success'] && $faceResult['match']) {
                    $faceVerificationMatched = true;
                    $user->setIsVerified(true);
                    $user->setFaceVerifiedAt(new \DateTimeImmutable());
                    $this->addFlash('success', 'Verification faciale reussie. Votre compte est maintenant verifie.');
                } else {
                    $faceVerificationMatched = false;
                    $user->setIsVerified(false);
                    $user->setFaceVerifiedAt(null);
                    $this->addFlash(
                        'error',
                        $faceResult['error'] ?? 'Nous n avons pas pu confirmer votre identite. Merci de reessayer avec des images plus nettes.'
                    );
                }
            } else {
                $faceVerificationMatched = false;
                $user->setIsVerified(false);
                $user->setFaceVerifiedAt(null);
                $this->addFlash('warning', 'Ajoutez les deux photos (selfie + piece d identite) pour verifier le compte.');
            }
        }

        $entityManager->flush();
        if (!$faceVerificationAttempted || $faceVerificationMatched === true) {
            $this->addFlash('success', 'Profil mis a jour.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/deactivate', name: 'app_profile_deactivate', methods: ['POST'])]
    public function deactivate(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        ?NotificationService $notificationService = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('profile-deactivate', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $user->deactivateByUser();
        $entityManager->flush();

        if ($notificationService) {
            $notificationService->sendAccountDeactivatedNotification((int) $user->getId(), (string) $user->getEmail());
        }

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        $this->addFlash('warning', 'Votre compte est desactive. Vous pouvez demander sa reactivation depuis la page de connexion.');

        return $this->redirectToRoute('app_login');
    }

    private function uploadImage(
        ?UploadedFile $file,
        string $subdirectory,
        SluggerInterface $slugger,
        string $filenamePrefix
    ): string|false|null {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $maxSizeMb = $this->resolveUploadMaxMb();
        $maxSizeBytes = $maxSizeMb * 1024 * 1024;
        if ($file->getSize() !== null && $file->getSize() > $maxSizeBytes) {
            $this->addFlash('error', sprintf('Image trop volumineuse (max %dMB).', $maxSizeMb));
            return false;
        }

        $clientMimeType = strtolower((string) $file->getClientMimeType());
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());

        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (
            !in_array($clientMimeType, $allowedMimeTypes, true)
            && !in_array($clientExtension, $allowedExtensions, true)
        ) {
            $this->addFlash('error', 'Format d image invalide. Utilisez JPG, PNG ou WEBP.');
            return false;
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = (string) $slugger->slug($originalFilename ?: $filenamePrefix);
        $extension = in_array($clientExtension, $allowedExtensions, true)
            ? $clientExtension
            : ($clientMimeType === 'image/png' ? 'png' : ($clientMimeType === 'image/webp' ? 'webp' : 'jpg'));
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . trim($subdirectory, '/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        try {
            $file->move($uploadDir, $newFilename);
        } catch (FileException) {
            $this->addFlash('error', 'Echec de l upload de la photo.');
            return false;
        }

        return '/uploads/' . trim($subdirectory, '/') . '/' . $newFilename;
    }

    private function resolveUploadMaxMb(): int
    {
        $raw = $_ENV['PROFILE_UPLOAD_MAX_MB'] ?? $_SERVER['PROFILE_UPLOAD_MAX_MB'] ?? '8';
        $value = is_numeric($raw) ? (int) $raw : 8;
        if ($value < 2) {
            return 2;
        }
        if ($value > 20) {
            return 20;
        }

        return $value;
    }
}
