<?php

namespace App\Controller\Front;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Form\AvisType;
use App\Service\BadWordsFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/avis')]
#[IsGranted('ROLE_USER')]
class AvisController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_avis_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        int $id, 
        EntityManagerInterface $entityManager,
        BadWordsFilter $badWordsFilter
    ): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            $this->addFlash('error', 'Cette réservation n\'existe pas.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Vérifier que l'utilisateur est le locataire
        if ($reservation->getLocataire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Vérifier que la réservation est confirmée
        if ($reservation->getStatut() !== 'confirmee') {
            $this->addFlash('error', 'Vous ne pouvez laisser un avis que pour une réservation confirmée.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        // Vérifier qu'il n'y a pas déjà un avis
        if ($reservation->getAvis()) {
            $this->addFlash('error', 'Vous avez déjà laissé un avis pour cette réservation.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        $avis = new Avis();
        $avis->setReservation($reservation);

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les mots interdits
            $commentaire = $avis->getCommentaire();
            if ($badWordsFilter->hasBadWords($commentaire)) {
                $badWords = $badWordsFilter->getBadWords($commentaire);
                $this->addFlash('error', 'Votre commentaire contient des mots inappropriés: ' . implode(', ', $badWords));
                
                return $this->render('front/avis/new.html.twig', [
                    'form' => $form->createView(),
                    'reservation' => $reservation,
                ], new Response('', 422));
            }

            try {
                $entityManager->persist($avis);
                $entityManager->flush();

                $this->addFlash('success', 'Merci pour votre avis !');
                return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la publication de l\'avis: ' . $e->getMessage());
            }
        }

        return $this->render('front/avis/new.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', 422) : null);
    }

    #[Route('/{id}/edit', name: 'app_avis_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Avis $avis,
        EntityManagerInterface $entityManager,
        BadWordsFilter $badWordsFilter
    ): Response
    {
        // Vérifier que l'utilisateur est le propriétaire de l'avis
        if ($avis->getReservation()->getLocataire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cet avis.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les mots interdits
            $commentaire = $avis->getCommentaire();
            if ($badWordsFilter->hasBadWords($commentaire)) {
                $badWords = $badWordsFilter->getBadWords($commentaire);
                $this->addFlash('error', 'Votre commentaire contient des mots inappropriés: ' . implode(', ', $badWords));
                
                return $this->render('front/avis/edit.html.twig', [
                    'form' => $form->createView(),
                    'avis' => $avis,
                ], new Response('', 422));
            }

            try {
                $entityManager->flush();
                $this->addFlash('success', 'Votre avis a été modifié avec succès !');
                return $this->redirectToRoute('app_reservation_show', ['id' => $avis->getReservation()->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }

        return $this->render('front/avis/edit.html.twig', [
            'form' => $form->createView(),
            'avis' => $avis,
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', 422) : null);
    }

    #[Route('/{id}/delete', name: 'app_avis_delete', methods: ['POST'])]
    public function delete(Request $request, Avis $avis, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est le propriétaire de l'avis
        if ($avis->getReservation()->getLocataire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cet avis.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($this->isCsrfTokenValid('delete'.$avis->getId(), $request->request->get('_token'))) {
            $reservationId = $avis->getReservation()->getId();
            $entityManager->remove($avis);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre avis a été supprimé.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservationId]);
        }

        return $this->redirectToRoute('app_reservation_index');
    }
}
