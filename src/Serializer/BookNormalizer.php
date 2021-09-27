<?php
namespace App\Serializer;

use App\Entity\Book;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BookNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($book, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($book, $format, $context);

        // Here, add, edit, or delete some data:
        if(!empty($book->getImage())){

        $data['image'] = $this->router->generateUrl('storage\default', [
            'id' => $book->getImage(),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);
    }	

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Book;
    }
}