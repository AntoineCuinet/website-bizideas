<?php

namespace App\Form;

use App\Entity\User;
use App\Service\CriteriaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'app.email_label',
                'required' => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'app.password_label',
                    'help' => 'app.password_help',
                ],
                'second_options' => [
                    'label' => 'app.password_confirm_label',
                ],
                'invalid_message' => 'security.password.mismatch',
                'constraints' => [
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ]);

        // Add preference field for each criterion
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
