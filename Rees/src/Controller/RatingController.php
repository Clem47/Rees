<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Series;
use App\Form\RatingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/', name: 'app_rating_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->findAll();

        return $this->render(
            'rating/index.html.twig',
            [
            'ratings' => $ratings,
            ]
        );
    }

    #[Route('/new', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {

        $serie = $entityManager
            ->getRepository(Series::class)
            ->findOneBy(['id' => $request->query->getInt('id')]);

        $haveRate = $entityManager
            ->getRepository(Rating::class)
            ->findOneBy(['user' => $this->getUser(),'series' => $serie]);

        if ($haveRate == null) {
            $rating = new Rating();
            $form = $this->createForm(RatingType::class, $rating);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $rating->setDate(new \DateTime());
                $rating->setUser($this->getUser());
                $rating->setSeries($serie);
                $entityManager->persist($rating);
                $entityManager->flush();
                return
                $this->redirectToRoute(
                    'app_series_show',
                    ['id'  => $rating->getSeries()->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }

            return $this->renderForm(
                'rating/new.html.twig',
                [
                'rating' => $rating,
                'form' => $form,
                ]
            );
        }

        return $this->redirectToRoute(
            'app_series_show',
            [
            'id' => $request->query->getInt('id')
            ]
        );
    }

    #[Route('/{id}', name: 'app_rating_show', methods: ['GET'])]
    public function show(Rating $rating): Response
    {
        return $this->render(
            'rating/show.html.twig',
            [
            'rating' => $rating,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_rating_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Rating $rating,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);
        $user = $this->getUser();
        if ($user == null) {
            return $this->redirectToRoute('app_login');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            
            $rating->setDate(new \DateTime());
            $entityManager->flush();
            return $this->redirectToRoute(
                'app_series_show',
                ['id'  => $rating->getSeries()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm(
            'rating/edit.html.twig',
            [
            'rating' => $rating,
            'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_rating_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Rating $rating,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $rating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rating);
            $entityManager->flush();
        }
        return $this->redirectToRoute(
            'app_series_show',
            ['id'  => $rating->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }
}
