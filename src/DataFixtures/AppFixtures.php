<?php

namespace App\DataFixtures;

use App\Entity\Bet;
use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── USERS ──────────────────────────────────────────────────────────────

        $this->makeUser('AdminNexus', 'admin@nexusbet.gg', ['ROLE_ADMIN'], 0, $manager);
        $this->makeUser('Diarapak',   'manager@nexusbet.gg', ['ROLE_MANAGER'], 0, $manager);

        $players = [];
        foreach ([
            ['Faker_Fan',   'faker@nexusbet.gg',  500.00],
            ['T1_Enjoyer',  't1@nexusbet.gg',     250.00],
            ['GenG_King',   'geng@nexusbet.gg',   150.00],
            ['G2_Believer', 'g2@nexusbet.gg',     800.00],
            ['BLG_Support', 'blg@nexusbet.gg',    300.00],
        ] as [$username, $email, $balance]) {
            $u = $this->makeUser($username, $email, [], $balance, $manager);
            $tx = (new Transaction())
                ->setUser($u)->setAmount((string) $balance)
                ->setType(Transaction::TYPE_DEPOSIT)
                ->setDescription('Dépôt initial');
            $manager->persist($tx);
            $players[] = $u;
        }

        // ── ÉVÉNEMENTS MSI 2025 ────────────────────────────────────────────────

        $eventsData = [
            ['T1',          'Gen.G',        'MSI 2025 — Demi-finale',      SportEvent::STATUS_PUBLISHED, '+2 days',  1.75, 2.10],
            ['G2 Esports',  'Cloud9',       'MSI 2025 — Demi-finale',      SportEvent::STATUS_PUBLISHED, '+3 days',  1.90, 1.95],
            ['BLG',         'Team Liquid',  'MSI 2025 — Quart de finale',  SportEvent::STATUS_PUBLISHED, '+1 day',   1.50, 2.60],
            ['FNC',         'NRG',          'MSI 2025 — Quart de finale',  SportEvent::STATUS_CLOSED,    '-1 day',   2.20, 1.65],
            ['T1',          'G2 Esports',   'MSI 2025 — Finale',           SportEvent::STATUS_DRAFT,     '+7 days',  1.60, 2.40],
            ['Weibo',       'Top Esports',  'MSI 2025 — Play-In',          SportEvent::STATUS_FINISHED,  '-5 days',  1.85, 2.05],
            ['100 Thieves', 'PSG Talon',    'MSI 2025 — Play-In',          SportEvent::STATUS_CANCELLED, '-7 days',  2.00, 1.85],
        ];

        $events = [];
        foreach ($eventsData as [$teamA, $teamB, $tournament, $status, $offset, $oddsA, $oddsB]) {
            $event = (new SportEvent())
                ->setName($teamA . ' vs ' . $teamB)
                ->setTournament($tournament)
                ->setTeamA($teamA)
                ->setTeamB($teamB)
                ->setStatus($status)
                ->setStartsAt(new \DateTime($offset));

            $oA = (new Outcome())->setLabel('Victoire ' . $teamA)->setOdds($oddsA)->setEvent($event);
            $oB = (new Outcome())->setLabel('Victoire ' . $teamB)->setOdds($oddsB)->setEvent($event);

            if ($status === SportEvent::STATUS_FINISHED) {
                $oA->setIsWinner(true);
                $oB->setIsWinner(false);
            }

            $manager->persist($event);
            $manager->persist($oA);
            $manager->persist($oB);
            $events[] = ['event' => $event, 'a' => $oA, 'b' => $oB];
        }

        $manager->flush();

        // ── PARIS ──────────────────────────────────────────────────────────────

        // Paris en attente sur les matchs publiés
        foreach ([
            [0, 0, 'a', 50.00],   // Faker_Fan → T1
            [1, 0, 'a', 100.00],  // T1_Enjoyer → T1
            [2, 0, 'b', 30.00],   // GenG_King → Gen.G
            [3, 1, 'a', 75.00],   // G2_Believer → G2
            [4, 2, 'b', 20.00],   // BLG_Support → Team Liquid
            [0, 1, 'b', 40.00],   // Faker_Fan → Cloud9
        ] as [$pi, $ei, $side, $amount]) {
            $player  = $players[$pi];
            $outcome = $events[$ei][$side];
            if ($player->getBalance() < $amount) continue;

            $bet = (new Bet())
                ->setUser($player)->setOutcome($outcome)
                ->setAmount((string) $amount)
                ->setLockedOdds($outcome->getOdds())
                ->setStatus(Bet::STATUS_PENDING);

            $player->setBalance($player->getBalance() - $amount);

            $tx = (new Transaction())
                ->setUser($player)->setAmount((string) $amount)
                ->setType(Transaction::TYPE_BET)
                ->setDescription('Mise sur ' . $outcome->getEvent()->getName() . ' — ' . $outcome->getLabel());

            $manager->persist($bet);
            $manager->persist($tx);
        }

        // Paris sur match terminé (Weibo vs Top Esports — Weibo gagne)
        $wonBet = (new Bet())
            ->setUser($players[0])->setOutcome($events[5]['a'])
            ->setAmount('80.00')->setLockedOdds(1.85)
            ->setStatus(Bet::STATUS_WON);
        $manager->persist($wonBet);

        $gain = round(80 * 1.85, 2);
        $players[0]->setBalance($players[0]->getBalance() + $gain);
        $manager->persist((new Transaction())
            ->setUser($players[0])->setAmount((string) $gain)
            ->setType(Transaction::TYPE_WIN)
            ->setDescription('Gain — Weibo vs Top Esports'));

        $lostBet = (new Bet())
            ->setUser($players[1])->setOutcome($events[5]['b'])
            ->setAmount('50.00')->setLockedOdds(2.05)
            ->setStatus(Bet::STATUS_LOST);
        $manager->persist($lostBet);

        // Paris annulés sur 100T vs PSG Talon
        $cancelledBet = (new Bet())
            ->setUser($players[2])->setOutcome($events[6]['a'])
            ->setAmount('25.00')->setLockedOdds(2.00)
            ->setStatus(Bet::STATUS_CANCELLED);
        $manager->persist($cancelledBet);

        $manager->persist((new Transaction())
            ->setUser($players[2])->setAmount('25.00')
            ->setType(Transaction::TYPE_REFUND)
            ->setDescription('Remboursement — 100 Thieves vs PSG Talon annulé'));
        $players[2]->setBalance($players[2]->getBalance() + 25);

        $manager->flush();
    }

    private function makeUser(string $username, string $email, array $roles, float $balance, ObjectManager $manager): User
    {
        $user = new User();
        $user->setUsername($username)
             ->setEmail($email)
             ->setRoles($roles)
             ->setBalance($balance)
             ->setBirthDate(new \DateTime('1995-06-15'))
             ->setPassword($this->hasher->hashPassword($user, 'password'));
        $manager->persist($user);
        return $user;
    }
}
