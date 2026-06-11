<?php

namespace App\Twig\Components;

use App\Entity\Outcome;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Composant live : affiche la cote en temps réel et calcule le gain potentiel.
 *
 * Chaque fois que l'utilisateur modifie le champ "amount", Symfony envoie
 * une requête AJAX, le composant est ré-exécuté côté serveur avec la nouvelle
 * valeur, et le HTML mis à jour est injecté dans la page sans rechargement.
 *
 * Avantage : la cote utilisée est toujours celle en base (même si elle a changé
 * depuis que la page s'est chargée).
 */
#[AsLiveComponent]
class BetSimulator
{
    use DefaultActionTrait;

    /** Montant saisi par l'utilisateur — writable = modifiable côté client */
    #[LiveProp(writable: true)]
    public float $amount = 0;

    /** ID de l'issue sur laquelle on parie — transmis à l'initialisation */
    #[LiveProp]
    public int $outcomeId = 0;

    public function __construct(private EntityManagerInterface $em) {}

    public function getOutcome(): ?Outcome
    {
        return $this->em->find(Outcome::class, $this->outcomeId);
    }

    /** Cote actuelle récupérée en base à chaque re-rendu */
    public function getCurrentOdds(): float
    {
        return $this->getOutcome()?->getOdds() ?? 1.50;
    }

    /** Gain potentiel = mise × cote */
    public function getPotentialGain(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return round($this->amount * $this->getCurrentOdds(), 2);
    }
}
