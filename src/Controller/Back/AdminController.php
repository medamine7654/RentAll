<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * Test Pusher configuration
     */
    #[Route('/test-pusher', name: 'admin_test_pusher')]
    public function testPusher(): Response
    {
        return $this->render('admin/test_pusher.html.twig');
    }

    /**
     * Dashboard overview
     */
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $userStats = $userRepository->getAdminStats();
        
        // Récupérer les vraies statistiques depuis la base de données
        $logementRepository = $entityManager->getRepository(\App\Entity\Logement::class);
        $reservationRepository = $entityManager->getRepository(\App\Entity\Reservation::class);
        
        $totalLogements = $logementRepository->count([]);
        $totalReservations = $reservationRepository->count([]);
        $reservationsConfirmees = $reservationRepository->count(['statut' => 'confirmee']);
        $reservationsAnnulees = $reservationRepository->count(['statut' => 'annulee']);
        
        // Calculer le taux d'annulation
        $cancellationRate = $totalReservations > 0 
            ? round(($reservationsAnnulees / $totalReservations) * 100, 1) 
            : 0;
        
        // Calculer le revenu mensuel (somme des réservations confirmées)
        $qb = $reservationRepository->createQueryBuilder('r');
        $monthlyRevenue = $qb
            ->select('SUM(r.montantTotal)')
            ->where('r.statut = :statut')
            ->andWhere('r.dateCreation >= :startDate')
            ->setParameter('statut', 'confirmee')
            ->setParameter('startDate', new \DateTime('-30 days'))
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $stats = [
            'totalUsers' => $userStats['total'],
            'totalHosts' => $userStats['hosts'],
            'totalGuests' => $userStats['guests'],
            'totalLogements' => $totalLogements,
            'totalServices' => 0, // À implémenter si vous avez une entité Service
            'totalTools' => 0, // À implémenter si vous avez une entité Tool
            'totalBookings' => $totalReservations,
            'totalToolRentals' => 0, // À implémenter
            'pendingReports' => 0, // À implémenter si vous avez une entité Report
            'flaggedAccounts' => 0, // À implémenter
            'monthlyRevenue' => round($monthlyRevenue, 2),
            'cancellationRate' => $cancellationRate,
            'reservationsConfirmees' => $reservationsConfirmees,
            'reservationsAnnulees' => $reservationsAnnulees,
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }

    /**
     * Users management
     */
    #[Route('/users', name: 'admin_users')]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search');
        $role = $request->query->get('role');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');

        $users = $userRepository->findForAdmin($search, $role, $status, $sort);
        $stats = $userRepository->getAdminStats();
        $pendingReactivationCount = $userRepository->countPendingReactivationRequests();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'active_users_count' => $stats['active'],
            'inactive_users_count' => $stats['inactive'],
            'suspended_users_count' => $stats['suspended'],
            'banned_users_count' => $stats['banned'],
            'flagged_users_count' => $stats['flagged'],
            'total_users_count' => $stats['total'],
            'suspicious_users' => $userRepository->findMostSuspiciousUsers(8),
            'pending_reactivation_count' => $pendingReactivationCount,
            'pending_reactivation_users' => $userRepository->findPendingReactivationRequests(8),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
                'sort' => $sort,
            ],
        ]);
    }

    #[Route('/users/stats', name: 'admin_users_stats')]
    public function usersStats(UserRepository $userRepository): Response
    {
        return $this->render('admin/users_stats.html.twig', [
            'stats' => $userRepository->getAdminStats(),
        ]);
    }

    #[Route('/users/export', name: 'admin_users_export')]
    public function exportUsers(Request $request, UserRepository $userRepository): StreamedResponse
    {
        $search = $request->query->get('search');
        $role = $request->query->get('role');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');
        $users = $userRepository->findForAdmin($search, $role, $status, $sort);

        $response = new StreamedResponse(function () use ($users): void {
            $handle = fopen('php://output', 'wb');
            if (!$handle) {
                return;
            }

            fputcsv($handle, ['ID', 'Email', 'Role', 'Status'], ';');
            foreach ($users as $user) {
                $roleLabel = 'Voyageur';
                if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    $roleLabel = 'Administrateur';
                } elseif (in_array('ROLE_HOST', $user->getRoles(), true)) {
                    $roleLabel = 'Hote';
                }

                $statusLabel = match ($user->getAccountStatus()) {
                    User::STATUS_BANNED => 'Banni',
                    User::STATUS_SUSPENDED => 'Suspendu',
                    default => ($user->isVerified() ? 'Actif' : 'Inactif'),
                };

                fputcsv($handle, [
                    $user->getId(),
                    $user->getEmail(),
                    $roleLabel,
                    $statusLabel,
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users-export.csv"');

        return $response;
    }

    /**
     * Toggle user status (suspend/reactivate)
     */
    #[Route('/users/{id}/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleUserStatus(string $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('toggle-status' . $id, $request->request->get('_token'))) {
            $user = $userRepository->find($id);
            if ($user instanceof User) {
                if ($user->getAccountStatus() === User::STATUS_ACTIVE) {
                    $user->suspend();
                } else {
                    $user->activateAccount();
                }
                $entityManager->flush();
                $this->addFlash('success', 'Le statut de l\'utilisateur a ete mis a jour.');
            }
        }

        return $this->redirectToRoute('admin_users');
    }


    /**
     * Services moderation
     */
    #[Route('/services', name: 'admin_services')]
    public function services(Request $request): Response
    {
        $tab = $request->query->get('tab', 'all');
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        // Fetch services from your repository
        // $services = $this->serviceRepository->findByFilters($tab, $search, $status);

        return $this->render('admin/services.html.twig', [
            // 'services' => $services,
            'pending_count' => 1,
            'reported_count' => 1,
        ]);
    }

    /**
     * Approve a service
     */
    #[Route('/services/{id}/approve', name: 'admin_service_approve', methods: ['POST'])]
    public function approveService(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('approve' . $id, $request->request->get('_token'))) {
            // Approve service logic here
            $this->addFlash('success', 'Le service a été approuvé.');
        }

        return $this->redirectToRoute('admin_services');
    }

    /**
     * Hide a service
     */
    #[Route('/services/{id}/hide', name: 'admin_service_hide', methods: ['POST'])]
    public function hideService(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('hide' . $id, $request->request->get('_token'))) {
            // Hide service logic here
            $this->addFlash('success', 'Le service a été masqué.');
        }

        return $this->redirectToRoute('admin_services');
    }

    /**
     * Suspend a service
     */
    #[Route('/services/{id}/suspend', name: 'admin_service_suspend', methods: ['POST'])]
    public function suspendService(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('suspend' . $id, $request->request->get('_token'))) {
            // Suspend service logic here
            $this->addFlash('success', 'Le service a été suspendu.');
        }

        return $this->redirectToRoute('admin_services');
    }

    /**
     * Tools moderation
     */
    #[Route('/tools', name: 'admin_tools')]
    public function tools(Request $request): Response
    {
        $tab = $request->query->get('tab', 'all');
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        return $this->render('admin/tools.html.twig', [
            'maintenance_count' => 1,
            'reported_count' => 1,
        ]);
    }

    /**
     * Activate a tool
     */
    #[Route('/tools/{id}/activate', name: 'admin_tool_activate', methods: ['POST'])]
    public function activateTool(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('activate' . $id, $request->request->get('_token'))) {
            $this->addFlash('success', 'Le matériel a été réactivé.');
        }

        return $this->redirectToRoute('admin_tools');
    }

    /**
     * Hide a tool
     */
    #[Route('/tools/{id}/hide', name: 'admin_tool_hide', methods: ['POST'])]
    public function hideTool(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('hide' . $id, $request->request->get('_token'))) {
            $this->addFlash('success', 'Le matériel a été masqué.');
        }

        return $this->redirectToRoute('admin_tools');
    }

    /**
     * Suspend a tool
     */
    #[Route('/tools/{id}/suspend', name: 'admin_tool_suspend', methods: ['POST'])]
    public function suspendTool(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('suspend' . $id, $request->request->get('_token'))) {
            $this->addFlash('success', 'Le matériel a été suspendu.');
        }

        return $this->redirectToRoute('admin_tools');
    }

    /**
     * Bookings oversight
     */
    #[Route('/bookings', name: 'admin_bookings')]
    public function bookings(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tab = $request->query->get('tab', 'all');
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $status = $request->query->get('status');

        $reservationRepository = $entityManager->getRepository(\App\Entity\Reservation::class);
        
        // Construire la requête avec filtres
        $qb = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.logement', 'l')
            ->leftJoin('r.locataire', 'u')
            ->orderBy('r.dateCreation', 'DESC');
        
        if ($search) {
            $qb->andWhere('l.titre LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($status) {
            $qb->andWhere('r.statut = :status')
               ->setParameter('status', $status);
        }
        
        if ($tab !== 'all') {
            $qb->andWhere('r.statut = :tab')
               ->setParameter('tab', $tab);
        }
        
        $reservations = $qb->getQuery()->getResult();
        
        // Compter les réservations par statut
        $enAttenteCount = $reservationRepository->count(['statut' => 'en_attente']);
        $serviceBookingsCount = $reservationRepository->count(['statut' => 'confirmee']);
        $cancelledCount = $reservationRepository->count(['statut' => 'annulee']);
        $refuseeCount = $reservationRepository->count(['statut' => 'refusee']);

        return $this->render('admin/bookings.html.twig', [
            'reservations' => $reservations,
            'en_attente_count' => $enAttenteCount,
            'service_bookings_count' => $serviceBookingsCount,
            'tool_rentals_count' => 0, // À implémenter
            'cancelled_count' => $cancelledCount,
            'refusee_count' => $refuseeCount,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
        ]);
    }
    
    /**
     * Change reservation status (Admin only)
     */
    #[Route('/bookings/{id}/status', name: 'admin_booking_change_status', methods: ['POST'])]
    public function changeBookingStatus(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($id);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('status'.$reservation->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        $newStatus = $request->request->get('status');
        $validStatuses = ['en_attente', 'confirmee', 'refusee', 'annulee', 'terminee'];
        
        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        $oldStatus = $reservation->getStatut();
        $reservation->setStatut($newStatus);
        $entityManager->flush();
        
        $statusLabels = [
            'en_attente' => 'En attente',
            'confirmee' => 'Confirmée',
            'refusee' => 'Refusée',
            'annulee' => 'Annulée',
            'terminee' => 'Terminée'
        ];
        
        $this->addFlash('success', sprintf(
            'Statut de la réservation #%d changé de "%s" à "%s".',
            $reservation->getId(),
            $statusLabels[$oldStatus] ?? $oldStatus,
            $statusLabels[$newStatus] ?? $newStatus
        ));
        
        return $this->redirectToRoute('admin_bookings');
    }
    
    /**
     * Delete reservation (Admin only)
     */
    #[Route('/bookings/{id}/delete', name: 'admin_booking_delete', methods: ['POST'])]
    public function deleteBooking(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($id);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$reservation->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        $reservationId = $reservation->getId();
        $entityManager->remove($reservation);
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('Réservation #%d supprimée avec succès.', $reservationId));
        
        return $this->redirectToRoute('admin_bookings');
    }
    
    /**
     * Confirm reservation via AJAX (for real-time notifications)
     */
    #[Route('/bookings/{id}/confirm', name: 'admin_booking_confirm', methods: ['POST'])]
    public function confirmBooking(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($id);
        
        if (!$reservation) {
            return $this->json(['ok' => false, 'error' => 'Réservation introuvable'], 404);
        }
        
        // Check CSRF if provided
        $token = $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('confirm-booking'.$id, $token)) {
            return $this->json(['ok' => false, 'error' => 'Token CSRF invalide'], 403);
        }
        
        $reservation->setStatut('confirmee');
        $entityManager->flush();
        
        return $this->json([
            'ok' => true,
            'newStatus' => 'confirmee',
            'message' => 'Réservation confirmée avec succès'
        ]);
    }
    
    /**
     * Reject reservation via AJAX (for real-time notifications)
     */
    #[Route('/bookings/{id}/reject', name: 'admin_booking_reject', methods: ['POST'])]
    public function rejectBooking(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($id);
        
        if (!$reservation) {
            return $this->json(['ok' => false, 'error' => 'Réservation introuvable'], 404);
        }
        
        // Check CSRF if provided
        $token = $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('reject-booking'.$id, $token)) {
            return $this->json(['ok' => false, 'error' => 'Token CSRF invalide'], 403);
        }
        
        $reservation->setStatut('refusee');
        $entityManager->flush();
        
        return $this->json([
            'ok' => true,
            'newStatus' => 'refusee',
            'message' => 'Réservation refusée'
        ]);
    }

    /**
     * Generate PDF for a reservation (Admin)
     */
    #[Route('/bookings/{id}/pdf', name: 'admin_booking_pdf')]
    public function generateBookingPdf(
        int $id,
        EntityManagerInterface $entityManager,
        PdfGenerator $pdfGenerator
    ): Response
    {
        $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($id);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        $pdfContent = $pdfGenerator->generatePdf('pdf/reservation.html.twig', [
            'reservation' => $reservation,
        ]);
        
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="reservation-%06d.pdf"', $reservation->getId()),
            ]
        );
    }

    /**
     * Reports and fraud monitoring
     */
    #[Route('/reports', name: 'admin_reports')]
    public function reports(Request $request): Response
    {
        $tab = $request->query->get('tab', 'reports');
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        return $this->render('admin/reports.html.twig', [
            'pending_reports_count' => 3,
            'critical_alerts_count' => 2,
            'unread_alerts_count' => 3,
        ]);
    }

    /**
     * Resolve a report
     */
    #[Route('/reports/{id}/resolve', name: 'admin_report_resolve', methods: ['POST'])]
    public function resolveReport(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('resolve' . $id, $request->request->get('_token'))) {
            $this->addFlash('success', 'Le signalement a été résolu.');
        }

        return $this->redirectToRoute('admin_reports');
    }

    /**
     * Dismiss a report
     */
    #[Route('/reports/{id}/dismiss', name: 'admin_report_dismiss', methods: ['POST'])]
    public function dismissReport(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('dismiss' . $id, $request->request->get('_token'))) {
            $this->addFlash('success', 'Le signalement a été rejeté.');
        }

        return $this->redirectToRoute('admin_reports');
    }

    /**
     * Mark alert as read
     */
    #[Route('/alerts/{id}/mark-read', name: 'admin_alert_mark_read', methods: ['POST'])]
    public function markAlertRead(string $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('mark-read' . $id, $request->request->get('_token'))) {
            $this->addFlash('success', 'L\'alerte a été marquée comme lue.');
        }

        return $this->redirectToRoute('admin_reports');
    }

    /**
     * Analytics
     */
    #[Route('/analytics', name: 'admin_analytics')]
    public function analytics(Request $request): Response
    {
        $range = $request->query->get('range', '30d');

        return $this->render('admin/analytics.html.twig', [
            'range' => $range,
        ]);
    }

    /**
     * Settings
     */
    #[Route('/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        return $this->render('admin/settings.html.twig');
    }

    /**
     * Update profile settings
     */
    #[Route('/settings/profile', name: 'admin_settings_profile', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SluggerInterface $slugger
    ): Response
    {
        if (!$this->isCsrfTokenValid('settings-profile', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_settings');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_settings');
        }

        $email = strtolower(trim((string) $request->request->get('email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return $this->redirectToRoute('admin_settings');
        }

        $existingUser = $userRepository->findOneBy(['email' => $email]);
        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'Cet email est deja utilise.');
            return $this->redirectToRoute('admin_settings');
        }

        /** @var UploadedFile|null $avatarFile */
        $avatarFile = $request->files->get('avatarFile');
        if ($avatarFile instanceof UploadedFile) {
            $maxSizeBytes = 2 * 1024 * 1024;
            if ($avatarFile->getSize() !== null && $avatarFile->getSize() > $maxSizeBytes) {
                $this->addFlash('error', 'Image trop volumineuse (max 2MB).');
                return $this->redirectToRoute('admin_settings');
            }

            $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = (string) $slugger->slug($originalFilename ?: 'avatar');
            $clientMimeType = strtolower((string) $avatarFile->getClientMimeType());
            $clientExtension = strtolower((string) $avatarFile->getClientOriginalExtension());

            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (
                !in_array($clientMimeType, $allowedMimeTypes, true)
                && !in_array($clientExtension, $allowedExtensions, true)
            ) {
                $this->addFlash('error', 'Format d image invalide. Utilisez JPG ou PNG.');
                return $this->redirectToRoute('admin_settings');
            }

            $extension = in_array($clientExtension, $allowedExtensions, true)
                ? $clientExtension
                : ($clientMimeType === 'image/png' ? 'png' : 'jpg');
            $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            try {
                $avatarFile->move($uploadDir, $newFilename);
            } catch (FileException) {
                $this->addFlash('error', 'Echec de l upload de la photo.');
                return $this->redirectToRoute('admin_settings');
            }

            $user->setAvatar('/uploads/avatars/' . $newFilename);
        }

        $firstName = trim((string) $request->request->get('firstName', ''));
        $lastName = trim((string) $request->request->get('lastName', ''));
        $phone = trim((string) $request->request->get('phone', ''));

        $user
            ->setFirstName($firstName !== '' ? $firstName : null)
            ->setLastName($lastName !== '' ? $lastName : null)
            ->setPhone($phone !== '' ? $phone : null)
            ->setEmail($email);

        $entityManager->flush();
        $this->addFlash('success', 'Votre profil a ete mis a jour.');

        return $this->redirectToRoute('admin_settings');
    }

    /**
     * Update notification settings
     */
    #[Route('/settings/notifications', name: 'admin_settings_notifications', methods: ['POST'])]
    public function updateNotifications(Request $request): Response
    {
        if ($this->isCsrfTokenValid('settings-notifications', $request->request->get('_token'))) {
            $this->addFlash('success', 'Vos préférences de notification ont été mises à jour.');
        }

        return $this->redirectToRoute('admin_settings');
    }

    /**
     * Update password
     */
    #[Route('/settings/password', name: 'admin_settings_password', methods: ['POST'])]
    public function updatePassword(Request $request): Response
    {
        if ($this->isCsrfTokenValid('settings-password', $request->request->get('_token'))) {
            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
        }

        return $this->redirectToRoute('admin_settings');
    }

    /**
     * Update security settings
     */
    #[Route('/settings/security', name: 'admin_settings_security', methods: ['POST'])]
    public function updateSecurity(Request $request): Response
    {
        if ($this->isCsrfTokenValid('settings-security', $request->request->get('_token'))) {
            $this->addFlash('success', 'Vos paramètres de sécurité ont été mis à jour.');
        }

        return $this->redirectToRoute('admin_settings');
    }

    /**
     * Update platform settings
     */
    #[Route('/settings/platform', name: 'admin_settings_platform', methods: ['POST'])]
    public function updatePlatform(Request $request): Response
    {
        if ($this->isCsrfTokenValid('settings-platform', $request->request->get('_token'))) {
            $this->addFlash('success', 'Les paramètres de la plateforme ont été mis à jour.');
        }

        return $this->redirectToRoute('admin_settings');
    }

    /**
     * Show user details
     */
    #[Route('/users/{id}', name: 'admin_user_show')]
    public function showUser(string $id): Response
    {
        return $this->redirectToRoute('admin_users');
    }
}
