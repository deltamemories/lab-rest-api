<?php

namespace App\Controller;

use App\Dto\CreateCommentDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/comments')]
final class CommentController extends AbstractController {
    #[Route('', name: 'api_comments_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateCommentDto $commentDto,
        EntityManagerInterface $emi,
        PostRepository $postRepo,
        #[CurrentUser] User $user
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

        return $this->json(['id' => $comment->getId()], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_comments_index', methods: ['GET'])]
    public function index(
        CommentRepository $commentRepo,
        #[CurrentUser] User $user
    ) {
        $comments = $commentRepo->findBy(['author' => $user]);

        return $this->json($comments, Response::HTTP_OK, [], [
            'groups' => 'comment:read'
        ]);
    }

    #[Route('/{id}', name: 'api_comments_show', methods: ['GET'])]
    public function show(Comment $comment): JsonResponse {
        $this->denyAccessUnlessGranted('COMMENT_VIEW', $comment);

        return $this->json($comment, context: ['groups' => 'comment:read']);
    }
}
