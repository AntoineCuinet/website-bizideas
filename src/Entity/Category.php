<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, BusinessIdea>
     */
    #[ORM\ManyToMany(targetEntity: BusinessIdea::class, mappedBy: 'categories')]
    private Collection $businessIdeas;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->businessIdeas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, BusinessIdea>
     */
    public function getBusinessIdeas(): Collection
    {
        return $this->businessIdeas;
    }

    public function addBusinessIdea(BusinessIdea $businessIdea): static
    {
        if (!$this->businessIdeas->contains($businessIdea)) {
            $this->businessIdeas->add($businessIdea);
            $businessIdea->addCategory($this);
        }

        return $this;
    }

    public function removeBusinessIdea(BusinessIdea $businessIdea): static
    {
        if ($this->businessIdeas->removeElement($businessIdea)) {
            $businessIdea->removeCategory($this);
        }

        return $this;
    }
}
