<?php

namespace App\Form;

use App\Entity\BusinessIdea;
use App\Service\CriteriaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessIdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'idea.title.label',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'idea.description.label',
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'idea.status.label',
                'choices' => [
                    'status.draft' => BusinessIdea::STATUS_DRAFT,
                    'status.adopted' => BusinessIdea::STATUS_ADOPTED,
                    'status.abandoned' => BusinessIdea::STATUS_ABANDONED,
                ],
                'required' => true,
            ])
            ->add('revenueModel', ChoiceType::class, [
                'label' => 'idea.revenue_model.label',
                'choices' => [
                    'revenue.single' => BusinessIdea::REVENUE_SINGLE,
                    'revenue.recurring' => BusinessIdea::REVENUE_RECURRING,
                    'revenue.both' => BusinessIdea::REVENUE_BOTH,
                ],
                'required' => true,
            ])
            ->add('targetAudience', ChoiceType::class, [
                'label' => 'idea.target_audience.label',
                'choices' => [
                    'audience.b2b' => BusinessIdea::AUDIENCE_B2B,
                    'audience.b2c' => BusinessIdea::AUDIENCE_B2C,
                    'audience.both' => BusinessIdea::AUDIENCE_BOTH,
                ],
                'required' => true,
            ]);

        // Add rated criteria fields (unmapped) for self-evaluation
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
                'expanded' => true, // renders as radio buttons (easier to style as stars)
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
            'data_class' => BusinessIdea::class,
            'existing_scores' => [],
            'translation_domain' => 'messages',
        ]);
    }
}
