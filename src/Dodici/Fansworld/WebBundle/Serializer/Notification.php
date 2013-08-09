<?php

namespace Dodici\Fansworld\WebBundle\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Notification serializer
 */
class Notification
{
    protected $serializer;

    function __construct($serializer)
    {
        $this->serializer = $serializer;
    }

    public function values($entity)
    {
        $typeParent = $entity->getTypeParent();
        switch ($typeParent) {
            case 'videos':
                $method = "getVideo";
                break;
            case 'photos':
                $method = "getPhoto";
                break;
            case 'fans':
                $method = "getFriendShip";
                break;
            default:
                $method = false;
                break;
        }
        return array(
            'type' => $entity->getTypeName(),
            'parent' => $typeParent,
            'author' => $this->serializer->values($entity->getAuthor()),
            'entity' => $this->serializer->values($entity->$method())
        );
    }
}