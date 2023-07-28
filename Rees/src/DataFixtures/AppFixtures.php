<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Series;
use App\Entity\Rating;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;
use DateTime;

class AppFixtures extends Fixture
{
    private $faker;

    public function load(ObjectManager $manager): void
    {
        $this->faker = Faker\Factory::create('fr_FR');

        $series = $manager->getRepository(Series::class)->findAll();
        $usersToCreate = array();
        for ($i = 0; $i < 100; $i++) {
             $usersToCreate[$i] = new User();
             $usersToCreate[$i]->setName($this->faker->name);
             $usersToCreate[$i]->setEmail($this->faker->email);
             $usersToCreate[$i]->setPassword($this->faker->password);
             $usersToCreate[$i]->setAdmin(0);
             $usersToCreate[$i]->setRegisterDate(new DateTime('now'));
             $manager->persist($usersToCreate[$i]);
        }
        $users = $manager->getRepository(User::class)->findAll();
        foreach ($series as $serie) {
            $border = $this->faker->numberBetween(0, 10);
            foreach ($usersToCreate as $user) {
                $chanceRate = $this->faker->numberBetween(0, 100);
                if ($chanceRate < 33) {
                    $value = $this->faker->numberBetween($border - 3, $border + 3);
                    if ($value < 0) {
                        $value = 0;
                    }
                    if ($value > 10) {
                        $value = 10;
                    }
                    $comm = "";
                    switch ($value) {
                        case 0:
                            $comm = "JE SUIS UN TROLL";
                            break;
                        case 1:
                            $comm = "Nul";
                            break;
                        case 2:
                            $comm = "J'ai connu mieux de la part de ce réalisateur... Décu..";
                            break;
                        case 3:
                            $comm = "Pas terrible";
                            break;
                        case 4:
                            $comm = "Ca aurait pu être mieux";
                            break;
                        case 5:
                            $comm = "Moyen";
                            break;
                        case 6:
                            $comm = "Pas mal mais pas excellent non plus";
                            break;
                        case 7:
                            $comm = "Etonnamment bon à voir pour passer le temps";
                            break;
                        case 8:
                            $comm = "Très bien";
                            break;
                        case 9:
                            $comm = "Va falloir que je le regarde une deuxième fois";
                            break;
                        case 10:
                            $comm = "J'ai rarement vu une série aussi bien faite, 
                            mais elle n'arrive même pas à la cheville du Seigneur des Anneaux.";
                            break;
                    }
                    $value /= 2.0;
                    $ratings = new Rating();
                    $ratings->setUser($user);
                    $ratings->setSeries($serie);
                    $ratings->setValue($value);
                    $ratings->setComment($comm);
                    $ratings->setDate(new DateTime('now'));
                    $manager->persist($ratings);
                }
            }
        }
        $manager->flush();
    }
}
