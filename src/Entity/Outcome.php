<?php

namespace App\Entity;

use App\Repository\OutcomeRepository;
use App\Service\OddsCalculatorService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OutcomeRepository::class)]
class Outcome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le libellé de l\'issue est obligatoire.')]
    private ?string $label = null;

    #[ORM\Column]
    #[Assert\GreaterThan(value: 0, message: 'La cote doit être positive.')]
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
