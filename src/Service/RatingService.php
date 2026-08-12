<?php

namespace App\Service;

use App\Entity\BusinessIdea;
use App\Entity\Rating;
use App\Entity\User;

class RatingService
{
    /**
     * Calculates the score of a single Rating using the preferences of the user who made the rating.
     * Scale: 1.0 to 5.0. Returns 0.0 if no criteria are rated.
     */
    public function calculateRatingScore(Rating $rating): float
    {
        $preferenceUser = $rating->getUser();
        $criteria = CriteriaManager::getRatedCriteria();
        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($criteria as $key => $config) {
            $score = $rating->getScoreFor($key);
            if ($score === null) {
                continue;
            }

            $weightStr = $preferenceUser->getPreferenceWeight($key);
            $weightVal = CriteriaManager::getWeightValue($weightStr);

            $weightedSum += $score * $weightVal;
            $totalWeight += $weightVal;
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Calculates the global score of a BusinessIdea based on all its ratings.
     * The global score is the simple average of all users' individual scores (50% each if 2 users).
     */
    public function calculateIdeaGlobalScore(BusinessIdea $idea): float
    {
        $ratings = $idea->getRatings();
        if ($ratings->isEmpty()) {
            return 0.0;
        }

        $sum = 0.0;
        $count = 0;

        foreach ($ratings as $rating) {
            $score = $this->calculateRatingScore($rating);
            if ($score > 0.0) {
                $sum += $score;
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        return round($sum / $count, 2);
    }

    /**
     * Sorts and ranks the list of ideas for a given user.
     * Returns an array of elements with 'idea', 'globalScore', and 'rank'.
     *
     * Sorting criteria options:
     * - 'global_score' (descending)
     * - 'last_added' (descending creation date)
     * - 'created_by_me' (user's ideas first)
     * - 'created_by_other' (other user's ideas first)
     * - 'status' (grouped by status)
     */
    public function getRankedIdeas(array $ideas, User $currentUser, string $sortBy = 'global_score'): array
    {
        // First compute scores for all ideas relative to current user
        $scoredIdeas = [];
        foreach ($ideas as $idea) {
            $ratingsData = [];
            foreach ($idea->getRatings() as $rating) {
                $ratingsData[] = [
                    'email' => $rating->getUser()->getEmail(),
                    'displayName' => $rating->getUser()->getDisplayName(),
                    'score' => $this->calculateRatingScore($rating),
                    'isCreator' => ($rating->getUser()->getId() === $idea->getCreator()->getId()),
                ];
            }

            $scoredIdeas[] = [
                'idea' => $idea,
                'globalScore' => $this->calculateIdeaGlobalScore($idea),
                'ratingsData' => $ratingsData,
            ];
        }

        // Sort them by global score descending by default for ranking
        usort($scoredIdeas, function (array $a, array $b) {
            $scoreDiff = $b['globalScore'] <=> $a['globalScore'];
            if ($scoreDiff !== 0) {
                return $scoreDiff;
            }
            // Tie-breaker: creation date descending
            return $b['idea']->getCreatedAt() <=> $a['idea']->getCreatedAt();
        });

        // Assign ranks based on the global score sorting
        // Ideas with the same score get the same rank
        $currentRank = 1;
        $previousScore = null;
        $ideasWithRank = [];

        foreach ($scoredIdeas as $index => $item) {
            if ($previousScore !== null && $item['globalScore'] < $previousScore) {
                $currentRank = $index + 1;
            }
            $item['rank'] = $currentRank;
            $ideasWithRank[] = $item;
            $previousScore = $item['globalScore'];
        }

        // If sorting request is not global_score, re-sort now but keep the rank value
        if ($sortBy !== 'global_score') {
            usort($ideasWithRank, function (array $a, array $b) use ($sortBy, $currentUser) {
                switch ($sortBy) {
                    case 'last_added':
                        return $b['idea']->getCreatedAt() <=> $a['idea']->getCreatedAt();
                    case 'created_by_me':
                        $aIsMe = ($a['idea']->getCreator()->getId() === $currentUser->getId());
                        $bIsMe = ($b['idea']->getCreator()->getId() === $currentUser->getId());
                        if ($aIsMe && !$bIsMe) return -1;
                        if (!$aIsMe && $bIsMe) return 1;
                        return $b['idea']->getCreatedAt() <=> $a['idea']->getCreatedAt();
                    case 'created_by_other':
                        $aIsMe = ($a['idea']->getCreator()->getId() === $currentUser->getId());
                        $bIsMe = ($b['idea']->getCreator()->getId() === $currentUser->getId());
                        if (!$aIsMe && $bIsMe) return -1;
                        if ($aIsMe && !$bIsMe) return 1;
                        return $b['idea']->getCreatedAt() <=> $a['idea']->getCreatedAt();
                    case 'status':
                        // Draft first, then Adopted, then Abandoned
                        $statusOrder = [
                            BusinessIdea::STATUS_DRAFT => 1,
                            BusinessIdea::STATUS_ADOPTED => 2,
                            BusinessIdea::STATUS_ABANDONED => 3,
                        ];
                        $orderA = $statusOrder[$a['idea']->getStatus()] ?? 99;
                        $orderB = $statusOrder[$b['idea']->getStatus()] ?? 99;
                        if ($orderA !== $orderB) {
                            return $orderA <=> $orderB;
                        }
                        return $b['globalScore'] <=> $a['globalScore'];
                    default:
                        return 0;
                }
            });
        }

        return $ideasWithRank;
    }
}
