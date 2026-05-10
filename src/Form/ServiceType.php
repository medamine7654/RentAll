<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Service;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Service Name',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'required' => true,
                'query_builder' => function (CategoryRepository $repository) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.type = :type')
                        ->setParameter('type', 'service')
                        ->orderBy('c.name', 'ASC');
                },
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Please select a category'),
                ],
            ])
            ->add('basePrice', NumberType::class, [
                'label' => 'Price (€)',
                'constraints' => [
                    new NotBlank(),
                    new Positive(),
                ],
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('durationMinutes', IntegerType::class, [
                'label' => 'Duration (minutes)',
                'constraints' => [
                    new NotBlank(),
                    new Positive(),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Remove image',
                'download_uri' => false,
                'image_uri' => false,
                'label' => 'Service Image',
                'help' => 'Max 5MB. Formats: JPG, PNG, GIF',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
