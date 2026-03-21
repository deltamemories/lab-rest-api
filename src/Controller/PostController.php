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
}
