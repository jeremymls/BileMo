<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer la liste des produits
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des produits",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Product::class, groups={"getProducts"}))
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
     * @OA\Tag(name="Product")
     * 
     * @Route("/api/products", name="products", methods={"GET"})
     */
    public function getProductList(
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);

        $idCache = 'product_list_' . $page . '_' . $limit;
        $jsonProductsList = $cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
            $item->tag('product_list');
            $context = SerializationContext::create()->setGroups(['getProducts']);
            $productsList = $productRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($productsList, 'json', $context);
        });

        return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet de récupérer le détail d'un produit
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne le détail d'un produit",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Product::class, groups={"getProduct"}))
     *      )
     * )
     * @OA\Tag(name="Product")
     * 
     * @Route("/api/product/{ref}", name="product", methods={"GET"})
     */
    public function getDetailProduct(Product $product, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();
        $qb->select('p.ref', 'p.name', 'SUM(c.quantity) as total_commands')
            ->from('App\Entity\Cart', 'c')
            ->leftJoin('c.product', 'p')
            ->where('p.ref = :ref')
            ->groupBy('p.id')
            ->setParameter('ref', $product->getRef());
        $product = $qb->getQuery()->getResult();
        $context = SerializationContext::create()->setGroups(['getProduct, getProducts']);
        $jsonProduct = $serializer->serialize($product, 'json', $context);

        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
