<?php

namespace App\Controller;

use App\Dto\CreateUserDto;
use App\Entity\User;
use App\Message\UserCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus,
        #[MapRequestPayload] CreateUserDto $userDto
    ): JsonResponse {
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $userDto->email]);
        if ($existingUser) {
            return new JsonResponse(
                ['error' => "User with this email already exists"],
                Response::HTTP_CONFLICT
            );
        }

        $user = new User();

        $user->setEmail($userDto->email);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $userDto->password
        );

        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();
        $bus->dispatch(new UserCreatedMessage($user->getId()));

        return new JsonResponse(
            ['status' => 'User created'],
            Response::HTTP_CREATED
        );
    }
}
