<?php

namespace App\Form;

use App\Service\CriteriaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'existing_scores' => [],
            'translation_domain' => 'messages',
        ]);
    }
}
