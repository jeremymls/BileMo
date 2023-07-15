<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
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
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'getProducts']);

        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
