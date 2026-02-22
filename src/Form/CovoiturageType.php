<?php

namespace App\Form;

use App\Entity\Covoiturage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CovoiturageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Check if we should validate future date
        $validateFutureDate = $options['validate_future_date'];
        
        $dateConstraints = [
            new NotBlank([
                'message' => 'Veuillez saisir une date de départ.',
            ]),
        ];
        
        // Only require future date if validation is enabled
        if ($validateFutureDate) {
            $dateConstraints[] = new GreaterThan([
                'value' => 'now',
                'message' => 'La date de départ doit être dans le futur.',
            ]);
        }
        
        $builder
            ->add('depart', TextType::class, [
                'label' => 'Depart',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un départ.',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le départ doit contenir au moins {{ limit }} caractères.',
                        'max' => 120,
                        'maxMessage' => 'Le départ ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une destination.',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'La destination doit contenir au moins {{ limit }} caractères.',
                        'max' => 120,
                        'maxMessage' => 'La destination ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date de depart',
                'widget' => 'single_text',
                'input' => 'datetime',
                'constraints' => $dateConstraints,
            ])
            ->add('places', IntegerType::class, [
                'label' => 'Nombre de places',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir le nombre de places.',
                    ]),
                    new Range([
                        'min' => 1,
                        'max' => 50,
                        'notInRangeMessage' => 'Le nombre de places doit être entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Covoiturage::class,
            'validate_future_date' => true, // By default, validate future date
        ]);
    }
}
