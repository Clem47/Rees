<?php

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin', methods: ['GET'])]
    //#[IsGranted('ROLE_ADMIN')]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        if ($user == null) {
            return $this->redirectToRoute('app_login');
        }

        $query = $entityManager ->createQuery('Select u from App\Entity\User u order by u.registerDate DESC');
        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render(
            'user/index.html.twig',
            [
            'users' => $users,
            ]
        );
    }

    #[Route('/search', name: 'app_user_search')]
    public function search(
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        if ($user == null) {
            return $this->redirectToRoute('app_login');
        }
        $mailAdress = $request->query->get('mailAdress');
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('s')
            ->from(User::class, 's')
            ->Where("s.email LIKE :mail")
            ->orderBy('s.registerDate', 'DESC')
            ->setParameter('mail', "%$mailAdress%");

        $query = $queryBuilder->getQuery();

        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render(
            'user/index.html.twig',
            [
            'users' => $users,
            ]
        );
    }
    //#[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm(
            'user/new.html.twig',
            [
            'user' => $user,
            'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    //#[IsGranted('ROLE_ADMIN')]
    public function show(
        EntityManagerInterface $entityManager,
        User $user,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $rate = $request->query->get('rate') ?? null;

        if ($rate != null) {
            $query = $entityManager->createQuery(
                "SELECT r
                FROM App\Entity\Rating r
                INNER JOIN App\Entity\User u
                WHERE r.user = u
                AND u.id = :id
                And r.value = :rate"
            )
                ->setParameter('id', $user->getId())
                ->setParameter('rate', $rate);
        } else {
            $query = $entityManager->createQuery(
                "SELECT r
                    FROM App\Entity\Rating r
                    INNER JOIN App\Entity\User u
                    WHERE r.user = u
                    AND u.id = :id"
            )->setParameter('id', $user->getId());
        }

        $ratedSeries = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $query = $entityManager->getRepository(Series::class)
        ->createQueryBuilder('s')
        ->select('s')
        ->join('s.user', 'u')
        ->Where('u.id = :user')
        ->orderBy('s.id')
        ->setParameter('user', $user)
        ->getQuery();

        $series = $paginator->paginate(
            $query,
            $request->query->getInt('pageLike', 1),
            10,
            ['pageParameterName' => 'pageLike']
        );

        return $this->render(
            'user/show.html.twig',
            [
            'user' => $user,
            'rates' => $ratedSeries,
            'series' => $series
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    //#[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm(
            'user/edit.html.twig',
            [
            'user' => $user,
            'form' => $form,
            ]
        );
    }


    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }
}
