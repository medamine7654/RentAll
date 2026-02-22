<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    #[Route('/register-legacy', name: 'app_register_legacy')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
       /** @var string $plainPassword */
        $plainPassword = $form->get('plainPassword')->getData();

       // Hash du mot de passe
        $user->setPassword(
        $userPasswordHasher->hashPassword($user, $plainPassword)
        );

        // Valeurs par défaut
       $user->setRoles(['ROLE_USER']);
       $user->setIsVerified(false);

       $entityManager->persist($user);
       $entityManager->flush();

      return $this->redirectToRoute('app_login');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Inscription refusée: vérifiez les champs du formulaire.');
        }

        return $this->render('front/registration/register.html.twig', [
          'registrationForm' => $form->createView(),
       ], $form->isSubmitted() && !$form->isValid() ? new Response('', 422) : null);

    }
}
