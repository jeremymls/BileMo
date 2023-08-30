<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use  Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class UserController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer la liste des utilisateurs d'un client
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des utilisateurs",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="La page que l'on veut afficher",
     *      @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Le nombre d'éléments que l'on veut récupérer",
     *      @OA\Schema(type="int")
     * )
     * @OA\Tag(name="User")
     * 
     * @Route("/api/users", name="users", methods={"GET"})
     * @IsGranted("ROLE_CLIENT")
     */
    public function getUserList(
        SerializerInterface $serializer,
        UserRepository $userRepository,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        if ($this->isGranted(["ROLE_CLIENT"])) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Vous n'êtes pas autorisé à accéder à cette ressource.");
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $client = $this->getUser();

        $idCache = 'user_list_' . $page . '_' . $limit;
        $jsonUsersList = $cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $page, $limit, $client, $serializer) {
            $item->tag('user_list');
            $context = SerializationContext::create()->setGroups(['getUsers']);
            $usersList = $userRepository->findAllWithPagination($page, $limit, $client);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet de récupérer la liste des utilisateurs d'un client
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des utilisateurs",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *      )
     * )
     * @OA\Tag(name="User")
     * 
     * @Route("/api/user/{id}", name="user", methods={"GET"})
     * @IsGranted("ROLE_CLIENT")
     */
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        if ($this->getUser() !== $user->getClient()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Vous n'êtes pas autorisé à accéder à cette ressource.");
        }

        $context = SerializationContext::create()->setGroups(['getUsers', 'getUser']);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet de créer un utilisateur
     * 
     * @OA\Response(
     *      response=201,
     *      description="Création d'un utilisateur",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=User::class, groups={"getUsers", "createUser"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="user",
     *      in="header",
     *      description="L'utilisateur à créer",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=User::class, groups={"getUsers", "createUser"}))
     *      )
     * )
     * @OA\Tag(name="User")
     * 
     * @Route("/api/user", name="create_user", methods={"POST"}, priority=10)
     * @IsGranted("ROLE_CLIENT")
     */
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordEncoder,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        if ($this->isGranted(["ROLE_CLIENT"])) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Vous n'êtes pas autorisé à ajouter un utilisateur.");
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
        $user->setClient($this->getUser());
        $em->persist($user);
        $em->flush();

        $cachePool->invalidateTags(['user_list']);

        $context = SerializationContext::create()->setGroups(['getUsers', 'createUser']);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        $location = $urlGenerator->generate('user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * Cette méthode permet de supprimer un utilisateur
     * 
     * @OA\Response(
     *      response=204,
     *      description="Suppression d'un utilisateur"
     * )
     * @OA\Tag(name="User")
     * 
     * @Route("/api/user/{user}", name="delete_user", methods={"DELETE"}, priority=10)
     */
    public function deleteUser(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ( $this->getUser() !== $user->getClient() ) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Vous n'êtes pas autorisé à supprimer cette ressource.");
        }

        $cachePool->invalidateTags(['user_list']);

        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Cette méthode permet de modifier un utilisateur
     * 
     * @OA\Response(
     *      response=204,
     *      description="Modification d'un utilisateur"
     * )
     * @OA\Tag(name="User")
     * 
     * @Route("/api/user/{id}", name="update_user", methods={"PUT"}, priority=10)
     */
    public function updateUser(
        Request $request,
        User $user,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordEncoder,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        if ($this->getUser() !== $user->getClient()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Vous n'êtes pas autorisé à accéder à cette ressource.");
        }

        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');

        if ($newUser->getEmail() === null || $newUser->getPassword() === null) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "Données manquantes");
        }

        $user->setEmail($newUser->getEmail());
        $user->setPassword($passwordEncoder->hashPassword($user, $newUser->getPassword()));

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->flush();

        $cache->invalidateTags(['user_list']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
