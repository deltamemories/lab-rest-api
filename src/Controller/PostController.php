<?php

namespace App\Controller;

use App\Dto\CreatePostDto;
use App\Dto\UpdatePostDto;
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
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/posts')]
final class PostController extends AbstractController {

    private function getUserTag(User $user): string {
        return 'user_posts_tag_' . $user->getId();
    }

    #[Route('', name: 'api_posts_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreatePostDto $postDto,
        EntityManagerInterface $emi,
        #[CurrentUser] User $user,
        TagAwareCacheInterface $cache
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
        $cache->invalidateTags([$this->getUserTag($user)]);

        return $this->json(['id' => $post->getId()], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_posts_index', methods: ['GET'])]
    public function index(
        PostRepository $postRepo,
        #[CurrentUser] User $user,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cacheKey = 'user_posts_arr_' . $user->getId();

        $posts = $cache->get($cacheKey, function (ItemInterface $item) use ($postRepo, $user) {
            $item->tag([$this->getUserTag($user)]);

            return $postRepo->findBy(['author' => $user]);
        });

        return $this->json($posts, Response::HTTP_OK, [], [
            'groups' => 'post:read'
        ]);
    }

    #[Route('/{id}', name: 'api_posts_show', methods: ['GET'])]
    public function show(
        PostRepository $postRepo,
        TagAwareCacheInterface $cache,
        int $id
        ): JsonResponse {
        $cacheKey = 'post_item_' . $id;

        $post = $cache->get($cacheKey, function(ItemInterface $item) use ($postRepo, $id) {
            /** @var Post $postEntity */
            $postEntity = $postRepo->find($id);
            if ($postEntity) {
                $item->tag([$this->getUserTag($postEntity->getAuthor())]);
            }

            return $postEntity;
        });

        if (!$post) {
            throw $this->createNotFoundException("Can't find Post");
        }

        $this->denyAccessUnlessGranted('POST_VIEW', $post);

        return $this->json($post, context: ['groups' => 'post:read']);
    }

    #[Route('/{id}', name: 'api_posts_delete', methods: ['DELETE'])]
    public function delete(
        Post $post,
        EntityManagerInterface $emi,
        TagAwareCacheInterface $cache,
        ): JsonResponse {
        $this->denyAccessUnlessGranted('POST_DELETE', $post);

        $emi->remove($post);
        $emi->flush();
        $cache->invalidateTags([$this->getUserTag($post->getAuthor())]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_posts_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Post $post,
        #[MapRequestPayload] UpdatePostDto $postDto,
        EntityManagerInterface $emi,
        TagAwareCacheInterface $cache,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('POST_EDIT', $post);

        if ($postDto->title !== null) {
            $post->setTitle($postDto->title);
        }

        if ($postDto->content !== null) {
            $post->setContent($postDto->content);
        }

        if ($postDto->status !== null) {
            $post->setStatus($postDto->status);
        }

        $emi->flush();
        $cache->invalidateTags([$this->getUserTag($post->getAuthor())]);

        return $this->json($post, context: ['groups' => 'post:read']);
    }
}
