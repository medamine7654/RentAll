<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/services')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_services')]
    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->query->get('category'),
        ];

        // TODO: Replace with actual Doctrine repository query
        $services = []; // $this->getDoctrine()->getRepository(Service::class)->findByFilters($filters);
        $hosts = []; // Get hosts indexed by ID

        return $this->render('services/index.html.twig', [
            'services' => $services,
            'hosts' => $hosts,
            'filters' => $filters,
        ]);
    }

    #[Route('/{id}/book', name: 'app_service_book')]
    public function book(int $id): Response
    {
        // TODO: Implement service booking form
        $this->denyAccessUnlessGranted('ROLE_USER');
        return new Response('Service booking form - ID: ' . $id);
    }
}
