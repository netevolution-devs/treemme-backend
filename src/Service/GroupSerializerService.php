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

    public function serializeGroup($item, string $group)
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $context->setGroups(array($group));

        return json_decode($this->serializer->serialize($item,'json', $context), true);
    }
}