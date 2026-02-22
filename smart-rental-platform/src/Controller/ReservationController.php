<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservations')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservations')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $statusFilter = $request->query->get('status', 'all');

        // TODO: Replace with actual Doctrine repository query
        // Get user's reservations filtered by status
        $reservations = []; // $this->getDoctrine()->getRepository(Reservation::class)->findByUser($this->getUser(), $statusFilter);
        $logements = []; // Get logements indexed by ID
        $tools = []; // Get tools indexed by ID
        $hosts = []; // Get hosts indexed by ID

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'logements' => $logements,
            'tools' => $tools,
            'hosts' => $hosts,
            'status' => $statusFilter,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel')]
    public function cancel(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // TODO: Implement reservation cancellation logic
        // Verify user owns this reservation
        // Update reservation status to 'cancelled'
        
        $this->addFlash('success', 'Reservation cancelled successfully');
        return $this->redirectToRoute('app_reservations');
    }
}
