<?php
namespace App\Serializer;

use App\Entity\Book;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BookNormalizer implements ContextAwareNormalizerInterface
{
    private $urlHelper;
    private $normalizer;

    public function __construct(UrlHelper $urlHelper, ObjectNormalizer $normalizer)
    {
        $this->urlHelper = $urlHelper;
        $this->normalizer = $normalizer;
    }

    public function normalize($book, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($book, $format, $context);

        // Here, add, edit, or delete some data:
        if(!empty($book->getImage())){

        $data['image'] = $this->urlHelper->getAbsoluteUrl('/storage/default/'.$book->getImage());
    }	

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Book;
    }
}