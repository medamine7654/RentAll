<?php

namespace App\Twig;

use App\Repository\FavoriteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class FavoriteExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private FavoriteRepository $favoriteRepository,
        private Security $security
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        $favoriteCount = 0;

        if ($user) {
            $favoriteCount = $this->favoriteRepository->countByUser($user);
        }

        return [
            'favoriteCount' => $favoriteCount,
        ];
    }
}
