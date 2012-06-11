<?php

namespace Dodici\Fansworld\WebBundle\Model;

/**
 * Element that can be visited
 */
interface VisitableInterface
{
    public function addVisit(\Dodici\Fansworld\WebBundle\Entity\Visit $visit);
    public function setVisitCount($visitCount);
    public function getVisitCount();
}
