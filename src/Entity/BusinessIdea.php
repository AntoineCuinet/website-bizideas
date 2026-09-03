<?php

namespace App\Entity;

use App\Repository\BusinessIdeaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BusinessIdeaRepository::class)]
class BusinessIdea
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ADOPTED = 'adopted';
    public const STATUS_ABANDONED = 'abandoned';

    public const REVENUE_SINGLE = 'single';
    public const REVENUE_RECURRING = 'recurring';
    public const REVENUE_BOTH = 'both';

    public const AUDIENCE_B2B = 'b2b';
    public const AUDIENCE_B2C = 'b2c';
    public const AUDIENCE_BOTH = 'both';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'idea.title.blank')]
    #[Assert\Length(max: 255, maxMessage: 'idea.title.max_length')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'idea.description.blank')]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'idea.status.blank')]
    #[Assert\Choice(choices: [self::STATUS_DRAFT, self::STATUS_ADOPTED, self::STATUS_ABANDONED])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'idea.revenue_model.blank')]
    #[Assert\Choice(choices: [self::REVENUE_SINGLE, self::REVENUE_RECURRING, self::REVENUE_BOTH])]
    private ?string $revenueModel = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'idea.target_audience.blank')]
    #[Assert\Choice(choices: [self::AUDIENCE_B2B, self::AUDIENCE_B2C, self::AUDIENCE_BOTH])]
    private ?string $targetAudience = null;

    #[ORM\ManyToOne(inversedBy: 'businessIdeas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'businessIdea', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ratings;

    public function __construct()
    {
        $this->ratings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isAdopted(): bool
    {
        return $this->status === self::STATUS_ADOPTED;
    }

    public function isAbandoned(): bool
    {
        return $this->status === self::STATUS_ABANDONED;
    }

    public function isVisibleTo(User $user): bool
    {
        if (!$this->isDraft()) {
            return true;
        }

        return $this->creator !== null && $this->creator->getId() === $user->getId();
    }

    public function getRevenueModel(): ?string
    {
        return $this->revenueModel;
    }

    public function setRevenueModel(string $revenueModel): static
    {
        $this->revenueModel = $revenueModel;
        return $this;
    }

    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(string $targetAudience): static
    {
        $this->targetAudience = $targetAudience;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setBusinessIdea($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getBusinessIdea() === $this) {
                $rating->setBusinessIdea(null);
            }
        }

        return $this;
    }

    public function getRatingByUser(User $user): ?Rating
    {
        foreach ($this->ratings as $rating) {
            if ($rating->getUser()->getId() === $user->getId()) {
                return $rating;
            }
        }
        return null;
    }

}
