<?php

namespace App\Form;

use App\Entity\SportEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class SportEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du match',
                'attr'  => ['placeholder' => 'ex: T1 vs Gen.G — LCK Finals'],
            ])
            ->add('sport', TextType::class, [
                'label' => 'Sport',
                'attr'  => ['placeholder' => 'ex: League of Legends'],
            ])
            ->add('tournament', TextType::class, [
                'label' => 'Tournoi',
                'attr'  => ['placeholder' => 'ex: MSI 2026'],
            ])
            ->add('teamA', TextType::class, [
                'label' => 'Équipe A',
                'attr'  => ['placeholder' => 'ex: T1'],
            ])
            ->add('teamB', TextType::class, [
                'label' => 'Équipe B',
                'attr'  => ['placeholder' => 'ex: Gen.G'],
            ])
            ->add('startsAt', DateTimeType::class, [
                'label'  => 'Date du match',
                'widget' => 'single_text',
            ])
            // Champs non mappés : le fichier n'est pas stocké dans l'entité directement
            // Le controller lit ces champs, déplace le fichier et enregistre l'URL dans l'entité
            ->add('logoAFile', FileType::class, [
                'label'    => 'Logo équipe A (optionnel)',
                'required' => false,
                'mapped'   => false,
                'constraints' => [
                    new Image([
                        'maxSize'   => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepté : JPG, PNG, WebP.',
                        'maxSizeMessage'   => 'L\'image ne doit pas dépasser 2 Mo.',
                    ]),
                ],
            ])
            ->add('logoBFile', FileType::class, [
                'label'    => 'Logo équipe B (optionnel)',
                'required' => false,
                'mapped'   => false,
                'constraints' => [
                    new Image([
                        'maxSize'   => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepté : JPG, PNG, WebP.',
                        'maxSizeMessage'   => 'L\'image ne doit pas dépasser 2 Mo.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SportEvent::class]);
    }
}
