<?php

namespace App\Form;

use App\Service\CriteriaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $criteria = CriteriaManager::getRatedCriteria();
        $existingScores = $options['existing_scores'] ?? [];

        foreach ($criteria as $key => $config) {
            $builder->add('rating_' . $key, ChoiceType::class, [
                'label' => $config['label'],
                'help' => $config['description'],
                'choices' => [
                    '5' => 5,
                    '4' => 4,
                    '3' => 3,
                    '2' => 2,
                    '1' => 1,
                ],
                'expanded' => true, // radio buttons
                'multiple' => false,
                'mapped' => false,
                'required' => true,
                'data' => $existingScores[$key] ?? null,
                'attr' => [
                    'class' => 'rating-stars-input',
                    'data-criterion' => $key,
                ],
            ]);
        }

        $builder->add('comment', TextareaType::class, [
            'label' => 'app.comment_label',
            'required' => false,
            'data' => $options['existing_comment'] ?? null,
            'attr' => [
                'placeholder' => 'app.comment_placeholder',
                'rows' => 4,
                'class' => 'form-control form-textarea',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'existing_scores' => [],
            'existing_comment' => null,
            'translation_domain' => 'messages',
        ]);
    }
}
