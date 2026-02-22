<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre email.',
                    ]),
                    new Email([
                        'message' => 'Veuillez saisir un email valide.',
                    ]),
                ],
            ])
            ->add('captcha', IntegerType::class, [
                'mapped' => false,
                'label' => $options['captcha_label'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez repondre au captcha.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'captcha_label' => 'Captcha',
        ]);

        $resolver->setAllowedTypes('captcha_label', 'string');
    }
}
