<?php

namespace App\Controller;

use App\Entity\BusinessIdea;
use App\Entity\Rating;
use App\Form\BusinessIdeaType;
use App\Form\RatingType;
use App\Repository\BusinessIdeaRepository;
use App\Repository\RatingRepository;
use App\Service\CriteriaManager;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/idea')]
#[IsGranted('ROLE_USER')]
class BusinessIdeaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RatingRepository $ratingRepository,
        private NotificationService $notificationService
    ) {
    }

    #[Route('/new', name: 'app_idea_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $idea = new BusinessIdea();
        $idea->setCreator($user);

        $form = $this->createForm(BusinessIdeaType::class, $idea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save business idea
            $this->entityManager->persist($idea);

            // Save self-evaluation rating
            $rating = new Rating();
            $rating->setBusinessIdea($idea);
            $rating->setUser($user);

            $criteria = CriteriaManager::getRatedCriteria();
            foreach ($criteria as $key => $config) {
                $score = (int) $form->get('rating_' . $key)->getData();
                $rating->setScoreFor($key, $score);
            }

            $this->entityManager->persist($rating);
            $this->entityManager->flush();

            // Send notification emails
            $this->notificationService->notifyNewIdea($idea, $user);

            $this->addFlash('success', 'app.success_idea_created');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('idea/new.html.twig', [
            'form' => $form->createView(),
            'idea' => $idea,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_idea_edit')]
    public function edit(Request $request, BusinessIdea $idea): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        if ($idea->getCreator()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres idées.');
        }

        $rating = $this->ratingRepository->findOneBy([
            'businessIdea' => $idea,
            'user' => $user,
        ]);

        $existingScores = $rating ? $rating->getScores() : [];

        $form = $this->createForm(BusinessIdeaType::class, $idea, [
            'existing_scores' => $existingScores,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$rating) {
                $rating = new Rating();
                $rating->setBusinessIdea($idea);
                $rating->setUser($user);
                $this->entityManager->persist($rating);
            }

            $criteria = CriteriaManager::getRatedCriteria();
            foreach ($criteria as $key => $config) {
                $score = (int) $form->get('rating_' . $key)->getData();
                $rating->setScoreFor($key, $score);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'app.success_idea_updated');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('idea/edit.html.twig', [
            'form' => $form->createView(),
            'idea' => $idea,
        ]);
    }

    #[Route('/{id}/rate', name: 'app_idea_rate')]
    public function rate(Request $request, BusinessIdea $idea): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        // Allow user to rate another person's idea or modify their existing rating
        $rating = $this->ratingRepository->findOneBy([
            'businessIdea' => $idea,
            'user' => $user,
        ]);

        $existingScores = $rating ? $rating->getScores() : [];
        $existingComment = $rating ? $rating->getComment() : null;

        $form = $this->createForm(RatingType::class, null, [
            'existing_scores' => $existingScores,
            'existing_comment' => $existingComment,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$rating) {
                $rating = new Rating();
                $rating->setBusinessIdea($idea);
                $rating->setUser($user);
                $this->entityManager->persist($rating);
            }

            $criteria = CriteriaManager::getRatedCriteria();
            foreach ($criteria as $key => $config) {
                $score = (int) $form->get('rating_' . $key)->getData();
                $rating->setScoreFor($key, $score);
            }

            $comment = $form->get('comment')->getData();
            $rating->setComment($comment);

            $this->entityManager->flush();

            $this->addFlash('success', 'app.success_rating_saved');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('idea/rate.html.twig', [
            'form' => $form->createView(),
            'idea' => $idea,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_idea_delete', methods: ['POST'])]
    public function delete(Request $request, BusinessIdea $idea): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        if ($idea->getCreator()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres idées.');
        }

        if ($this->isCsrfTokenValid('delete' . $idea->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($idea);
            $this->entityManager->flush();
            $this->addFlash('success', 'app.success_idea_deleted');
        }

        return $this->redirectToRoute('app_home');
    }
}
