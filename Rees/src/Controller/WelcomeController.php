<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class WelcomeController extends AbstractController
{
    #[Route('/', name: 'app_welcome')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $finalSeries = [];

        for ($i = 0; $i < 3; $i++) {
            // TODO put actual values from the database
            $randomSeries = rand(1, 234);
            $rand = $entityManager
                ->getRepository(Series::class)
                ->findOneBy(array('id' => $randomSeries));
            if ($rand != null) {
                $finalSeries[$i] = $rand;
            } else {
                while ($rand == null) {
                    $randomSeries = rand(1, 234);
                    $rand =  $entityManager
                        ->getRepository(Series::class)
                        ->findOneBy(array('id' => $randomSeries));
                }
                $finalSeries[$i] = $rand;
            }
        }

        return $this->render(
            'welcome/index.html.twig',
            [
                'series' => $finalSeries,
            ]
        );
    }
}
