<?php

namespace App\Controller\Back;

use App\Entity\Covoiturage;
use App\Form\CovoiturageType;
use App\Repository\CovoiturageRepository;
use App\Repository\ParticipantRepository;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/covoiturages')]
#[IsGranted('ROLE_ADMIN')]
class CovoiturageBackController extends AbstractController
{
    #[Route('', name: 'admin_covoiturages')]
    public function index(Request $request, CovoiturageRepository $covoiturageRepository): Response
    {
        $filters = [
            'search' => trim((string) $request->query->get('search', '')),
            'depart' => trim((string) $request->query->get('depart', '')),
            'destination' => trim((string) $request->query->get('destination', '')),
            'sort' => (string) $request->query->get('sort', 'date_desc'),
        ];

        $covoiturages = $covoiturageRepository->findForAdmin(
            $filters['search'] !== '' ? $filters['search'] : null,
            $filters['depart'] !== '' ? $filters['depart'] : null,
            $filters['destination'] !== '' ? $filters['destination'] : null,
            $filters['sort']
        );

        return $this->render('admin/covoiturages.html.twig', [
            'covoiturages' => $covoiturages,
            'total_covoiturages_count' => $covoiturageRepository->count([]),
            'filters' => $filters,
        ]);
    }

    #[Route('/stats', name: 'admin_covoiturages_stats')]
    public function stats(CovoiturageRepository $covoiturageRepository): Response
    {
        return $this->render('admin/covoiturages_stats.html.twig', [
            'stats' => $covoiturageRepository->getStatsByCity(),
        ]);
    }

    #[Route('/export', name: 'admin_covoiturages_export')]
    public function export(Request $request, CovoiturageRepository $covoiturageRepository): StreamedResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $depart = trim((string) $request->query->get('depart', ''));
        $destination = trim((string) $request->query->get('destination', ''));
        $sort = (string) $request->query->get('sort', 'date_desc');

        $covoiturages = $covoiturageRepository->findForAdmin(
            $search !== '' ? $search : null,
            $depart !== '' ? $depart : null,
            $destination !== '' ? $destination : null,
            $sort
        );

