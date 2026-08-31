<?php

namespace App\Form;

use App\Entity\User;
use App\Service\CriteriaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $criteria = CriteriaManager::getRatedCriteria();
        $user = $options['user'];

        foreach ($criteria as $key => $config) {
            $builder->add('pref_' . $key, ChoiceType::class, [
                'label' => $config['label'],
                'choices' => [
                    'weight.low' => CriteriaManager::WEIGHT_LOW,
                    'weight.medium' => CriteriaManager::WEIGHT_MEDIUM,
                    'weight.high' => CriteriaManager::WEIGHT_HIGH,
                ],
                'required' => true,
                'mapped' => false,
                'data' => $user ? $user->getPreferenceWeight($key) : CriteriaManager::WEIGHT_MEDIUM,
                'attr' => [
                    'class' => 'preference-select',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user' => null,
            'translation_domain' => 'messages',
        ]);
    }
}
