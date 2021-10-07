<?php

namespace App\Service;

use App\Entity\Book;
use App\Form\Model\BookDto;
use App\Form\Model\CategoryDto;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\BookManager;
use App\Service\CategoryManager;
use App\Service\FileUploader;
use Doctrine\Common\Collections\ArrayCollection;
use App\Form\Type\BookFormType;


class BookFormProcessor
{

    private $bookManager;
    private $categoryManager;
    private $fileUploader;
    private $formFactoryInterface;
    

    public function __construct(
        BookManager $bookManager,
        CategoryManager $categoryManager,
        FileUploader $fileUploader,
        FormFactoryInterface $formFactoryInterface
    ) {
        $this->bookManager = $bookManager;
        $this->categoryManager = $categoryManager;
        $this->fileUploader = $fileUploader;
        $this->formFactoryInterface = $formFactoryInterface;
    }

    public function __invoke(Book $book, Request $request): array
    {


        // Se rellena el dto de book, obteniendo el libro de la entidad
        $bookDto = BookDto::createFromBook($book);

        // Se crea una coleccion de arrays para guardar las categorias
        $originalCategories = new ArrayCollection();

        // Se recorren todas las categorias relacionadas con el libro
        foreach ($book->getCategories() as $category) {
            // Se rellena el dto de categoria, solo aquella que se relacione con el libro
            $categoryDto = CategoryDto::createFromCategory($category);
            // Las categorias obtenidas del libro, se colocan en un array en el bookDto
            $bookDto->categories[] = $categoryDto;
            // Ahora las categorias tambien se introducen en el array collection para su uso en el controlador.
            $originalCategories->add($categoryDto);
        }


        // Se crea el formulario a base del bookDto 
        $form = $this->formFactoryInterface->create(BookFormType::class, $bookDto);
        // Los datos enviados por la request se agregan al dto
        $form->handleRequest($request);


        // Si el formulario nos es enviado entonces se muestra una bad request
        if (!$form->isSubmitted()) {
            return [null, 'Form is not submitted'];
        }

        // Si el formulario para todas las validaciones
        if ($form->isValid()) {

            // Remove categories
            foreach ($originalCategories as $originalCategoryDto) {

                // Si el contenido del recien submeteado categories de bookDto no se encuentra en la 
                //categoria original, entonces se eliminara.
                if (!in_array($originalCategoryDto, $bookDto->categories)) {
                    // saca el id desde el repositorio de la categoria que se va a eliminar
                    $category = $this->categoryManager->find($originalCategoryDto->id);
                    // Aqui se envia el id de la categoria a eliminar a la clase removCategory de book
                    $book->removeCategory($category);

                    // NOTA: solo se eliminara la relacion entre la categoria y el book, pero la categoria sigue existiendo
                }
            }

            // Add categories
            // Se recoje la categorias submetedas
            foreach ($bookDto->categories as $newCategoryDto) {
                // Si la categoria submeteada se encuentra dentro de las originales
                if (!$originalCategories->contains($newCategoryDto)) {      

                    // Entonces tambien se busca dentro del repositorio, y si no, manda 0 como valor
                    $category = $this->categoryManager->find($newCategoryDto->id ?? 0);

                    // Si la categoria submeteada no se encuentra en el repositorio
                    if (!$category) {
                        // Se crea una instancia de la entidad category
                        $category = $this->categoryManager->create();
                        // Se setea el nombre de la categoria
                        $category->setName($newCategoryDto->name);
                        // Y se persiste para luego enviarse en un flush
                        $this->categoryManager->persist($category);
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
                $filename = $this->fileUploader->uploadBase64File($bookDto->base64Image);
                $book->setImage($filename);
            }
            // Se hace un flush para guardarlo
            $this->bookManager->save($book);
            // Se refresca el entity manaager
            $this->bookManager->reload($book);
            // Se retorna un objeto libro
            return [$book, null];
        }
        return [null, $form];
    }
}
