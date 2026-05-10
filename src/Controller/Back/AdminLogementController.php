<?php

namespace App\Controller\Back;

use App\Entity\Logement;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logements')]
#[IsGranted('ROLE_ADMIN')]
class AdminLogementController extends AbstractController
{
    #[Route('/', name: 'admin_logements')]
    public function index(LogementRepository $logementRepository): Response
    {
        $logements = $logementRepository->findAllForAdmin();

        return $this->render('admin/logements/index.html.twig', [
            'logements' => $logements,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_logement_approve', methods: ['POST'])]
    public function approve(Logement $logement, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve'.$logement->getId(), $request->request->get('_token'))) {
            $logement->setIsActive(true);
            $em->flush();

            $this->addFlash('success', 'Logement approved successfully!');
        }

        return $this->redirectToRoute('admin_logements');
    }

    #[Route('/{id}/hide', name: 'admin_logement_hide', methods: ['POST'])]
    public function hide(Logement $logement, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('hide'.$logement->getId(), $request->request->get('_token'))) {
            $logement->setIsActive(false);
            $em->flush();

            $this->addFlash('success', 'Logement hidden successfully!');
        }

        return $this->redirectToRoute('admin_logements');
    }
}
