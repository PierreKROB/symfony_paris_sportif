<?php

namespace App\Entity;

use App\Repository\OutcomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Service\OddsCalculatorService;

#[ORM\Entity(repositoryClass: OutcomeRepository::class)]
class Outcome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column]
    private ?float $odds = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isWinner = null;

    #[ORM\ManyToOne(inversedBy: 'outcomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SportEvent $event = null;

    #[ORM\OneToMany(targetEntity: Bet::class, mappedBy: 'outcome')]
    private Collection $bets;

    public function __construct()
    {
        $this->bets = new ArrayCollection();
        $this->odds = OddsCalculatorService::DEFAULT_ODDS ?? 1.50;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getOdds(): ?float
    {
        return $this->odds;
    }

    public function setOdds(float $odds): static
    {
        $this->odds = $odds;

        return $this;
    }

    public function isWinner(): ?bool
    {
        return $this->isWinner;
    }

    public function setIsWinner(?bool $isWinner): static
    {
        $this->isWinner = $isWinner;

        return $this;
    }

    public function getEvent(): ?SportEvent
    {
        return $this->event;
    }

    public function setEvent(?SportEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getBets(): Collection
    {
        return $this->bets;
    }
}
