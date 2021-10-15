<?php

namespace App\Controller\Api;

use App\Form\Model\CategoryDto;
use App\Service\CategoryFormProcessor;
use App\Service\CategoryManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;


class CategoryController extends AbstractFOSRestController
{
    /**
     * @Rest\Get(path="/categories")
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function getAction(
        CategoryManager $categoryManager 
    ){
       return $categoryManager->findAll();
    }

    /**
     * @Rest\Post(path="/categories")
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function postAction(
        CategoryFormProcessor $categoryFormProcessor,
        CategoryManager $categoryManager,
        Request $request
    ){  
        $category = $categoryManager->create();

        // El BookFormProcessor retorna como primer valor el book cuando todo haya salido bien, sino 
        // enviara como resultado un NULL, y el error como segundo valor
        [$category, $error] = ($categoryFormProcessor)($category, $request);
        
        // Se le agrega codigo HTTP dependiendo de que si exista o no $book
        $statusCode = $category ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST;
        // Si $book no existe, entonces $data toma el valor $error
        $data = $category ?? $error;

        // Se crea una vista dandole los valores de lo uqe llega por el formulario
        return View::create($data, $statusCode);
        
    }

}