<?php

namespace App\Service;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;


class GroupSerializerService
{
    private SerializerInterface $serializer;


    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serializeGroup($item, string|array $group)
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        if (is_string($group)) {
            $group = array($group);
        }

        $context->setGroups($group);

        return json_decode($this->serializer->serialize($item,'json', $context), true);
    }
}