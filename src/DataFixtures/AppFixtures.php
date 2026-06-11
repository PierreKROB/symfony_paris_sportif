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
        // ── Utilisateurs ──────────────────────────────────────────────────────

        $admin   = $this->makeUser('AdminNexus', 'admin@nexusbet.gg',   ['ROLE_ADMIN'],   0,      $manager);
        $manager_ = $this->makeUser('Diarapak',  'manager@nexusbet.gg', ['ROLE_MANAGER'], 0,      $manager);

        $users = [];
        foreach ([
            ['Faker_Fan',   'faker@nexusbet.gg',  500.00],
            ['T1_Enjoyer',  't1@nexusbet.gg',     250.00],
            ['GenG_King',   'geng@nexusbet.gg',   150.00],
            ['G2_Believer', 'g2@nexusbet.gg',     800.00],
            ['BLG_Support', 'blg@nexusbet.gg',    300.00],
        ] as [$username, $email, $balance]) {
            $u = $this->makeUser($username, $email, [], $balance, $manager);
            $manager->persist(
                (new Transaction())
                    ->setUser($u)
                    ->setAmount((string) $balance)
                    ->setType(Transaction::TYPE_DEPOSIT)
                    ->setDescription('Dépôt initial')
            );
            $users[] = $u;
        }

        // ── Événements avec leurs issues ──────────────────────────────────────

        $eventsData = [
            [
                'name'       => 'T1 vs Gen.G — LCK Spring Finals',
                'sport'      => 'League of Legends',
                'tournament' => 'LCK Spring 2026',
                'teamA'      => 'T1',
                'teamB'      => 'Gen.G',
                'startsAt'   => '+1 day',
                'status'     => SportEvent::STATUS_PUBLISHED,
                'oddsA'      => 1.60,
                'oddsB'      => 2.10,
            ],
            [
                'name'       => 'G2 Esports vs Fnatic — LEC Playoffs',
                'sport'      => 'League of Legends',
                'tournament' => 'LEC Spring 2026',
                'teamA'      => 'G2 Esports',
                'teamB'      => 'Fnatic',
                'startsAt'   => '+2 days',
                'status'     => SportEvent::STATUS_PUBLISHED,
                'oddsA'      => 1.45,
                'oddsB'      => 2.70,
            ],
            [
                'name'       => 'BLG vs EDG — LPL Summer',
                'sport'      => 'League of Legends',
                'tournament' => 'LPL Summer 2026',
                'teamA'      => 'BLG',
                'teamB'      => 'EDG',
                'startsAt'   => '+3 days',
                'status'     => SportEvent::STATUS_PUBLISHED,
                'oddsA'      => 1.80,
                'oddsB'      => 1.90,
            ],
            [
                'name'       => 'Cloud9 vs Team Liquid — LCS',
                'sport'      => 'League of Legends',
                'tournament' => 'LCS Summer 2026',
                'teamA'      => 'Cloud9',
                'teamB'      => 'Team Liquid',
                'startsAt'   => '-3 days',
                'status'     => SportEvent::STATUS_FINISHED,
                'oddsA'      => 1.70,
                'oddsB'      => 2.00,
                'winner'     => 'A',
            ],
            [
                'name'       => 'KT Rolster vs DRX — LCK',
                'sport'      => 'League of Legends',
                'tournament' => 'LCK Summer 2026',
                'teamA'      => 'KT Rolster',
                'teamB'      => 'DRX',
                'startsAt'   => '-1 day',
                'status'     => SportEvent::STATUS_CLOSED,
                'oddsA'      => 1.55,
                'oddsB'      => 2.30,
            ],
            [
                'name'       => 'Vitality vs MAD Lions — LEC',
                'sport'      => 'League of Legends',
                'tournament' => 'LEC Spring 2026',
                'teamA'      => 'Vitality',
                'teamB'      => 'MAD Lions',
                'startsAt'   => '+7 days',
                'status'     => SportEvent::STATUS_DRAFT,
                'oddsA'      => 1.50,
                'oddsB'      => 1.50,
            ],
            [
                'name'       => 'NRG vs 100 Thieves — LCS (annulé)',
                'sport'      => 'League of Legends',
                'tournament' => 'LCS Summer 2026',
                'teamA'      => 'NRG',
                'teamB'      => '100 Thieves',
                'startsAt'   => '+5 days',
                'status'     => SportEvent::STATUS_CANCELLED,
                'oddsA'      => 1.50,
                'oddsB'      => 1.50,
            ],
        ];

        $createdOutcomes = [];

        foreach ($eventsData as $data) {
            $event = (new SportEvent())
                ->setName($data['name'])
                ->setSport($data['sport'])
                ->setTournament($data['tournament'])
                ->setTeamA($data['teamA'])
                ->setTeamB($data['teamB'])
                ->setStartsAt(new \DateTime($data['startsAt']))
                ->setStatus($data['status']);

            $outcomeA = (new Outcome())
                ->setLabel('Victoire ' . $data['teamA'])
                ->setOdds($data['oddsA'])
                ->setEvent($event);

            $outcomeB = (new Outcome())
                ->setLabel('Victoire ' . $data['teamB'])
                ->setOdds($data['oddsB'])
                ->setEvent($event);

            // Marque le gagnant pour les événements terminés
            if (isset($data['winner'])) {
                if ($data['winner'] === 'A') {
                    $outcomeA->setIsWinner(true);
                    $outcomeB->setIsWinner(false);
                } else {
                    $outcomeA->setIsWinner(false);
                    $outcomeB->setIsWinner(true);
                }
            }

            $manager->persist($event);
            $manager->persist($outcomeA);
            $manager->persist($outcomeB);

            $createdOutcomes[] = ['event' => $event, 'outcomeA' => $outcomeA, 'outcomeB' => $outcomeB, 'data' => $data];
        }

        // On flush ici pour que les outcomes aient un ID utilisable pour les paris
        $manager->flush();

        // ── Paris et transactions associées ───────────────────────────────────

        // Quelques paris sur les événements publiés et terminés
        $betsData = [
            // [userIndex, eventIndex, outcomeKey (A ou B), amount, status]
            [0, 0, 'A', 50.00,  Bet::STATUS_PENDING],
            [1, 0, 'A', 30.00,  Bet::STATUS_PENDING],
            [2, 0, 'B', 20.00,  Bet::STATUS_PENDING],
            [3, 1, 'A', 100.00, Bet::STATUS_PENDING],
            [4, 1, 'B', 40.00,  Bet::STATUS_PENDING],
            [0, 2, 'A', 25.00,  Bet::STATUS_PENDING],
            // Paris sur l'événement terminé (Cloud9 vs Team Liquid, winner A)
            [1, 3, 'A', 60.00,  Bet::STATUS_WON],
            [2, 3, 'B', 35.00,  Bet::STATUS_LOST],
            [3, 3, 'A', 45.00,  Bet::STATUS_WON],
            // Paris sur l'événement fermé
            [4, 4, 'A', 80.00,  Bet::STATUS_PENDING],
        ];

        foreach ($betsData as [$userIdx, $eventIdx, $outcomeKey, $amount, $status]) {
            $user    = $users[$userIdx];
            $outcome = $createdOutcomes[$eventIdx]['outcome' . $outcomeKey];
            $odds    = $outcome->getOdds();

            $bet = (new Bet())
                ->setUser($user)
                ->setOutcome($outcome)
                ->setAmount((string) $amount)
                ->setLockedOdds($odds)
                ->setStatus($status);

            $manager->persist($bet);

            // Transaction de mise
            $manager->persist(
                (new Transaction())
                    ->setUser($user)
                    ->setAmount((string) $amount)
                    ->setType(Transaction::TYPE_BET)
                    ->setDescription('Mise sur « ' . $outcome->getLabel() . ' »')
            );

            // Pour les paris gagnés, on crée la transaction de gain
            if ($status === Bet::STATUS_WON) {
                $gain = round($amount * $odds, 2);
                $manager->persist(
                    (new Transaction())
                        ->setUser($user)
                        ->setAmount((string) $gain)
                        ->setType(Transaction::TYPE_WIN)
                        ->setDescription('Gain sur « ' . $outcome->getLabel() . ' »')
                );
            }
        }

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
