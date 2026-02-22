<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tools')]
class ToolController extends AbstractController
{
    #[Route('/', name: 'app_tools')]
    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->query->get('category'),
            'available' => $request->query->get('available') === '1',
        ];

        // TODO: Replace with actual Doctrine repository query
        $tools = []; // $this->getDoctrine()->getRepository(Tool::class)->findByFilters($filters);
        $hosts = []; // Get hosts indexed by ID

        return $this->render('front/tools/index.html.twig', [
            'tools' => $tools,
            'hosts' => $hosts,
            'filters' => $filters,
        ]);
    }

    #[Route('/{id}', name: 'app_tool_show')]
    public function show(int $id): Response
    {
        // TODO: Implement tool detail page
        return new Response('Tool detail page - ID: ' . $id);
    }

    #[Route('/create', name: 'app_tool_create')]
    public function create(): Response
    {
        // TODO: Implement tool creation form
        $this->denyAccessUnlessGranted('ROLE_HOST');
        return new Response('Tool creation form');
    }
}


