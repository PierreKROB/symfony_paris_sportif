<?php

namespace App\Entity;

use App\Repository\SportEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportEventRepository::class)]
class SportEvent
{
    const STATUS_DRAFT     = 'BROUILLON';
    const STATUS_PUBLISHED = 'PUBLIE';
    const STATUS_CLOSED    = 'FERME';
    const STATUS_FINISHED  = 'TERMINE';
    const STATUS_CANCELLED = 'ANNULE';

    const SOURCE_MANUAL     = 'manual';
    const SOURCE_RIOT       = 'riot_ranked';
    const SOURCE_ESPORTS    = 'lol_esports';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $tournament = null;

    #[ORM\Column(length: 100)]
    private ?string $teamA = null;

    #[ORM\Column(length: 100)]
    private ?string $teamB = null;

    #[ORM\Column(length: 100)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTime $startsAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, options: ['default' => 'manual'])]
    private string $source = self::SOURCE_MANUAL;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $riotMatchId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $riotPuuid = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $summonerName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $teamALogoUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $teamBLogoUrl = null;

    /**
     * @var Collection<int, Outcome>
     */
    #[ORM\OneToMany(targetEntity: Outcome::class, mappedBy: 'event')]
    private Collection $outcomes;

    public function __construct()
    {
        $this->outcomes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_DRAFT;
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

    public function getTournament(): ?string
    {
        return $this->tournament;
    }

    public function setTournament(string $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getTeamA(): ?string
    {
        return $this->teamA;
    }

    public function setTeamA(string $teamA): static
    {
        $this->teamA = $teamA;

        return $this;
    }

    public function getTeamB(): ?string
    {
        return $this->teamB;
    }

    public function setTeamB(string $teamB): static
    {
        $this->teamB = $teamB;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartsAt(): ?\DateTime
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTime $startsAt): static
    {
        $this->startsAt = $startsAt;

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

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getRiotMatchId(): ?string
    {
        return $this->riotMatchId;
    }

    public function setRiotMatchId(?string $riotMatchId): static
    {
        $this->riotMatchId = $riotMatchId;
        return $this;
    }

    public function getRiotPuuid(): ?string
    {
        return $this->riotPuuid;
    }

    public function setRiotPuuid(?string $riotPuuid): static
    {
        $this->riotPuuid = $riotPuuid;
        return $this;
    }

    public function getSummonerName(): ?string
    {
        return $this->summonerName;
    }

    public function setSummonerName(?string $summonerName): static
    {
        $this->summonerName = $summonerName;
        return $this;
    }

    public function getTeamALogoUrl(): ?string
    {
        return $this->teamALogoUrl;
    }

    public function setTeamALogoUrl(?string $teamALogoUrl): static
    {
        $this->teamALogoUrl = $teamALogoUrl;
        return $this;
    }

    public function getTeamBLogoUrl(): ?string
    {
        return $this->teamBLogoUrl;
    }

    public function setTeamBLogoUrl(?string $teamBLogoUrl): static
    {
        $this->teamBLogoUrl = $teamBLogoUrl;
        return $this;
    }

    /**
     * @return Collection<int, Outcome>
     */
    public function getOutcomes(): Collection
    {
        return $this->outcomes;
    }

    public function addOutcome(Outcome $outcome): static
    {
        if (!$this->outcomes->contains($outcome)) {
            $this->outcomes->add($outcome);
            $outcome->setEvent($this);
        }

        return $this;
    }

    public function removeOutcome(Outcome $outcome): static
    {
        if ($this->outcomes->removeElement($outcome)) {
            // set the owning side to null (unless already changed)
            if ($outcome->getEvent() === $this) {
                $outcome->setEvent(null);
            }
        }

        return $this;
    }
}
