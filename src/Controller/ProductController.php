<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    /**
     * @Route("/api/products", name="products")
     */
    public function getProductList(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $productsList = $productRepository->findAll();
        $jsonProductsList = $serializer->serialize($productsList, 'json', ['groups' => 'getProducts']);

        return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/product/{ref}", name="product")
     */
    public function getDetailProduct(Product $product, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();
        $qb->select('p.ref', 'p.name', 'SUM(c.quantity) as total')
            ->from('App\Entity\Cart', 'c')
            ->leftJoin('c.product', 'p')
            ->where('p.ref = :ref')
            ->groupBy('p.id')
            ->setParameter('ref', $product->getRef());
        $product = $qb->getQuery()->getResult();
        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => ['getProducts', 'getProduct']]);

        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
