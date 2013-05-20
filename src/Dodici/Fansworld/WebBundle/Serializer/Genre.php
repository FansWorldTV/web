<?php

namespace Dodici\Fansworld\WebBundle\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Genre serializer
 */
class Genre
{

    protected $em;

    function __construct($em)
    {
        $this->em = $em;
    }


    public function values($entity)
    {

        $genreRepo = $this->em->getRepository('DodiciFansworldWebBundle:Genre');
        $type = $entity->getType();
        $childrenArray = array();

        if ('genre' == $type) {
            $children = $genreRepo->getChildren($entity);
            $childrenCount = count($children);

            $childrenInfo = array();
            foreach ($children as $achildren) {
                $childrenInfo['id'] = $achildren->getId();
                $childrenInfo['title'] = $achildren->getTitle();
                $childrenInfo['type'] = $achildren->getType();
                $childrenArray[] = $childrenInfo;
            }
        }


        return array(
            'type' => $type,
            'children' => $childrenArray
        );
    }
}