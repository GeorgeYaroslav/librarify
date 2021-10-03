<?php
namespace App\Controller\Api;

// Dto's
use App\Form\Model\CategoryDto;
use App\Form\Model\BookDto;

// Repositories
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;

//Form's types
use App\Form\Type\BookFormType;

//FOS RestBundle
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

// Entity Manager
use Doctrine\ORM\EntityManagerInterface;

//Request and Response
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Entities
use App\Entity\Book;
use App\Entity\Category;

// Servicios
use App\Service\FileUploader;
use App\Service\BookManager;
use App\Service\CategoryManager;

// Array Collections
use Doctrine\Common\Collections\ArrayCollection;


class BooksController extends AbstractFOSRestController{
    /**
     * @Rest\Get(path="/books")
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function getAction(
        BookRepository $bookRepository 
    ){
       return $bookRepository->findAll();
    }

    /**
     * @Rest\Post(path="/books")
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function postAction(
        EntityManagerInterface $em,
        Request $request,
        FileUploader $fileUploader
    ){
        $bookDto = new BookDto();
        $form = $this->createForm(BookFormType::class, $bookDto);
        $form->handleRequest($request);

            // Para cuando no llegue ningun json no lo procese como 200
            if (!$form->isSubmitted()) {
                return new Response('', Response::HTTP_BAD_REQUEST);
            }

         if($form->isValid()){
            $book = new Book();
            $book->setTitle($bookDto->title);

            // Por si el base64 llega vacio
            if($bookDto->base64Image){
                $filename = $fileUploader->uploadBase64File($bookDto->base64Image);
                $book->setImage($filename);
            } 
             $em->persist($book);
             $em->flush();
             return $book;
         }

            return $form;
    }

    /**
     * @Rest\Post(path="/books/{id}", requirements={"id"="\d+"})
     * @Rest\View(serializerGroups={"book"}, serializerEnableMaxDepthChecks=true)
     */
    public function editAction(
        int $id,
        EntityManagerInterface $em,
        BookManager $bookManager,
        CategoryManager $categoryManager,
        Request $request,
        FileUploader $fileUploader
    ){
        
        // Recogemos el id que llega por la url, y buscamos el libro que coincida
        $book = $bookManager->find($id);

        // En caso que que se ingrese un libro inexistente, se regresa una exepcion.
        if(!$book){
            throw $this->createNotFoundException('Book Not Found :(');
        }

        // Se rellena el dto de book, obteniendo el libro de la entidad
        $bookDto = BookDto::createFromBook($book);

        // Se crea una coleccion de arrays para guardar las categorias
        $originalCategories = new ArrayCollection();
        
        // Se recorren todas las categorias relacionadas con el libro
        foreach ($book->getCategories() as $category){
            // Se rellena el dto de categoria, solo aquella que se relacione con el libro
            $categoryDto = CategoryDto::createFromCategory($category);
            // Las categorias obtenidas del libro, se colocan en un array en el bookDto
            $bookDto->categories[] = $categoryDto;
            // Ahora las categorias tambien se introducen en el array collection para su uso en el controlador.
            $originalCategories->add($categoryDto);
        }
        
        
        // Se crea el formulario a base del bookDto 
        $form = $this->createForm(BookFormType::class, $bookDto);
        // Los datos enviados por la request se agregan al dto
        $form->handleRequest($request);


        // Si el formulario nos es enviado entonces se muestra una bad request
        if (!$form->isSubmitted()) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        // Si el formulario para todas las validaciones
        if ($form->isValid()) {

            // Remove categories
            foreach ($originalCategories as $originalCategoryDto){
                
                // Si el contenido del recien submeteado categories de bookDto no se encuentra en la 
                //categoria original, entonces se eliminara.
                if (!in_array($originalCategoryDto, $bookDto->categories)){
                    // saca el id desde el repositorio de la categoria que se va a eliminar
                    $category = $categoryManager->find($originalCategoryDto->id);
                    // Aqui se envia el id de la categoria a eliminar a la clase removCategory de book
                    $book->removeCategory($category);

                    // NOTA: solo se eliminara la relacion entre la categoria y el book, pero la categoria sigue existiendo
                } 
            }

            // Add categories
            // Se recoje la categorias submetedas
            foreach ($bookDto->categories as $newCategoryDto){
                // Si la categoria submeteada se encuentra dentro de las originales
                if (!$originalCategories->contains($newCategoryDto)){
                  
                    // Entonces tambien se busca dentro del repositorio, y si no, manda 0 como valor
                    $category = $categoryRepository->find($newCategoryDto->id ?? 0);

                    // Si la categoria submeteada no se encuentra en el repositorio
                    if (!$category) {
                        // Se crea una instancia de la entidad category
                        $category = new Category();
                        // Se setea el nombre de la categoria
                        $category->setName($newCategoryDto->name);
                        // Y se persiste para luego enviarse en un flush
                        $em->persist($category);
                    }
                    
                    // si la categoria ya existe, o se ha creado una nueva de igual manera se envia el resultado
                    $book->addCategory($category);
                }
            }

            // Se setea del titulo con el que llega del formulario
            $book->setTitle($bookDto->title);

            // Se setea la imagen con la que llega del formulario
            if ($bookDto->base64Image) {
                // Aqui se realiza el proceso de decodificacion
                $filename = $fileUploader->uploadBase64File($bookDto->base64Image);
            }
            $book->setImage($filename);
            // Se persiste la entidad book
            $em->persist($book);
            // Se hace un flush para guardarlo
            $em->flush();
            // Se refresca el entity manaager
            $em->refresh($book);
            // Se retorna un objeto libro
            return $book;

        }

        return $form;
    }
}











?>