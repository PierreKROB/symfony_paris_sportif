<?php

namespace App\DataFixtures;

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
        $this->makeUser('AdminNexus', 'admin@nexusbet.gg',   ['ROLE_ADMIN'],   0,      $manager);
        $this->makeUser('Diarapak',   'manager@nexusbet.gg', ['ROLE_MANAGER'], 0,      $manager);

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
