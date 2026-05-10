<?php

namespace App\Controller\Front;

use App\Entity\Covoiturage;
use App\Entity\Participant;
use App\Entity\User;
use App\Form\CovoiturageType;
use App\Repository\CovoiturageRepository;
use App\Repository\ParticipantRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/covoiturage')]
class CovoiturageController extends AbstractController
{
    #[Route('/', name: 'app_covoiturage')]
    public function index(
        Request $request,
        CovoiturageRepository $covoiturageRepository,
        ParticipantRepository $participantRepository
    ): Response
    {
        $filters = [
            'search' => trim((string) $request->query->get('search', '')),
            'depart' => trim((string) $request->query->get('depart', '')),
            'destination' => trim((string) $request->query->get('destination', '')),
            'sort' => (string) $request->query->get('sort', 'date_desc'),
        ];

        $trips = $covoiturageRepository->searchWithSort(
            $filters['search'] !== '' ? $filters['search'] : null,
            $filters['depart'] !== '' ? $filters['depart'] : null,
            $filters['destination'] !== '' ? $filters['destination'] : null,
            $filters['sort']
        );

        $tripIds = array_map(static fn (Covoiturage $trip): ?int => $trip->getId(), $trips);
        $tripIds = array_values(array_filter($tripIds, static fn (?int $id): bool => $id !== null));

        $participantsCountByTrip = $participantRepository->getCountsByTripIds($tripIds);
        $isBookedByTrip = [];
        $currentUser = $this->getUser();

        if ($currentUser instanceof User) {
            $bookedTripIds = $participantRepository->getBookedTripIdsForUser($currentUser, $tripIds);
            $isBookedByTrip = array_fill_keys($bookedTripIds, true);
        }

        return $this->render('front/covoiturage/index.html.twig', [
            'trips' => $trips,
            'filters' => $filters,
            'participantsCountByTrip' => $participantsCountByTrip,
            'isBookedByTrip' => $isBookedByTrip,
        ]);
    }

    #[Route('/ajouter', name: 'app_covoiturage_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $entityManager,
        ?NotificationService $notificationService = null
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $trip = new Covoiturage();
        $form = $this->createForm(CovoiturageType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('Utilisateur non authentifie.');
            }

            $trip->setConducteur($user);
            $entityManager->persist($trip);
            $entityManager->flush();

            // Send real-time notification to admin
            if ($notificationService) {
                try {
                    $notificationService->sendCovoiturageNotification(
                        $trip->getId(),
                        $user->getEmail(),
                        $trip->getDepart(),
                        $trip->getDestination()
                    );
                } catch (\Exception $e) {
                    // Silently fail - notification is not critical
                }
            }

            $this->addFlash('success', 'Trajet ajoute avec succes.');

            return $this->redirectToRoute('app_covoiturage');
        }

        return $this->render('front/covoiturage/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/reserver', name: 'app_covoiturage_book', methods: ['POST'])]
    public function book(
        Covoiturage $trip,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Participant table not available in current DB — feature disabled
        $this->addFlash('warning', 'La réservation de covoiturage n\'est pas disponible pour le moment.');
        return $this->redirectToRoute('app_covoiturage');
    }

    #[Route('/{id}/annuler', name: 'app_covoiturage_cancel', methods: ['POST'])]
    public function cancel(
        Covoiturage $trip,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Participant table not available in current DB — feature disabled
        $this->addFlash('warning', 'L\'annulation de covoiturage n\'est pas disponible pour le moment.');
        return $this->redirectToRoute('app_covoiturage');
    }

    #[Route('/mes-trajets', name: 'app_covoiturage_my_trips')]
    public function myTrips(
        CovoiturageRepository $covoiturageRepository,
        ParticipantRepository $participantRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        // Trajets créés par l'utilisateur (en tant que conducteur)
        $myTrips = $covoiturageRepository->findBy(['conducteur' => $user], ['dateDepart' => 'DESC']);

        // Récupérer les participants pour chaque trajet
        $participantsByTrip = [];
        foreach ($myTrips as $trip) {
            $participantsByTrip[$trip->getId()] = $participantRepository->findBy(
                ['covoiturage' => $trip],
                ['dateCreation' => 'DESC']
            );
        }

        // Trajets où l'utilisateur est passager
        $myParticipations = $participantRepository->findBy(
            ['passager' => $user],
            ['dateCreation' => 'DESC']
        );

        return $this->render('front/covoiturage/my_trips.html.twig', [
            'myTrips' => $myTrips,
            'participantsByTrip' => $participantsByTrip,
            'myParticipations' => $myParticipations,
        ]);
    }

    #[Route('/participant/{id}/accept', name: 'app_covoiturage_accept_participant', methods: ['POST'])]
    public function acceptParticipant(
        int $id,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->addFlash('warning', 'La gestion des participants n\'est pas disponible pour le moment.');
        return $this->redirectToRoute('app_covoiturage_my_trips');
    }

    #[Route('/participant/{id}/reject', name: 'app_covoiturage_reject_participant', methods: ['POST'])]
    public function rejectParticipant(
        int $id,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->addFlash('warning', 'La gestion des participants n\'est pas disponible pour le moment.');
        return $this->redirectToRoute('app_covoiturage_my_trips');
    }
}
