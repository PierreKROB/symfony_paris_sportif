<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Objet de transfert de données pour la soumission d'un pari.
 *
 * Un DTO (Data Transfer Object) sert à transporter les données du formulaire
 * avant de les passer au service. Cela évite de lier directement le formulaire
 * à l'entité Bet et permet de valider les données en dehors de l'entité.
 */
class PlaceBetDTO
{
    #[Assert\NotNull(message: 'Le montant est obligatoire.')]
    #[Assert\Positive(message: 'Le montant doit être positif.')]
    #[Assert\LessThanOrEqual(value: 10000, message: 'Le montant ne peut pas dépasser 10 000 €.')]
    public ?float $amount = null;
}
