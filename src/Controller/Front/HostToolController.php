<?php

namespace App\Controller\Front;

use App\Entity\Tool;
use App\Form\ToolType;
use App\Repository\ToolRepository;
use App\Repository\CategoryRepository;
use App\Service\PriceSuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/host/tools')]
#[IsGranted('ROLE_HOST')]
class HostToolController extends AbstractController
{
    #[Route('/', name: 'host_tools')]
    public function index(ToolRepository $toolRepository): Response
    {
        $tools = $toolRepository->findByHost($this->getUser());

        return $this->render('front/host/tools/index.html.twig', [
            'tools' => $tools,
        ]);
    }

    #[Route('/new', name: 'host_tool_new')]
    public function new(Request $request, EntityManagerInterface $em, PriceSuggestionService $priceSuggestionService, CategoryRepository $categoryRepository): Response
    {
        $tool = new Tool();
        $form = $this->createForm(ToolType::class, $tool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tool->setHost($this->getUser());
            
            if ($this->isGranted('ROLE_ADMIN')) {
                $tool->setIsActive(true);
                $this->addFlash('success', 'Tool created and published successfully!');
            } else {
                $tool->setIsActive(false);
                $this->addFlash('success', 'Tool created successfully! Waiting for admin approval.');
            }

            $em->persist($tool);
            $em->flush();

            return $this->redirectToRoute('host_tools');
        }

        $priceSuggestions = [];
        $toolCategories = $categoryRepository->findByType('tool');
        foreach ($toolCategories as $category) {
            $suggestion = $priceSuggestionService->getToolPriceSuggestion($category);
            if ($suggestion) {
                $priceSuggestions[$category->getId()] = $suggestion;
            }
        }

        return $this->render('front/host/tools/new.html.twig', [
            'form' => $form->createView(),
            'priceSuggestions' => $priceSuggestions,
        ]);
    }

    #[Route('/{id}/edit', name: 'host_tool_edit')]
    public function edit(Tool $tool, Request $request, EntityManagerInterface $em): Response
    {
        if ($tool->getHost() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ToolType::class, $tool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Tool updated successfully!');
            return $this->redirectToRoute('host_tools');
        }

        return $this->render('front/host/tools/edit.html.twig', [
            'tool' => $tool,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'host_tool_delete', methods: ['POST'])]
    public function delete(Tool $tool, Request $request, EntityManagerInterface $em): Response
    {
        if ($tool->getHost() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$tool->getId(), $request->request->get('_token'))) {
            $em->remove($tool);
            $em->flush();

            $this->addFlash('success', 'Tool deleted successfully!');
        }

        return $this->redirectToRoute('host_tools');
    }
}
