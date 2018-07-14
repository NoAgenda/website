<?php
/*
 * (c) Tim Goudriaan <tim@codedmonkey.com>
 */

namespace App\Serializer;

use App\Entity\ChatMessage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ChatMessageNormalizer implements NormalizerInterface
{
    /**
     * @param ChatMessage $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [
            $object->getUsername(),
            nl2br($object->getContents()),
            $object->getPostedAt(),
            $object->getSource(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ChatMessage;
    }
}
