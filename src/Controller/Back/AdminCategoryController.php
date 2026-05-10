<?php

namespace App\Controller\Back;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class AdminCategoryController extends AbstractController
{
    #[Route('', name: 'admin_category_index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $stats = $categoryRepository->getCategoryStats();
        
        return $this->render('admin/categories/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $category = new Category();
            $category->setName($request->request->get('name'));
            $category->setDescription($request->request->get('description'));
            $category->setType($request->request->get('type'));
            $category->setIcon($request->request->get('icon'));

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/categories/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $category->setName($request->request->get('name'));
            $category->setDescription($request->request->get('description'));
            $category->setType($request->request->get('type'));
            $category->setIcon($request->request->get('icon'));

            $entityManager->flush();

            $this->addFlash('success', 'Category updated successfully!');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/categories/edit.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($category->getServices()->count() > 0 || $category->getTools()->count() > 0) {
            $this->addFlash('error', 'Cannot delete category that has services or tools assigned to it.');
            return $this->redirectToRoute('admin_category_index');
        }

        $entityManager->remove($category);
        $entityManager->flush();

        $this->addFlash('success', 'Category deleted successfully!');
        return $this->redirectToRoute('admin_category_index');
    }
}
