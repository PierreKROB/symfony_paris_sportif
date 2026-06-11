<?php

namespace App\Form;

use App\Entity\SportEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SportEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom du match'])
            ->add('tournament', TextType::class, ['label' => 'Tournoi', 'attr' => ['placeholder' => 'ex: MSI 2025']])
            ->add('teamA', TextType::class, ['label' => 'Équipe A', 'attr' => ['placeholder' => 'ex: T1']])
            ->add('teamB', TextType::class, ['label' => 'Équipe B', 'attr' => ['placeholder' => 'ex: Gen.G']])
            ->add('startsAt', DateTimeType::class, [
                'label'  => 'Date du match',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SportEvent::class]);
    }
}
