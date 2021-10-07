<?php
namespace App\Controller\Api;

//FOS RestBundle
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

//Request and Response
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Servicios
use App\Service\BookFormProcessor;
use App\Service\BookManager;

// FOS RestBundle
use FOS\RestBundle\View\View;


class BooksController extends AbstractFOSRestController{
    /**
     * @Rest\Get(path="/books")
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function getAction(
        BookManager $bookManager 
    ){
       return $bookManager->findAll();
    }

    /**
     * @Rest\Get(path="/books/search/{id}", requirements={"id"="\d+"})
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function getSingleAction(
        int $id,
        BookManager $bookManager
    ){
        $book = $bookManager->find($id);

        // En caso que que se ingrese un libro inexistente, se regresa una exepcion.
        if(!$book){
            return View::create('Book Not Found :(', Response::HTTP_BAD_REQUEST);
        }

        return $book;
    }

    /**
     * @Rest\Post(path="/books")
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function postAction(
        BookFormProcessor $bookFormProcessor,
        BookManager $bookManager,
        Request $request
    ){  
        $book = $bookManager->create();

        // El BookFormProcessor retorna como primer valor el book cuando todo haya salido bien, sino 
        // enviara como resultado un NULL, y el error como segundo valor
        [$book, $error] = ($bookFormProcessor)($book, $request);
        
        // Se le agrega codigo HTTP dependiendo de que si exista o no $book
        $statusCode = $book ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST;
        // Si $book no existe, entonces $data toma el valor $error
        $data = $book ?? $error;

        // Se crea una vista dandole los valores de lo uqe llega por el formulario
        return View::create($data, $statusCode);
        
    }

    /**
     * @Rest\Post(path="/books/{id}", requirements={"id"="\d+"})
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function editAction(
        int $id,
        BookFormProcessor $bookFormProcessor,
        BookManager $bookManager,
        Request $request
    ){
        
        // Recogemos el id que llega por la url, y buscamos el libro que coincida
        $book = $bookManager->find($id);

        // En caso que que se ingrese un libro inexistente, se regresa una exepcion.
        if(!$book){
            return View::create('Book Not Found :(', Response::HTTP_BAD_REQUEST);
        }

         // El BookFormProcessor retorna como primer valor el book cuando todo haya salido bien, sino 
        // enviara como resultado un NULL, y el error como segundo valor
        [$book, $error] = ($bookFormProcessor)($book, $request);
        
        // Se le agrega codigo HTTP dependiendo de que si exista o no $book
        $statusCode = $book ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST;
        // Si $book no existe, entonces $data toma el valor $error
        $data = $book ?? $error;

        // Se crea una vista dandole los valores de lo uqe llega por el formulario
        return View::create($data, $statusCode);

    }

    /**
     * @Rest\Delete(path="/books/{id}", requirements={"id"="\d+"})
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function deleteAction(
        int $id,
        BookManager $bookManager,
        Request $request
    ){  
        $book = $bookManager->find($id);   

        if (!$book) {
            return View::create('Book not found', Response::HTTP_BAD_REQUEST);

        }

        $bookManager->delete($book);
        return View::create(null, Response::HTTP_NO_CONTENT);
        
    }

}











?>