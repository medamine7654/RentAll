<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HostController extends AbstractController
{
    #[Route('/host', name: 'host_dashboard')]
public function index(): Response
{
    return $this->render('front/host/index.html.twig');
}

}


