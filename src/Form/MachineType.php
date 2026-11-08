<?php

namespace App\Form;

use App\Entity\Machine;
use App\Entity\Muscle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MachineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClasses = 'w-full bg-[#1a1a1a] border-0 border-b-2 border-white/20 px-4 py-3 font-headline text-sm text-white focus:outline-none focus:border-[#ff7351] transition-colors mb-4';

        $builder
            ->add('name', TextType::class, [
                'label' => 'NAZWA SPRZĘTU / ĆWICZENIA',
                'attr' => ['class' => $inputClasses]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'KATEGORIA GŁÓWNA (DZIEŃ)',
                'choices' => [
                    'Klatka piersiowa' => 'Klatka piersiowa',
                    'Plecy' => 'Plecy',
                    'Nogi' => 'Nogi',
                    'Barki' => 'Barki',
                    'Biceps' => 'Biceps',
                    'Triceps' => 'Triceps',
                    'Brzuch' => 'Brzuch'
                ],
                'attr' => ['class' => $inputClasses]
            ])
            ->add('mechanicType', ChoiceType::class, [
                'label' => 'TYP MECHANIKI',
                'choices' => [
                    'Wielostawowe (Compound)' => 'compound',
                    'Izolowane (Isolation)' => 'isolation',
                ],
                'attr' => ['class' => $inputClasses]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'OPIS ĆWICZENIA',
                'attr' => ['class' => $inputClasses, 'rows' => 4]
            ])
            ->add('tips', TextareaType::class, [
                'label' => 'WSKAZÓWKI (TIPS)',
                'attr' => ['class' => $inputClasses, 'rows' => 3],
                'required' => false
            ])
            ->add('imageStart', FileType::class, [
                'label' => 'GRAFIKA FAZY 1',
                'mapped' => false,
                'required' => false, // Pozwala to na ewentualne ominięcie przesyłania przy edycji
                'attr' => ['class' => $inputClasses],
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Wgraj prawidłowy obraz (JPG, PNG lub WEBP)',
                    ])
                ],
            ])
            ->add('imageEnd', FileType::class, [
                'label' => 'GRAFIKA FAZY 2',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => $inputClasses],
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Wgraj prawidłowy obraz (JPG, PNG lub WEBP)',
                    ])
                ],
            ])
            ->add('primaryMuscles', EntityType::class, [
                'class' => Muscle::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'GŁÓWNE PARTIE MIĘŚNIOWE',
                'attr' => ['class' => $inputClasses . ' h-32']
            ])
            ->add('secondaryMuscles', EntityType::class, [
                'class' => Muscle::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'POBOCZNE PARTIE MIĘŚNIOWE',
                'attr' => ['class' => $inputClasses . ' h-32']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Machine::class,
        ]);
    }
}