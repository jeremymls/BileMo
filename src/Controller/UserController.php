<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users/{client}", name="users")
     */
    public function getUserList(User $client, SerializerInterface $serializer): JsonResponse
    {
        $usersList = $client->getUsers();
        $jsonUsersList = $serializer->serialize($usersList, 'json', ['groups' => 'getUsers']);

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/user/{id}", name="user")
     */
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => ['getUsers','getUser']]);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }
}
