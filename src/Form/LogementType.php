<?php

namespace App\Form;

use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du logement',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'placeholder' => 'Ex: Appartement cosy au centre-ville',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length(['min' => 5, 'max' => 255]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre logement...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire']),
                    new Assert\Length(['min' => 20]),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'placeholder' => 'Adresse complète',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'adresse est obligatoire']),
                ],
            ])
            ->add('prixParNuit', TextType::class, [
                'label' => 'Prix par nuit (€)',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'placeholder' => '150.00',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1,2})?$/',
                        'message' => 'Le prix doit être un nombre valide (ex: 150.00)'
                    ]),
                ],
            ])
            ->add('nombreChambres', IntegerType::class, [
                'label' => 'Nombre de chambres',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'min' => 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de chambres est obligatoire']),
                    new Assert\Positive(),
                ],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité (nombre de personnes)',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'min' => 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La capacité est obligatoire']),
                    new Assert\Positive(),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de logement',
                'choices' => [
                    'Appartement' => 'Appartement',
                    'Maison' => 'Maison',
                    'Studio' => 'Studio',
                    'Villa' => 'Villa',
                    'Loft' => 'Loft',
                    'Chalet' => 'Chalet',
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                ],
                'placeholder' => 'Sélectionnez un type',
            ])
            ->add('image', TextType::class, [
                'label' => 'URL de l\'image',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                    'placeholder' => 'https://example.com/image.jpg',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logement::class,
        ]);
    }
}
