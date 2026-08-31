<?php

namespace App\Controller;

use App\Form\AccountType;
use App\Form\UserPreferencesType;
use App\Service\CriteriaManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 1. Form for account info
        $infoForm = $this->createForm(AccountType::class, $user, [
            'user' => $user,
        ]);
        $infoForm->handleRequest($request);

        if ($infoForm->isSubmitted() && $infoForm->isValid()) {
            // Update password if provided
            $plainPassword = $infoForm->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'app.success_account_updated');

            return $this->redirectToRoute('app_account');
        }

        // 2. Form for preferences
        $prefForm = $this->createForm(UserPreferencesType::class, $user, [
            'user' => $user,
        ]);
        $prefForm->handleRequest($request);

        if ($prefForm->isSubmitted() && $prefForm->isValid()) {
            // Update preferences
            $criteria = CriteriaManager::getRatedCriteria();
            foreach ($criteria as $key => $config) {
                $weight = $prefForm->get('pref_' . $key)->getData();
                $user->setPreferenceWeight($key, $weight);
            }

            $entityManager->flush();

            $this->addFlash('success', 'app.success_preferences_updated');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/index.html.twig', [
            'infoForm' => $infoForm->createView(),
            'prefForm' => $prefForm->createView(),
        ]);
    }
}
