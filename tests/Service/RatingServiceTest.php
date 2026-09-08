<?php

namespace App\Tests\Service;

use App\Entity\BusinessIdea;
use App\Entity\Rating;
use App\Entity\User;
use App\Service\CriteriaManager;
use App\Service\RatingService;
use PHPUnit\Framework\TestCase;

class RatingServiceTest extends TestCase
{
    private RatingService $ratingService;

    protected function setUp(): void
    {
        $this->ratingService = new RatingService();
    }

    public function testCalculateRatingScoreWithNoScores(): void
    {
        $user = new User();
        $rating = new Rating();
        $rating->setUser($user);

        $score = $this->ratingService->calculateRatingScore($rating);
        $this->assertEquals(0.0, $score);
    }

    public function testCalculateRatingScoreWithScores(): void
    {
        $user = new User();
        // Set weights
        $user->setPreferenceWeight('profitability', CriteriaManager::WEIGHT_HIGH); // weight 3
        $user->setPreferenceWeight('feasibility', CriteriaManager::WEIGHT_MEDIUM); // weight 2

        $rating = new Rating();
        $rating->setUser($user);
        
        // Mock scores
        $rating->setScoreFor('profitability', 4); // 4 * 3 = 12
        $rating->setScoreFor('feasibility', 3);   // 3 * 2 = 6
                                      // Total weighted sum = 18. Total weight = 5.
                                      // Score = 18 / 5 = 3.6

        $score = $this->ratingService->calculateRatingScore($rating);
        $this->assertEquals(3.6, $score);
    }

    public function testCalculateIdeaGlobalScore(): void
    {
        $idea = new BusinessIdea();

        // User 1
        $user1 = new User();
        $user1->setPreferenceWeight('profitability', CriteriaManager::WEIGHT_MEDIUM); // 2
        $rating1 = new Rating();
        $rating1->setUser($user1);
        $rating1->setScoreFor('profitability', 5); // 5 * 2 = 10 / 2 = 5.0
        
        // User 2
        $user2 = new User();
        $user2->setPreferenceWeight('profitability', CriteriaManager::WEIGHT_MEDIUM); // 2
        $rating2 = new Rating();
        $rating2->setUser($user2);
        $rating2->setScoreFor('profitability', 3); // 3 * 2 = 6 / 2 = 3.0

        $idea->addRating($rating1);
        $idea->addRating($rating2);

        $globalScore = $this->ratingService->calculateIdeaGlobalScore($idea);
        
        // (5.0 + 3.0) / 2 = 4.0
        $this->assertEquals(4.0, $globalScore);
    }

    public function testCalculateIdeaGlobalScoreEmpty(): void
    {
        $idea = new BusinessIdea();
        $this->assertEquals(0.0, $this->ratingService->calculateIdeaGlobalScore($idea));
    }
}
