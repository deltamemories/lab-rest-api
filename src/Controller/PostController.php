<?php

namespace App\Controller;

use App\Dto\CreatePostDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route('/api/posts')]
final class PostController extends AbstractController {
    #[Route('', name: 'api_posts_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreatePostDto $postDto,
        EntityManagerInterface $emi,
        #[CurrentUser] User $user
    ): JsonResponse {
        $post = new Post();

        $post->setTitle($postDto->title);
        $post->setContent($postDto->content);
        
        if ($postDto->status !== null) {
            $post->setStatus($postDto->status);
        }
        
        $post->setAuthor($user);

        $emi->persist($post);
        $emi->flush();

        return $this->json(['id' => $post->getId()], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_posts_index', methods: ['GET'])]
    public function index(
        PostRepository $postRepo,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $posts = $postRepo->findBy(['author' => $user]);

        return $this->json($posts, Response::HTTP_OK, [], [
            'groups' => 'post:read'
        ]);
    }

    #[Route('/{id}', name: 'api_posts_show', methods: ['GET'])]
    public function show(Post $post): JsonResponse {
        $this->denyAccessUnlessGranted('POST_VIEW', $post);

        return $this->json($post, context: ['groups' => 'post:read']);
    }

    #[Route('/{id}', name: 'api_posts_delete', methods: ['DELETE'])]
    public function delete(Post $post, EntityManagerInterface $emi): JsonResponse {
        $this->denyAccessUnlessGranted('POST_DELETE', $post);

        $emi->remove($post);
        $emi->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
