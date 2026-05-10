<?php

namespace App\Controller\Front;

use App\Entity\Reservation;
use App\Entity\Logement;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\PdfGenerator;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        // Récupérer uniquement les réservations de l'utilisateur connecté
        $user = $this->getUser();
        $reservations = $reservationRepository->findBy(['locataire' => $user], ['dateCreation' => 'DESC']);

        return $this->render('front/reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        int $id, 
        EntityManagerInterface $entityManager, 
        ReservationRepository $reservationRepository,
        ?NotificationService $notificationService = null
    ): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation.');
            return $this->redirectToRoute('app_login');
        }

        // Charger le logement
        $logement = $entityManager->getRepository(Logement::class)->find($id);
        
        if (!$logement) {
            $this->addFlash('error', 'Ce logement n\'existe pas.');
            return $this->redirectToRoute('app_home');
        }

        // Empêcher un propriétaire de réserver son propre logement
        if ($logement->getProprietaire() === $user) {
            $this->addFlash('error', 'Vous ne pouvez pas réserver votre propre logement.');
            return $this->redirectToRoute('app_home');
        }

        if (!$logement->isActive()) {
            $this->addFlash('error', 'Ce logement n\'est pas disponible.');
            return $this->redirectToRoute('app_home');
        }

        $reservation = new Reservation();
        $reservation->setLogement($logement);
        $reservation->setLocataire($user); // Associer l'utilisateur connecté
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        // Debug: vérifier si le formulaire est soumis
        if ($form->isSubmitted()) {
            $this->addFlash('info', 'Formulaire soumis');
            
            if (!$form->isValid()) {
                // Afficher les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Formulaire invalide. Erreurs: ' . (empty($errors) ? 'Aucune erreur spécifique' : implode(', ', $errors)));
                
                // Debug: afficher les données du formulaire
                $data = $form->getData();
                $this->addFlash('info', sprintf(
                    'Données: dateDebut=%s, dateFin=%s, nbPersonnes=%s',
                    $data->getDateDebut() ? $data->getDateDebut()->format('Y-m-d') : 'null',
                    $data->getDateFin() ? $data->getDateFin()->format('Y-m-d') : 'null',
                    $data->getNombrePersonnes() ?? 'null'
                ));
            } else {
                $this->addFlash('success', 'Formulaire valide, traitement en cours...');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la date de fin est après la date de début
            if ($reservation->getDateFin() <= $reservation->getDateDebut()) {
                $this->addFlash('error', 'La date de départ doit être après la date d\'arrivée.');
                return $this->render('front/reservation/new.html.twig', [
                    'logement' => $logement,
                    'form' => $form->createView(),
                    'nombreNuits' => 0,
                    'montantTotal' => 0,
                ], new Response('', 422));
            }

            // Vérifier la capacité du logement
            if ($reservation->getNombrePersonnes() > $logement->getCapacite()) {
                $this->addFlash('error', 'Le nombre de personnes dépasse la capacité du logement (' . $logement->getCapacite() . ' personnes max).');
                return $this->render('front/reservation/new.html.twig', [
                    'logement' => $logement,
                    'form' => $form->createView(),
                    'nombreNuits' => 0,
                    'montantTotal' => 0,
                ], new Response('', 422));
            }

            // Vérifier la disponibilité (pas de chevauchement de dates)
            $dateDebut = $reservation->getDateDebut();
            $dateFin = $reservation->getDateFin();
            
            $reservationsExistantes = $reservationRepository->createQueryBuilder('r')
                ->where('r.logement = :logement')
                ->andWhere('r.statut != :statut')
                ->andWhere('(
                    (r.dateDebut <= :dateDebut AND r.dateFin > :dateDebut) OR
                    (r.dateDebut < :dateFin AND r.dateFin >= :dateFin) OR
                    (r.dateDebut >= :dateDebut AND r.dateFin <= :dateFin)
                )')
                ->setParameter('logement', $logement)
                ->setParameter('statut', 'annulee')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin)
                ->getQuery()
                ->getResult();

            if (count($reservationsExistantes) > 0) {
                $this->addFlash('error', 'Ce logement n\'est pas disponible pour ces dates. Veuillez choisir d\'autres dates.');
                return $this->render('front/reservation/new.html.twig', [
                    'logement' => $logement,
                    'form' => $form->createView(),
                    'nombreNuits' => $reservation->getNombreNuits(),
                    'montantTotal' => 0,
                ], new Response('', 422));
            }

            // Calculer le montant total
            $nombreNuits = $reservation->getNombreNuits();
            $montantTotal = $nombreNuits * floatval($logement->getPrixParNuit());

            $reservation->setMontantTotal((string)$montantTotal);
            // IMPORTANT: La réservation est en attente de validation par l'hôte
            $reservation->setStatut('en_attente');
            $reservation->setDateCreation(new \DateTime());

            $entityManager->persist($reservation);
            $entityManager->flush();

            // Send real-time notification to admin
            if ($notificationService) {
                try {
                    $notificationService->sendReservationNotification(
                        $reservation->getId(),
                        $user->getEmail(),
                        $logement->getTitre()
                    );
                } catch (\Exception $e) {
                    // Silently fail - notification is not critical
                }
            }

            $this->addFlash('success', 'Demande de réservation envoyée avec succès ! L\'hôte doit maintenant accepter votre demande. Montant total : ' . $montantTotal . '€ pour ' . $nombreNuits . ' nuit(s).');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        // Calculer le montant pour l'affichage
        $nombreNuits = 0;
        $montantTotal = 0;
        if ($reservation->getDateDebut() && $reservation->getDateFin()) {
            $nombreNuits = $reservation->getNombreNuits();
            $montantTotal = $nombreNuits * floatval($logement->getPrixParNuit());
        }

        return $this->render('front/reservation/new.html.twig', [
            'logement' => $logement,
            'form' => $form->createView(),
            'nombreNuits' => $nombreNuits,
            'montantTotal' => $montantTotal,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        // Vérifier que l'utilisateur connecté est bien le propriétaire de la réservation
        if ($reservation->getLocataire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('front/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur connecté est bien le propriétaire de la réservation
        if ($reservation->getLocataire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Vérifier si la réservation peut être annulée
        if (!$reservation->peutEtreAnnulee()) {
            $this->addFlash('error', 'Cette réservation ne peut plus être annulée (moins de 3 jours avant l\'arrivée).');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        $reservation->setStatut('annulee');
        $entityManager->flush();

        $this->addFlash('success', 'Réservation annulée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/pdf', name: 'app_reservation_pdf', methods: ['GET'])]
    public function downloadPdf(Reservation $reservation, PdfGenerator $pdfGenerator): Response
    {
        // Vérifier que l'utilisateur connecté est bien le propriétaire de la réservation
        if ($reservation->getLocataire() !== $this->getUser() && 
            $reservation->getLogement()->getProprietaire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $pdfContent = $pdfGenerator->generatePdf('pdf/reservation.html.twig', [
            'reservation' => $reservation,
        ]);

        $filename = sprintf('reservation-%d-%s.pdf', 
            $reservation->getId(), 
            date('Y-m-d')
        );

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}
