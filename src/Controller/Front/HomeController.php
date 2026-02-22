<?php

namespace App\Controller\Front;

use App\Repository\LogementRepository;
use App\Repository\AvisRepository;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, LogementRepository $logementRepository, AvisRepository $avisRepository): Response
    {
        $user = $this->getUser();
        
        $filters = [
            'category' => $request->query->get('category'),
            'location' => $request->query->get('location'),
            'minPrice' => $request->query->get('minPrice'),
            'maxPrice' => $request->query->get('maxPrice'),
            'guests' => $request->query->get('guests'),
        ];

        // Fetch real logements from database
        $queryBuilder = $logementRepository->createQueryBuilder('l')
            ->where('l.disponible = :disponible')
            ->setParameter('disponible', true);

        // Si l'utilisateur est un HOST (et pas ADMIN), ne montrer que SES logements
        if ($user && $this->isGranted('ROLE_HOST') && !$this->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere('l.proprietaire = :proprietaire')
                ->setParameter('proprietaire', $user);
        }
        // Si l'utilisateur est un USER simple, ne pas montrer ses propres logements (s'il en a)
        elseif ($user && !$this->isGranted('ROLE_HOST') && !$this->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere('l.proprietaire != :proprietaire')
                ->setParameter('proprietaire', $user);
        }

        // Apply filters
        if ($filters['location']) {
            $queryBuilder->andWhere('l.adresse LIKE :location OR l.titre LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if ($filters['minPrice']) {
            $queryBuilder->andWhere('l.prixParNuit >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if ($filters['maxPrice']) {
            $queryBuilder->andWhere('l.prixParNuit <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if ($filters['guests']) {
            $queryBuilder->andWhere('l.capacite >= :guests')
                ->setParameter('guests', $filters['guests']);
        }

        if ($filters['category']) {
            $queryBuilder->andWhere('l.type = :type')
                ->setParameter('type', $filters['category']);
        }

        $logements = $queryBuilder->getQuery()->getResult();

        // Get ratings for each logement
        $ratings = [];
        foreach ($logements as $logement) {
            $avgRating = $avisRepository->getAverageRatingForLogement($logement);
            $totalAvis = count($avisRepository->findByLogement($logement));
            $ratings[$logement->getId()] = [
                'average' => $avgRating,
                'total' => $totalAvis,
            ];
        }

        return $this->render('front/home/index.html.twig', [
            'logements' => $logements,
            'ratings' => $ratings,
            'filters' => $filters,
            'isLoading' => false,
        ]);
    }
    
    #[Route('/carte', name: 'app_map_view')]
    public function mapView(LogementRepository $logementRepository, CovoiturageRepository $covoiturageRepository): Response
    {
        // Get all available logements with addresses
        $logements = $logementRepository->createQueryBuilder('l')
            ->where('l.disponible = :disponible')
            ->andWhere('l.adresse IS NOT NULL')
            ->setParameter('disponible', true)
            ->getQuery()
            ->getResult();
        
        // Get all future covoiturage trips
        $covoiturages = $covoiturageRepository->createQueryBuilder('c')
            ->where('c.dateDepart > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('c.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('front/home/map.html.twig', [
            'logements' => $logements,
            'covoiturages' => $covoiturages,
        ]);
    }
}


