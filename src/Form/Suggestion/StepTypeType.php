<?php

namespace App\Form\Suggestion;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StepTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modeChoices = array_combine(
            array_map(fn(SuggestionMode $c) => $c->value, SuggestionMode::cases()),
            array_map(fn(SuggestionMode $c) => $c->value, SuggestionMode::cases()),
        );

        $entityChoices = array_combine(
            array_map(fn(SuggestionEntityType $c) => $c->value, SuggestionEntityType::cases()),
            array_map(fn(SuggestionEntityType $c) => $c->value, SuggestionEntityType::cases()),
        );

        $builder
            ->add('mode', ChoiceType::class, [
                'choices'     => $modeChoices,
                'required'    => true,
                'constraints' => [new NotBlank(message: 'Veuillez sélectionner un type de proposition.')],
            ])
            ->add('entityType', ChoiceType::class, [
                'choices'     => $entityChoices,
                'required'    => true,
                'constraints' => [new NotBlank(message: 'Veuillez sélectionner un type de fiche.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => null,
            'csrf_protection' => false,
        ]);
    }
}
