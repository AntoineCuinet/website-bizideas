<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: '`rating`')]
#[ORM\UniqueConstraint(name: 'unique_user_idea_rating', columns: ['user_id', 'business_idea_id'])]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ratings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BusinessIdea $businessIdea = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Stores scores for each rated criterion.
     * Example: ['profitability' => 4, 'feasibility' => 5, ...]
     */
    #[ORM\Column]
    private array $scores = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessIdea(): ?BusinessIdea
    {
        return $this->businessIdea;
    }

    public function setBusinessIdea(?BusinessIdea $businessIdea): static
    {
        $this->businessIdea = $businessIdea;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getScores(): array
    {
        return $this->scores;
    }

    public function setScores(array $scores): static
    {
        $this->scores = $scores;
        return $this;
    }

    public function getScoreFor(string $criterion): ?int
    {
        return isset($this->scores[$criterion]) ? (int) $this->scores[$criterion] : null;
    }

    public function setScoreFor(string $criterion, int $score): static
    {
        if ($score >= 1 && $score <= 5) {
            $this->scores[$criterion] = $score;
        }
        return $this;
    }
}
