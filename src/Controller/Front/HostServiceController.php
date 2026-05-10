<?php

namespace App\Controller\Front;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Repository\CategoryRepository;
use App\Service\PriceSuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/host/services')]
#[IsGranted('ROLE_HOST')]
class HostServiceController extends AbstractController
{
    #[Route('/', name: 'host_services')]
    public function index(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findByHost($this->getUser());

        return $this->render('front/host/services/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/new', name: 'host_service_new')]
    public function new(Request $request, EntityManagerInterface $em, PriceSuggestionService $priceSuggestionService, CategoryRepository $categoryRepository): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->setHost($this->getUser());
            
            if ($this->isGranted('ROLE_ADMIN')) {
                $service->setIsActive(true);
                $this->addFlash('success', 'Service created and published successfully!');
            } else {
                $service->setIsActive(false);
                $this->addFlash('success', 'Service created successfully! Waiting for admin approval.');
            }

            $em->persist($service);
            $em->flush();

            return $this->redirectToRoute('host_services');
        }

        $priceSuggestions = [];
        $serviceCategories = $categoryRepository->findByType('service');
        foreach ($serviceCategories as $category) {
            $suggestion = $priceSuggestionService->getServicePriceSuggestion($category);
            if ($suggestion) {
                $priceSuggestions[$category->getId()] = $suggestion;
            }
        }
        
        return $this->render('front/host/services/new.html.twig', [
            'form' => $form->createView(),
            'priceSuggestions' => $priceSuggestions,
        ]);
    }

    #[Route('/{id}/edit', name: 'host_service_edit')]
    public function edit(Service $service, Request $request, EntityManagerInterface $em): Response
    {
        if ($service->getHost() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Service updated successfully!');
            return $this->redirectToRoute('host_services');
        }

        return $this->render('front/host/services/edit.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'host_service_delete', methods: ['POST'])]
    public function delete(Service $service, Request $request, EntityManagerInterface $em): Response
    {
        if ($service->getHost() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            $em->remove($service);
            $em->flush();

            $this->addFlash('success', 'Service deleted successfully!');
        }

        return $this->redirectToRoute('host_services');
    }
}
