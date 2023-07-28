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

class ProductController extends AbstractController
{
    /**
     * @Route("/api/products", name="products")
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
     * @Route("/api/product/{ref}", name="product")
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
