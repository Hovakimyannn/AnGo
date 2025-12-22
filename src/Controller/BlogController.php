<?php

namespace App\Controller;

use App\Entity\ArtistPost;
use App\Entity\PostComment;
use App\Entity\PostRating;
use App\Entity\User as AppUser;
use App\Repository\ArtistPostRepository;
use App\Repository\PostCommentRepository;
use App\Repository\PostRatingRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'app_blog_index')]
    #[Route('/blog/{category}', name: 'app_blog_category', requirements: ['category' => 'hair|nails|makeup'], defaults: ['category' => null])]
    public function index(
        ?string $category,
        Request $request,
        ArtistPostRepository $postRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $serviceId = $request->query->getInt('service') ?: null;

        $posts = $postRepository->findPublishedByCategory($category, $serviceId);

        $services = $category
            ? $serviceRepository->findBy(['category' => $category], ['name' => 'ASC'])
            : $serviceRepository->findAll();

        $categoryLabels = [
            'hair' => 'Վարսահարդարում',
            'nails' => 'Մատնահարդարում',
            'makeup' => 'Դիմահարդարում',
        ];

        return $this->render('blog/index.html.twig', [
            'posts' => $posts,
            'category' => $category,
            'categoryLabel' => $category ? ($categoryLabels[$category] ?? $category) : 'Բոլորը',
            'services' => $services,
            'selectedServiceId' => $serviceId,
        ]);
    }

    #[Route('/blog/post/{id}-{slug}', name: 'app_blog_show', requirements: ['id' => '\d+'])]
    public function show(
        ArtistPost $post,
        string $slug,
        PostCommentRepository $commentRepository,
        PostRatingRepository $ratingRepository,
    ): Response {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }

        // Enforce canonical slug (prevents duplicate URLs like /blog/post/123-wrong-slug).
        $canonicalSlug = (string) $post->getSlug();
        if ($canonicalSlug !== '' && $slug !== $canonicalSlug) {
            return $this->redirectToRoute('app_blog_show', [
                'id' => $post->getId(),
                'slug' => $canonicalSlug,
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        $comments = $commentRepository->findApprovedForPost($post, 200);
        $ratingStats = $ratingRepository->getStatsForPost($post);

        $myRating = null;
        $user = $this->getUser();
        if ($user instanceof AppUser) {
            $myRatingEntity = $ratingRepository->findOneByPostAndUser($post, $user);
            $myRating = $myRatingEntity?->getValue();
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'ratingStats' => $ratingStats,
            'myRating' => $myRating,
        ]);
    }

    #[Route('/blog/post/{id}/comment', name: 'app_blog_comment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function comment(
        ArtistPost $post,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }

        $wantsJson = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if (!$this->isCsrfTokenValid('blog_comment_' . $post->getId(), (string) $request->request->get('_token'))) {
            $message = 'Սխալ CSRF token։ Խնդրում ենք կրկին փորձել։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_blog_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $body = trim((string) $request->request->get('body', ''));
        if (mb_strlen($body) < 3 || mb_strlen($body) > 2000) {
            $message = 'Մեկնաբանությունը պետք է լինի 3-ից 2000 նիշ։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_blog_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $user = $this->getUser();
        if (!$user instanceof AppUser) {
            if ($wantsJson) {
                return $this->json([
                    'success' => false,
                    'error' => 'Մուտք գործեք՝ մեկնաբանելու համար',
                    'redirect' => $this->generateUrl('app_login'),
                ], 401);
            }
            return $this->redirectToRoute('app_login');
        }

        $comment = new PostComment();
        $comment->setPost($post);
        $comment->setUser($user);
        $comment->setBody($body);
        $comment->setIsApproved(true); // default; admin can later moderate

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
        return $this->redirectToRoute('app_blog_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
    }

    #[Route('/blog/post/{id}/rate', name: 'app_blog_rate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function rate(
        ArtistPost $post,
        Request $request,
        EntityManagerInterface $em,
        PostRatingRepository $ratingRepository,
    ): Response {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }

        $wantsJson = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if (!$this->isCsrfTokenValid('blog_rate_' . $post->getId(), (string) $request->request->get('_token'))) {
            $message = 'Սխալ CSRF token։ Խնդրում ենք կրկին փորձել։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_blog_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $value = (int) $request->request->get('value', 0);
        if ($value < 1 || $value > 5) {
            $message = 'Գնահատականը պետք է լինի 1-ից 5։';
            if ($wantsJson) {
                return $this->json(['success' => false, 'error' => $message], 400);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_blog_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
        }

        $user = $this->getUser();
        if (!$user instanceof AppUser) {
            if ($wantsJson) {
                return $this->json([
                    'success' => false,
                    'error' => 'Մուտք գործեք՝ գնահատելու համար',
                    'redirect' => $this->generateUrl('app_login'),
                ], 401);
            }
            return $this->redirectToRoute('app_login');
        }

        $rating = $ratingRepository->findOneByPostAndUser($post, $user);
        if (!$rating) {
            $rating = new PostRating();
            $rating->setPost($post);
            $rating->setUser($user);
            $rating->setCreatedAt(new \DateTime());
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
                'myRating' => $value,
            ]);
        }

        $this->addFlash('success', 'Շնորհակալություն գնահատականի համար։');
        return $this->redirectToRoute('app_blog_show', ['id' => $post->getId(), 'slug' => $post->getSlug()]);
    }
}