        $response = new StreamedResponse(function () use ($covoiturages): void {
            $handle = fopen('php://output', 'wb');
            if (!$handle) {
                return;
            }

            fputcsv($handle, ['ID', 'Depart', 'Destination', 'Date depart', 'Places', 'Conducteur'], ';');
            foreach ($covoiturages as $trip) {
                fputcsv($handle, [
                    $trip->getId(),
                    $trip->getDepart(),
                    $trip->getDestination(),
                    $trip->getDateDepart()?->format('Y-m-d H:i:s'),
                    $trip->getPlaces(),
                    $trip->getConducteur()?->getEmail(),
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="covoiturages-export.csv"');

        return $response;
    }

    #[Route('/{id}/edit', name: 'admin_covoiturage_edit', methods: ['GET', 'POST'])]
    public function edit(Covoiturage $covoiturage, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'validate_future_date' => false, // Allow editing past dates in admin
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le covoiturage a ete modifie.');

            return $this->redirectToRoute('admin_covoiturages');
        }

        return $this->render('admin/covoiturage_edit.html.twig', [
            'covoiturage' => $covoiturage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_covoiturage_delete', methods: ['POST'])]
    public function delete(Covoiturage $covoiturage, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-covoiturage' . $covoiturage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($covoiturage);
            $entityManager->flush();
            $this->addFlash('success', 'Le covoiturage a ete supprime.');
        }

        return $this->redirectToRoute('admin_covoiturages');
    }
    
    /**
     * Confirm covoiturage via AJAX (for real-time notifications)
     */
    #[Route('/{id}/confirm', name: 'admin_covoiturage_confirm', methods: ['POST'])]
    public function confirmCovoiturage(
        Covoiturage $covoiturage,
        Request $request
    ): Response
    {
        // Check CSRF if provided
        $token = $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('confirm-covoiturage'.$covoiturage->getId(), $token)) {
            return $this->json(['ok' => false, 'error' => 'Token CSRF invalide'], 403);
        }
        
        // Covoiturage doesn't have status field, just acknowledge
        return $this->json([
            'ok' => true,
            'message' => 'Covoiturage confirmé avec succès'
        ]);
    }
    
    /**
     * Reject covoiturage via AJAX (for real-time notifications)
     */
    #[Route('/{id}/reject', name: 'admin_covoiturage_reject', methods: ['POST'])]
    public function rejectCovoiturage(
        Covoiturage $covoiturage,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check CSRF if provided
        $token = $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('reject-covoiturage'.$covoiturage->getId(), $token)) {
            return $this->json(['ok' => false, 'error' => 'Token CSRF invalide'], 403);
        }
        
        // Since there's no status field, we delete the covoiturage to "reject" it
        $entityManager->remove($covoiturage);
        $entityManager->flush();
        
        return $this->json([
            'ok' => true,
            'message' => 'Covoiturage rejeté et supprimé'
        ]);
    }

    #[Route('/{id}/participants', name: 'admin_covoiturage_participants')]
    public function participants(
        Covoiturage $covoiturage,
        ParticipantRepository $participantRepository
    ): Response
    {
        $participants = $participantRepository->findBy(
            ['covoiturage' => $covoiturage],
            ['dateCreation' => 'DESC']
        );

        $stats = [
            'en_attente' => 0,
            'confirme' => 0,
            'refuse' => 0,
            'annule' => 0,
        ];

        foreach ($participants as $participant) {
            $statut = $participant->getStatut();
            if (isset($stats[$statut])) {
                $stats[$statut]++;
            }
        }

        return $this->render('admin/covoiturage_participants.html.twig', [
            'covoiturage' => $covoiturage,
            'participants' => $participants,
            'stats' => $stats,
        ]);
    }

    #[Route('/participant/{id}/status', name: 'admin_participant_change_status', methods: ['POST'])]
    public function changeParticipantStatus(
        int $id,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $participant = $participantRepository->find($id);

        if (!$participant) {
            $this->addFlash('error', 'Participant introuvable.');
            return $this->redirectToRoute('admin_covoiturages');
        }

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('status'.$participant->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_covoiturage_participants', ['id' => $participant->getCovoiturage()->getId()]);
        }

        $newStatus = $request->request->get('status');
        $validStatuses = ['en_attente', 'confirme', 'refuse', 'annule'];

        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_covoiturage_participants', ['id' => $participant->getCovoiturage()->getId()]);
        }

        $oldStatus = $participant->getStatut();
        $participant->setStatut($newStatus);
        $entityManager->flush();

        $statusLabels = [
            'en_attente' => 'En attente',
            'confirme' => 'Confirmé',
            'refuse' => 'Refusé',
            'annule' => 'Annulé'
        ];

        $this->addFlash('success', sprintf(
            'Statut du participant changé de "%s" à "%s".',
            $statusLabels[$oldStatus] ?? $oldStatus,
            $statusLabels[$newStatus] ?? $newStatus
        ));

        return $this->redirectToRoute('admin_covoiturage_participants', ['id' => $participant->getCovoiturage()->getId()]);
    }

    #[Route('/participant/{id}/delete', name: 'admin_participant_delete', methods: ['POST'])]
    public function deleteParticipant(
        int $id,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $participant = $participantRepository->find($id);

        if (!$participant) {
            $this->addFlash('error', 'Participant introuvable.');
            return $this->redirectToRoute('admin_covoiturages');
        }

        $covoiturageId = $participant->getCovoiturage()->getId();

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$participant->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_covoiturage_participants', ['id' => $covoiturageId]);
        }

        $entityManager->remove($participant);
        $entityManager->flush();

        $this->addFlash('success', 'Participant supprimé avec succès.');

        return $this->redirectToRoute('admin_covoiturage_participants', ['id' => $covoiturageId]);
    }
    
    /**
     * Generate PDF for a covoiturage (Admin)
     */
    #[Route('/{id}/pdf', name: 'admin_covoiturage_pdf')]
    public function generatePdf(
        Covoiturage $covoiturage,
        ParticipantRepository $participantRepository,
        PdfGenerator $pdfGenerator
    ): Response
    {
        $participants = $participantRepository->findBy(
            ['covoiturage' => $covoiturage],
            ['dateCreation' => 'ASC']
        );
        
        $pdfContent = $pdfGenerator->generatePdf('pdf/covoiturage.html.twig', [
            'covoiturage' => $covoiturage,
            'participants' => $participants,
        ]);
        
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="covoiturage-%06d.pdf"', $covoiturage->getId()),
            ]
        );
    }
}
