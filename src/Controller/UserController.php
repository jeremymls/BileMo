<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use  Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users/{client}", name="users")
     * @IsGranted("ROLE_CLIENT")
     */
    public function getUserList(User $client, SerializerInterface $serializer, UserRepository $userRepository, Request $request): JsonResponse
    {
        if ($this->getUser() !== $client) {
            throw new HttpException(403, "Vous n'êtes pas autorisé à accéder à cette ressource.");
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $usersList = $userRepository->findAllWithPagination($page, $limit, $client);
        $jsonUsersList = $serializer->serialize($usersList, 'json', ['groups' => 'getUsers']);

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/user/{id}", name="user")
     * @IsGranted("ROLE_CLIENT")
     */
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        if ($this->getUser() !== $user->getClient() && $this->getUser() !== $user) {
            throw new HttpException(403, "Vous n'êtes pas autorisé à accéder à cette ressource.");
        }

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => ['getUsers','getUser']]);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/user/{client}", name="create_user", methods={"POST"}, priority=10)
     * @IsGranted("ROLE_CLIENT")
     */
    public function createUser(
        User $client,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordEncoder
    ): JsonResponse {
        if ($this->getUser() !== $client) {
            throw new HttpException(403, "Vous n'êtes pas autorisé à ajouté un utilisateur à ce client.");
        }

        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $data = ["status" => 400];
            $data["message"] = (count($errors) > 1) ? "Requête invalide" : $errors[0]->getMessage();
            if (count($errors) > 1) {
                foreach ($errors as $error) {
                    $data["errors"][] = $error->getMessage();
                }
            }
            return new JsonResponse($serializer->serialize($data, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        if ($user->getPassword()) {
            $user->setPassword($passwordEncoder->hashPassword($user, $user->getPassword()));
        } 
        $user->setRoles(['ROLE_USER']);
        $user->setClient($client);
        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => ['getUsers', 'createUser']]);

        $location = $urlGenerator->generate('user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * @Route("/api/user/{id}", name="delete_user", methods={"DELETE"}, priority=10)
     */
    public function deleteUser(User $user, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
