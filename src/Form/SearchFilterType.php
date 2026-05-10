<?php

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type = $options['type'];

        $builder
            ->add('query', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Search by name or description...',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'All Categories',
                'label' => false,
                'query_builder' => function (CategoryRepository $repository) use ($type) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.type = :type')
                        ->setParameter('type', $type)
                        ->orderBy('c.name', 'ASC');
                },
                'attr' => ['class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent']
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Location',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]
            ])
            ->add('minPrice', NumberType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Min Price',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'min' => 0,
                    'step' => '0.01'
                ]
            ])
            ->add('maxPrice', NumberType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Max Price',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'min' => 0,
                    'step' => '0.01'
                ]
            ])
            ->add('sortBy', ChoiceType::class, [
                'required' => false,
                'label' => false,
                'placeholder' => 'Sort by',
                'choices' => [
                    'Newest First' => 'date_desc',
                    'Oldest First' => 'date_asc',
                    'Price: Low to High' => 'price_asc',
                    'Price: High to Low' => 'price_desc',
                    'Name: A to Z' => 'name_asc',
                    'Name: Z to A' => 'name_desc',
                ],
                'attr' => ['class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => 'service',
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
