<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WalletService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function deposit(User $user, float $amount): void
    {
        $user->setBalance($user->getBalance() + $amount);

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount((string) $amount);
        $transaction->setType(Transaction::TYPE_DEPOSIT);
        $transaction->setDescription('Dépôt de ' . $amount . ' €');

        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function debit(User $user, float $amount, string $description): void
    {
        $user->setBalance($user->getBalance() - $amount);

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount((string) $amount);
        $transaction->setType(Transaction::TYPE_BET);
        $transaction->setDescription($description);

        $this->em->persist($transaction);
    }

    public function credit(User $user, float $amount, string $description): void
    {
        $user->setBalance($user->getBalance() + $amount);

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount((string) $amount);
        $transaction->setType(Transaction::TYPE_WIN);
        $transaction->setDescription($description);

        $this->em->persist($transaction);
    }

    public function refund(User $user, float $amount, string $description): void
    {
        $user->setBalance($user->getBalance() + $amount);

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount((string) $amount);
        $transaction->setType(Transaction::TYPE_REFUND);
        $transaction->setDescription($description);

        $this->em->persist($transaction);
    }

    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return $user->getBalance() >= $amount;
    }
}
