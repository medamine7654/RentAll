<?php

namespace App\Controller\Front;

use App\Entity\Logement;
use App\Repository\LogementRepository;
use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/logement')]
class LogementController extends AbstractController
{
    #[Route('/{id}', name: 'app_logement_show', methods: ['GET'])]
    public function show(int $id, LogementRepository $logementRepository, AvisRepository $avisRepository): Response
    {
        $logement = $logementRepository->find($id);

        if (!$logement) {
            $this->addFlash('error', 'Ce logement n\'existe pas.');
            return $this->redirectToRoute('app_home');
        }

        // Récupérer les avis pour ce logement
        $avis = $avisRepository->findByLogement($logement);
        $averageRating = $avisRepository->getAverageRatingForLogement($logement);

        return $this->render('front/logement/show.html.twig', [
            'logement' => $logement,
            'avis' => $avis,
            'averageRating' => $averageRating,
            'totalAvis' => count($avis),
        ]);
    }
}
