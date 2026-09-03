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
use Symfony\Contracts\Translation\TranslatorInterface;
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
        private NotificationService $notificationService,
        private TranslatorInterface $translator
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

                $critComment = $form->get('comment_' . $key)->getData();
                $rating->setCommentFor($key, $critComment);
            }

            $this->entityManager->persist($rating);
            $this->entityManager->flush();

            // Send notification emails if not a draft
            if (!$idea->isDraft()) {
                $this->notificationService->notifyNewIdea($idea, $user);
                $this->addFlash('success', 'app.success_idea_created');
            } else {
                $this->addFlash('success', 'app.success_idea_created_draft');
            }

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
            throw $this->createAccessDeniedException($this->translator->trans('error.cannot_edit_others_idea'));
        }

        $wasDraft = $idea->isDraft();

        $rating = $this->ratingRepository->findOneBy([
            'businessIdea' => $idea,
            'user' => $user,
        ]);

        $existingScores = $rating ? $rating->getScores() : [];
        $existingComments = $rating ? $rating->getCriterionComments() : [];

        $form = $this->createForm(BusinessIdeaType::class, $idea, [
            'existing_scores' => $existingScores,
            'existing_comments' => $existingComments,
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

                $critComment = $form->get('comment_' . $key)->getData();
                $rating->setCommentFor($key, $critComment);
            }

            $this->entityManager->flush();

            // If the idea was a draft and is now published, notify collaborators
            if ($wasDraft && !$idea->isDraft()) {
                $this->notificationService->notifyNewIdea($idea, $user);
            }

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

        // Draft ideas can only be viewed and edited by their creator
        if ($idea->isDraft()) {
            throw $this->createAccessDeniedException($this->translator->trans('error.cannot_view_draft'));
        }

        // Prevent the creator of the idea from accessing the rating/commenting page
        if ($idea->getCreator()->getId() === $user->getId()) {
            throw $this->createAccessDeniedException($this->translator->trans('error.creator_cannot_comment'));
        }

        // Allow user to rate another person's idea or modify their existing rating
        $rating = $this->ratingRepository->findOneBy([
            'businessIdea' => $idea,
            'user' => $user,
        ]);

        $existingScores = $rating ? $rating->getScores() : [];
        $existingComment = $rating ? $rating->getComment() : null;
        $existingComments = $rating ? $rating->getCriterionComments() : [];

        $form = $this->createForm(RatingType::class, null, [
            'existing_scores' => $existingScores,
            'existing_comment' => $existingComment,
            'existing_comments' => $existingComments,
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

                $critComment = $form->get('comment_' . $key)->getData();
                $rating->setCommentFor($key, $critComment);
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
            throw $this->createAccessDeniedException($this->translator->trans('error.cannot_delete_others_idea'));
        }

        if ($this->isCsrfTokenValid('delete' . $idea->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($idea);
            $this->entityManager->flush();
            $this->addFlash('success', 'app.success_idea_deleted');
        }

        return $this->redirectToRoute('app_home');
    }
}
