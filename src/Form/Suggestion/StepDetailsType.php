<?php

namespace App\Form\Suggestion;

use App\Entity\Enum\SuggestionEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class StepDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['entity_type'] === SuggestionEntityType::BOOK->value) {
            $this->addBookFields($builder);
        } else {
            $builder->add('name', TextType::class, [
                'required'    => true,
                'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')],
            ]);
        }
    }

    private function addBookFields(FormBuilderInterface $builder): void
    {
        $maxYear = (int) date('Y') + 2;

        $builder
            ->add('title', TextType::class, [
                'required'    => true,
                'constraints' => [
                    new NotBlank(message: 'Ce champ est obligatoire.'),
                    new Length(max: 255),
                ],
            ])
            ->add('subtitle', TextType::class, ['required' => false])
            ->add('author', TextType::class, ['required' => false])
            ->add('illustrator', TextType::class, ['required' => false])
            ->add('traductor', TextType::class, ['required' => false])
            ->add('editor', TextType::class, ['required' => false])
            ->add('collection', TextType::class, ['required' => false])
            ->add('isbn', TextType::class, ['required' => false])
            ->add('publicationFr', IntegerType::class, [
                'required'    => false,
                'constraints' => [
                    new Range(
                        min: 1800,
                        max: $maxYear,
                        notInRangeMessage: "L'année doit être comprise entre {{ min }} et {{ max }}.",
                    ),
                ],
            ])
            ->add('originalEdition', IntegerType::class, [
                'required'    => false,
                'constraints' => [
                    new Range(
                        min: 1800,
                        max: $maxYear,
                        notInRangeMessage: "L'année doit être comprise entre {{ min }} et {{ max }}.",
                    ),
                ],
            ])
            ->add('paragraphs', IntegerType::class, [
                'required'    => false,
                'constraints' => [
                    new Range(
                        max: 800,
                        maxMessage: 'Le nombre de paragraphes ne peut pas dépasser {{ limit }}.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => null,
            'csrf_protection' => false,
            'entity_type'     => null,
        ]);
        $resolver->setAllowedTypes('entity_type', ['null', 'string']);
    }
}
