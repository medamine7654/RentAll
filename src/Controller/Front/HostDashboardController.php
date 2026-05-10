<?php

namespace App\Controller\Front;

use App\Entity\Logement;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/host')]
#[IsGranted('ROLE_HOST')]
class HostDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'host_dashboard')]
    public function dashboard(
        LogementRepository $logementRepository,
        ReservationRepository $reservationRepository
    ): Response
    {
        $user = $this->getUser();
        
        // Récupérer les logements de l'hôte
        $logements = $logementRepository->findBy(['proprietaire' => $user]);
        
        // Récupérer les réservations pour les logements de l'hôte
        $reservations = [];
        foreach ($logements as $logement) {
            $reservationsLogement = $reservationRepository->findBy(
                ['logement' => $logement],
                ['dateCreation' => 'DESC']
            );
            $reservations = array_merge($reservations, $reservationsLogement);
        }
        
        // Calculer les statistiques
        $totalLogements = count($logements);
        $totalReservations = count($reservations);
        $reservationsConfirmees = count(array_filter($reservations, fn($r) => $r->getStatut() === 'confirmee'));
        
        // Calculer le revenu total
        $revenuTotal = array_reduce($reservations, function($carry, $reservation) {
            if ($reservation->getStatut() === 'confirmee') {
                return $carry + floatval($reservation->getMontantTotal());
            }
            return $carry;
        }, 0);
        
        return $this->render('front/host/dashboard.html.twig', [
            'logements' => $logements,
            'reservations' => $reservations,
            'stats' => [
                'totalLogements' => $totalLogements,
                'totalReservations' => $totalReservations,
                'reservationsConfirmees' => $reservationsConfirmees,
                'revenuTotal' => $revenuTotal,
            ],
        ]);
    }
    
    #[Route('/logements', name: 'host_logements')]
    public function logements(LogementRepository $logementRepository): Response
    {
        $user = $this->getUser();
        $logements = $logementRepository->findBy(['proprietaire' => $user], ['id' => 'DESC']);
        
        return $this->render('front/host/logements.html.twig', [
            'logements' => $logements,
        ]);
    }
    
    #[Route('/logements/new', name: 'host_logement_new', methods: ['GET', 'POST'])]
    public function newLogement(Request $request, EntityManagerInterface $entityManager): Response
    {
        $logement = new Logement();
        $logement->setProprietaire($this->getUser());
        $logement->setDisponible(true);
        
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($logement);
                $entityManager->flush();
                
                $this->addFlash('success', 'Logement créé avec succès!');
                return $this->redirectToRoute('host_logements');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création: ' . $e->getMessage());
            }
        }
        
        return $this->render('front/host/logement_new.html.twig', [
            'form' => $form->createView(),
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', 422) : null);
    }
    
    #[Route('/logements/{id}/edit', name: 'host_logement_edit', methods: ['GET', 'POST'])]
    public function editLogement(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire
        if ($logement->getProprietaire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce logement.');
            return $this->redirectToRoute('host_logements');
        }
        
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                
                $this->addFlash('success', 'Logement modifié avec succès!');
                return $this->redirectToRoute('host_logements');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }
        
        return $this->render('front/host/logement_edit.html.twig', [
            'form' => $form->createView(),
            'logement' => $logement,
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', 422) : null);
    }
    
    #[Route('/logements/{id}/toggle', name: 'host_logement_toggle', methods: ['POST'])]
    public function toggleLogement(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire
        if ($logement->getProprietaire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce logement.');
            return $this->redirectToRoute('host_logements');
        }

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle'.$logement->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_home');
        }
        
        $logement->setDisponible(!$logement->isDisponible());
        $entityManager->flush();
        
        $status = $logement->isDisponible() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Logement $status avec succès!");
        
        return $this->redirectToRoute('app_home');
    }
    
    #[Route('/reservations', name: 'host_reservations')]
    public function reservations(
        LogementRepository $logementRepository,
        ReservationRepository $reservationRepository
    ): Response
    {
        $user = $this->getUser();
        $logements = $logementRepository->findBy(['proprietaire' => $user]);
        
        // Récupérer toutes les réservations pour les logements de l'hôte
        $reservations = [];
        foreach ($logements as $logement) {
            $reservationsLogement = $reservationRepository->findBy(
                ['logement' => $logement],
                ['dateCreation' => 'DESC']
            );
            $reservations = array_merge($reservations, $reservationsLogement);
        }
        
        // Séparer les réservations en attente des autres
        $reservationsEnAttente = array_filter($reservations, fn($r) => $r->getStatut() === 'en_attente');
        $autresReservations = array_filter($reservations, fn($r) => $r->getStatut() !== 'en_attente');
        
        return $this->render('front/host/reservations.html.twig', [
            'reservations' => $reservations,
            'reservationsEnAttente' => $reservationsEnAttente,
            'autresReservations' => $autresReservations,
        ]);
    }
    
    #[Route('/reservations/{id}/accept', name: 'host_reservation_accept', methods: ['POST'])]
    public function acceptReservation(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $reservation = $reservationRepository->find($id);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire du logement
        if ($reservation->getLogement()->getProprietaire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('accept'.$reservation->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Vérifier que la réservation est en attente
        if ($reservation->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cette réservation n\'est plus en attente.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Accepter la réservation
        $reservation->setStatut('confirmee');
        $entityManager->flush();
        
        $this->addFlash('success', 'Réservation acceptée avec succès!');
        return $this->redirectToRoute('host_reservations');
    }
    
    #[Route('/reservations/{id}/reject', name: 'host_reservation_reject', methods: ['POST'])]
    public function rejectReservation(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $reservation = $reservationRepository->find($id);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire du logement
        if ($reservation->getLogement()->getProprietaire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject'.$reservation->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Vérifier que la réservation est en attente
        if ($reservation->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cette réservation n\'est plus en attente.');
            return $this->redirectToRoute('host_reservations');
        }
        
        // Refuser la réservation
        $reservation->setStatut('refusee');
        $entityManager->flush();
        
        $this->addFlash('success', 'Réservation refusée.');
        return $this->redirectToRoute('host_reservations');
    }
}
