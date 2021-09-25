<?php
namespace App\Controller\Api;

use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\Form\Type\BookFormType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Book;

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
        Request $request
    ){
        $book = new Book();
        $form = $this->createForm(BookFormType::class, $book);
        $form->handleRequest($request);

        $json = [
            'boy' => $request->query->get('boy'),
            'book' => $request->query->get('book_form')
        ];
        $json = json_encode($json);
        return $json;
        // if($form->isSubmitted() && $form->isValid()){
        //     $em->persist($book);
        //     $em->flush();
        //     return $book;
        // }

        //     return $form;
    }
}











?>