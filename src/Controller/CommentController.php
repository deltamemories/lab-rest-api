<?php

namespace App\Controller;

use App\Dto\CreateCommentDto;
use App\Dto\UpdateCommentDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Post;
use App\Message\CommentCreatedMessage;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/comments')]
final class CommentController extends AbstractController {

    private function getUserTag(User $user): string {
        return 'user_comments_tag_' . $user->getId();
    }

    private function getPostTag(Post $post): string {
        return 'post_comments_tag_' . $post->getId();
    }

    #[Route('', name: 'api_comments_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateCommentDto $commentDto,
        EntityManagerInterface $emi,
        PostRepository $postRepo,
        #[CurrentUser] User $user,
        TagAwareCacheInterface $cache,
        MessageBusInterface $bus
    ): JsonResponse {
        $post = $postRepo->findOneBy(['id' => $commentDto->post]);
        if ($post === null) {
            return $this->json(['error' => "Can't find post with this id"], Response::HTTP_NOT_FOUND);
        }

        $comment = new Comment();

        $comment->setContent($commentDto->content);
        $comment->setPost($post);
        $comment->setAuthor($user);

        $emi->persist($comment);
        $emi->flush();
        $bus->dispatch(new CommentCreatedMessage($comment->getId()));
        $cache->invalidateTags([
            $this->getUserTag($user),
            $this->getPostTag($comment->getPost())
        ]);

        return $this->json(['id' => $comment->getId()], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_comments_index', methods: ['GET'])]
    public function index(
        CommentRepository $commentRepo,
        #[CurrentUser] User $user,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cacheKey = 'user_comments_arr_' . $user->getId();

        $comments = $cache->get($cacheKey, function (ItemInterface $item) use ($commentRepo, $user) {
            $item->tag([$this->getUserTag($user)]);

            return $commentRepo->findBy(['author' => $user]);
        });

        return $this->json($comments, Response::HTTP_OK, [], [
            'groups' => 'comment:read'
        ]);
    }

    #[Route('/{id}', name: 'api_comments_show', methods: ['GET'])]
    public function show(
        CommentRepository $commentRepo,
        TagAwareCacheInterface $cache,
        int $id
    ): JsonResponse {
        $cacheKey = 'comment_item_' . $id;

        $comment = $cache->get($cacheKey, function(ItemInterface $item) use ($commentRepo, $id) {
            /** @var Comment $commentEntity */
            $commentEntity = $commentRepo->find($id);
            if ($commentEntity) {
                $item->tag([
                    $this->getUserTag($commentEntity->getAuthor()),
                    $this->getPostTag($commentEntity->getPost())
                ]);
            }

            return $commentEntity;
        });

        if (!$comment) {
            throw $this->createNotFoundException("Can't find Comment");
        }

        $this->denyAccessUnlessGranted('COMMENT_VIEW', $comment);

        return $this->json($comment, context: ['groups' => 'comment:read']);
    }

    #[Route('/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function delete(
        Comment $comment,
        EntityManagerInterface $emi,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('COMMENT_DELETE', $comment);

        $emi->remove($comment);
        $emi->flush();
        $cache->invalidateTags([
            $this->getUserTag($comment->getAuthor()),
            $this->getPostTag($comment->getPost())
        ]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_comments_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Comment $comment,
        #[MapRequestPayload] UpdateCommentDto $commentDto,
        EntityManagerInterface $emi,
        PostRepository $postRepo,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('COMMENT_EDIT', $comment);

        if ($commentDto->content !== null) {
            $comment->setContent($commentDto->content);
        }

        if ($commentDto->post !== null) {
            $post = $postRepo->findOneBy(['id' => $commentDto->post]);
            if ($post === null) {
                return $this->json(['error' => "New Post with this id not found"], Response::HTTP_NOT_FOUND);
            }
            $comment->setPost($post);
        }

        $emi->flush();
        $cache->invalidateTags([
            $this->getUserTag($comment->getAuthor()),
            $this->getPostTag($comment->getPost())
        ]);

        return $this->json($comment, context: ['groups' => 'comment:read']);
    }
}
