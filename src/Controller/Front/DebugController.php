<?php

namespace App\Controller\Front;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/reservations', name: 'debug_reservations')]
    public function debugReservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new Response('Vous devez être connecté');
        }
        
        $reservations = $reservationRepository->findBy(['locataire' => $user]);
        
        $html = '<h1>Debug Réservations pour ' . $user->getEmail() . '</h1>';
        $html .= '<p>Nombre de réservations: ' . count($reservations) . '</p>';
        
        foreach ($reservations as $reservation) {
            $html .= '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">';
            $html .= '<h3>Réservation #' . $reservation->getId() . '</h3>';
            $html .= '<p><strong>Logement:</strong> ' . $reservation->getLogement()->getTitre() . '</p>';
            $html .= '<p><strong>Statut:</strong> ' . $reservation->getStatut() . '</p>';
            $html .= '<p><strong>Date début:</strong> ' . $reservation->getDateDebut()->format('d/m/Y') . '</p>';
            $html .= '<p><strong>Date fin:</strong> ' . $reservation->getDateFin()->format('d/m/Y') . '</p>';
            $html .= '<p><strong>Aujourd\'hui:</strong> ' . (new \DateTime())->format('d/m/Y H:i:s') . '</p>';
            $html .= '<p><strong>Est terminée (isTerminee):</strong> ' . ($reservation->isTerminee() ? 'OUI ✅' : 'NON ❌') . '</p>';
            $html .= '<p><strong>A un avis:</strong> ' . ($reservation->getAvis() ? 'OUI' : 'NON') . '</p>';
            
            $html .= '<p><strong>Conditions pour laisser un avis:</strong></p>';
            $html .= '<ul>';
            $html .= '<li>Statut confirmée: ' . ($reservation->getStatut() === 'confirmee' ? '✅' : '❌ (statut: ' . $reservation->getStatut() . ')') . '</li>';
            $html .= '<li>Séjour terminé: ' . ($reservation->isTerminee() ? '✅' : '❌') . '</li>';
            $html .= '<li>Pas d\'avis: ' . (!$reservation->getAvis() ? '✅' : '❌') . '</li>';
            $html .= '</ul>';
            
            $canLeaveReview = $reservation->getStatut() === 'confirmee' 
                && $reservation->isTerminee() 
                && !$reservation->getAvis();
            
            $html .= '<p><strong>PEUT LAISSER UN AVIS:</strong> ' . ($canLeaveReview ? '✅ OUI' : '❌ NON') . '</p>';
            
            if ($canLeaveReview) {
                $html .= '<p><a href="/avis/new/' . $reservation->getId() . '" style="background: blue; color: white; padding: 10px; text-decoration: none; display: inline-block;">Laisser un avis</a></p>';
            }
            
            $html .= '</div>';
        }
        
        return new Response($html);
    }
}
