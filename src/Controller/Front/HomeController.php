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
            'guests'   => $request->query->get('guests'),
        ];

        // isActive replaces the old 'disponible' field (no such column in DB)
        $queryBuilder = $logementRepository->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->setParameter('active', true);

        // HOST sees only their own logements; regular USER sees others'
        if ($user && $this->isGranted('ROLE_HOST') && !$this->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere('l.proprietaire = :proprietaire')
                ->setParameter('proprietaire', $user);
        } elseif ($user && !$this->isGranted('ROLE_HOST') && !$this->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere('l.proprietaire != :proprietaire')
                ->setParameter('proprietaire', $user);
        }

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
            // maxGuests replaces the old 'capacite' field (no such column in DB)
            $queryBuilder->andWhere('l.maxGuests >= :guests')
                ->setParameter('guests', $filters['guests']);
        }

        // 'type' column doesn't exist in DB — category filter is skipped for now
        // if ($filters['category']) { ... }

        $logements = $queryBuilder->getQuery()->getResult();

        // Avoid N+1 on avis: aggregate in one query
        $ratings = $avisRepository->getRatingsSummaryForLogements($logements);
        foreach ($logements as $logement) {
            $id = $logement->getId();
            if (!isset($ratings[$id])) {
                $ratings[$id] = ['average' => null, 'total' => 0];
            }
        }

        return $this->render('front/home/index.html.twig', [
            'logements' => $logements,
            'ratings'   => $ratings,
            'filters'   => $filters,
            'isLoading' => false,
        ]);
    }

    #[Route('/carte', name: 'app_map_view')]
    public function mapView(LogementRepository $logementRepository, CovoiturageRepository $covoiturageRepository): Response
    {
        // isActive replaces the old 'disponible' field
        $logements = $logementRepository->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.adresse IS NOT NULL')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $covoiturages = $covoiturageRepository->createQueryBuilder('c')
            ->where('c.dateDepart > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('c.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('front/home/map.html.twig', [
            'logements'    => $logements,
            'covoiturages' => $covoiturages,
        ]);
    }
}
