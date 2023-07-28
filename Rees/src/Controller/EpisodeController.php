<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Series;
use App\Entity\Season;
use App\Form\EpisodeType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/episode')]
class EpisodeController extends AbstractController
{
    /**
     * Display all the episodes
     */
    #[Route('/', name: 'app_episode_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $episodes = $entityManager
            ->getRepository(Episode::class)
            ->findAll();

        return $this->render(
            'episode/index.html.twig',
            [
            'episodes' => $episodes,
            ]
        );
    }

    /**
     * Display all the episodes tracked by the user
     */
    #[Route(['/tracked'], name: 'app_episode_tracked', methods: ['GET', 'POST'])]
    public function tracked(
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        if ($this->getUser() == null) {
            return $this->redirectToRoute('app_login');
        }
        $query = $entityManager->getRepository(Episode::class)
        ->createQueryBuilder('e')
        ->select('e')
        ->join('e.user', 'u')
        ->join('e.season', 's')
        ->join('s.series', 'se')
        ->Where('u.id = :user')
        ->orderBy('se.id, s.id, e.number')
        ->setParameter('user', $this->getUser())
        ->getQuery();

        $episodes = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render(
            'episode/tracked/index.html.twig',
            [
            'episodes' => $episodes
            ]
        );
    }


    /**
     * Page to create an episode
     */
    #[Route('/new', name: 'app_episode_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $episode = new Episode();
        $form = $this->createForm(EpisodeType::class, $episode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($episode);
            $entityManager->flush();

            return $this->redirectToRoute('app_episode_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm(
            'episode/new.html.twig',
            [
            'episode' => $episode,
            'form' => $form,
            ]
        );
    }

    /**
     * Page to display an episode
     */
    #[Route('/{id}', name: 'app_episode_show', methods: ['GET'])]
    public function show(Episode $episode): Response
    {
        return $this->render(
            'episode/show.html.twig',
            [
            'episode' => $episode,
            ]
        );
    }

    /**
     * Page to edit an episode
     */
    #[Route('/{id}/edit', name: 'app_episode_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Episode $episode, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EpisodeType::class, $episode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_episode_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm(
            'episode/edit.html.twig',
            [
            'episode' => $episode,
            'form' => $form,
            ]
        );
    }

    /**
     * Route to add an episode to the user's watched episodes
     */
    #[Route('/{idEpisode}/watch', name: 'app_watch_add')]
    public function watch(Request $request, EntityManagerInterface $entityManager): Response
    {

        $episodeId = $request->attributes->get('idEpisode');
        $episode = $entityManager->getRepository(Episode::class)->find($episodeId);
        $serie = $episode->getSeason()->getSeries();
        if (!$this->getUser()->getSeries()->contains($serie)) {
            $this->getUser()->addSeries($serie);
        }
        $episode->addUser($this->getUser());
        $entityManager->flush();


        return $this->redirectToRoute('app_series_show', ['id' => $serie->getId()]);
    }


    /**
     * Route to add a season from the user's watched episodes
     */
    #[Route('/{idSeason}/watchSeason', name: 'app_watchSeason_add')]
    public function seasonWatch(Request $request, EntityManagerInterface $entityManager): Response
    {
        $season = $request->attributes->get('idSeason');
        $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $season]);
        foreach ($episodes as $episode) {
            $serie = $episode->getSeason()->getSeries();
            if (!$this->getUser()->getSeries()->contains($serie)) {
                $this->getUser()->addSeries($serie);
            }
            $episode->addUser($this->getUser());
        }
        $entityManager->flush();


        return $this->redirectToRoute('app_series_show', ['id' => $serie->getId()]);
    }

    /**
     * Route to remove a season from the user's watched episodes
     */
    #[Route('/{idSeason}/NoWatchSeason', name: 'app_WatchSeason_remove')]
    public function seasonNoWatch(Request $request, EntityManagerInterface $entityManager): Response
    {
        $season = $request->attributes->get('idSeason');
        $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $season]);
        foreach ($episodes as $episode) {
            $serie = $episode->getSeason()->getSeries();
            $episode->removeUser($this->getUser());
        }
        $entityManager->flush();


        return $this->redirectToRoute('app_series_show', ['id' => $serie->getId()]);
    }


    /**
     * Route to remove an episode from the user's watched episodes
     */
    #[Route('/{idEpisode}/unwatch', name: 'app_watch_remove')]
    public function unWatch(Request $request, EntityManagerInterface $entityManager): Response
    {
        $episodeId = $request->attributes->get('idEpisode');
        $episode = $entityManager->getRepository(Episode::class)->find($episodeId);
        $serie = $episode->getSeason()->getSeries();
        $episode->removeUser($this->getUser());
        $entityManager->flush();
        return $this->redirectToRoute('app_series_show', ['id' => $serie->getId()]);
    }

    /**
     * Route to remove an episode
     */
    #[Route('/{id}', name: 'app_episode_delete', methods: ['POST'])]
    public function delete(Request $request, Episode $episode, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $episode->getId(), $request->request->get('_token'))) {
            $entityManager->remove($episode);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_episode_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{numberPrevious}/{season}/watchPrevious', name: 'app_watch_previous_episodes')]
    public function deleteRemovePrevious(Request $request, EntityManagerInterface $entityManager): Response
    {

        $season = $request->attributes->get('season');
        $season = $entityManager->getRepository(Season::class)->find($season);
        $serie = $season->getSeries();
        $episodeToAdd = $request->attributes->get('numberPrevious');
        $episodes = $entityManager->createQueryBuilder()
                                ->select('e')
                                ->from(Episode::class, 'e')
                                ->where('e.season = :season')
                                ->andWhere('e.number >= :first')
                                ->andWhere('e.number < :last')
                                ->setParameter('season', $season)
                                ->setParameter('first', 1)
                                ->setParameter('last', $episodeToAdd)
                                ->getQuery()
                                ->getResult();

        foreach ($episodes as $episode) {
            if (!$this->getUser()->getSeries()->contains($serie)) {
                $this->getUser()->addSeries($serie);
            }
            $episode->addUser($this->getUser());
        }
        $entityManager->flush();


        return $this->redirectToRoute('app_series_show', ['id' => $serie->getId()]);
    }
}
