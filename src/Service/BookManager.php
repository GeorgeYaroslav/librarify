<?php

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookManager {

    private $em;
    private $bookRepository;

    public function __construct(
        EntityManagerInterface $em, 
        BookRepository $bookRepository
        )
    {
        $this->em = $em;
        $this->bookRepository = $bookRepository;
    }

    public function find(int $id): ?Book
    {
        return $this->bookRepository->find($id);
    }

    public function create(): Book 
    {
        $book = new Book();
        return $book;
    }

    public function persist(Book $book): Book
    {
        $this->em->persist($book);
        return $book;
    }


    public function save(Book $book): Book
    {
        $this->em->persist($book);
        $this->em->flush();
        return $book;
    }

    public function reload(Book $book)
    {
        $this->em->refresh($book);
        return $book;
    }
}