<?php

namespace App\Service;


use App\Entity\Category;

use App\Form\Model\CategoryDto;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\CategoryManager;
use App\Form\Type\CategoryFormType;
use Symfony\Component\HttpFoundation\Response;

class CategoryFormProcessor
{
    private $categoryManager;
    private $formFactoryInterface;
    

    public function __construct(
        CategoryManager $categoryManager,
        FormFactoryInterface $formFactoryInterface
    ) {
        $this->categoryManager = $categoryManager;
        $this->formFactoryInterface = $formFactoryInterface;
    }

    public function __invoke(Category $category, Request $request): array
    {

              // Se rellena el dto de book, obteniendo el libro de la entidad
              $categoryDto = CategoryDto::createFromCategory($category);

              // Se crea el formulario a base del bookDto 
              $form = $this->formFactoryInterface->create(CategoryFormType::class, $categoryDto);
              // Los datos enviados por la request se agregan al dto
              $form->handleRequest($request);
      
      
              // Si el formulario nos es enviado entonces se muestra una bad request
              if (!$form->isSubmitted()) {
                  return [null, 'Form is not submitted'];
              }
      
              // Si el formulario para todas las validaciones
              if ($form->isValid()) {
         
                  // Se setea del titulo con el que llega del formulario
                  $category->setName($categoryDto->name);

                  // Se hace un flush para guardarlo
                  $this->categoryManager->save($category);
                  // Se refresca el entity manaager
                  $this->categoryManager->reload($category);
                  // Se retorna un objeto libro
                  return [$category, null];
              }
              return [null, $form];
        }
}