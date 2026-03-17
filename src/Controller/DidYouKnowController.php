<?php

namespace App\Controller;

use App\Entity\DidYouKnowPost;
use App\Entity\DidYouKnowComment;
use App\Entity\DidYouKnowRating;
use App\Entity\User as AppUser;
use App\Repository\DidYouKnowPostRepository;
use App\Repository\DidYouKnowCommentRepository;
use App\Repository\DidYouKnowRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DidYouKnowController extends AbstractController
{
    #[Route('/did-you-know', name: 'app_did_you_know_index')]
    public function index(DidYouKnowPostRepository $postRepository, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 9;
        $allPosts = $postRepository->findPublished();
        $totalPosts = count($allPosts);
        $totalPages = max(1, (int) ceil($totalPosts / $perPage));
        if ($totalPosts > 0 && $page > $totalPages) {
            $page = $totalPages;
        }
        $posts = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

        return $this->render('did_you_know/index.html.twig', [
            'posts' => $posts,
            'page' => $page,
            'perPage' => $perPage,
            'totalPosts' => $totalPosts,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/did-you-know/{id}-{slug}', name: 'app_did_you_know_show', requirements: ['id' => '\d+'])]
    public function show(
        DidYouKnowPost $post,
        string $slug,
        DidYouKnowCommentRepository $commentRepository,
        DidYouKnowRatingRepository $ratingRepository
    ): Response {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }

        if ($post->getSlug() !== $slug) {
            return $this->redirectToRoute('app_did_you_know_show', [
                'id' => $post->getId(),
                'slug' => $post->getSlug()
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        $comments = $commentRepository->findApprovedForPost($post);
        $ratingStats = $ratingRepository->getStatsForPost($post);

        $myRating = null;
        $user = $this->getUser();
        if ($user instanceof AppUser) {
            $myRatingEntity = $ratingRepository->findOneByPostAndUser($post, $user);
            $myRating = $myRatingEntity ? $myRatingEntity->getValue() : null;
        }

        return $this->render('did_you_know/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'ratingStats' => $ratingStats,
            'myRating' => $myRating,
        ]);
    }

    #[Route('/did-you-know/{id}/comment', name: 'app_did_you_know_comment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function comment(
        DidYouKnowPost $post,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }

        $wantsJson = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if (!$this->isCsrfTokenValid('dyk_comment_' . $post->getId(), (string) $request->request->get('_token'))) {
            $message = 'Սխալ CSRF token։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_did_you_know_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $body = trim((string) $request->request->get('body', ''));
        if (mb_strlen($body) < 3) {
            $message = 'Մեկնաբանությունը պետք է լինի առնվազն 3 նիշ։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_did_you_know_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $user = $this->getUser();
        if (!$user instanceof AppUser) {
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            return $this->redirectToRoute('app_login');
        }

        $comment = new DidYouKnowComment();
        $comment->setPost($post);
        $comment->setUser($user);
        $comment->setBody($body);
        $comment->setIsApproved(true);

        $em->persist($comment);
        $em->flush();

        if ($wantsJson) {
            $fullName = trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
            return $this->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->getId(),
                    'userName' => $fullName !== '' ? $fullName : $user->getUserIdentifier(),
                    'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i'),
                    'body' => $comment->getBody(),
                ],
            ], 201);
        }

        $this->addFlash('success', 'Մեկնաբանությունը ավելացվեց։');
        return $this->redirectToRoute('app_did_you_know_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
    }

    #[Route('/did-you-know/{id}/rate', name: 'app_did_you_know_rate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function rate(
        DidYouKnowPost $post,
        Request $request,
        EntityManagerInterface $em,
        DidYouKnowRatingRepository $ratingRepository
    ): Response {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }

        $wantsJson = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if (!$this->isCsrfTokenValid('dyk_rate_' . $post->getId(), (string) $request->request->get('_token'))) {
            $message = 'Սխալ CSRF token։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_did_you_know_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $value = (int) $request->request->get('value', 0);
        if ($value < 1 || $value > 5) {
            $message = 'Գնահատականը պետք է լինի 1-ից 5։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_did_you_know_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $user = $this->getUser();
        if (!$user instanceof AppUser) {
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            return $this->redirectToRoute('app_login');
        }

        $rating = $ratingRepository->findOneByPostAndUser($post, $user);
        if (!$rating) {
            $rating = new DidYouKnowRating();
            $rating->setPost($post);
            $rating->setUser($user);
            $em->persist($rating);
        } else {
            $rating->setUpdatedAt(new \DateTime());
        }

        $rating->setValue($value);
        $em->flush();

        if ($wantsJson) {
            $stats = $ratingRepository->getStatsForPost($post);
            return $this->json([
                'success' => true,
                'avg' => (float) ($stats['avg'] ?? 0),
                'count' => (int) ($stats['count'] ?? 0),
                'myRating' => $value
            ]);
        }

        $this->addFlash('success', 'Շնորհակալություն գնահատականի համար։');
        return $this->redirectToRoute('app_did_you_know_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
    }
}
