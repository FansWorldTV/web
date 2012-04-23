<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Complaint;
use Symfony\Component\HttpFoundation\Request;

/**
 * Comment controller.
 */
class ComplaintController extends SiteController
{

    /**
     * @Route("/ajax/report", name= "complaint_make")
     * @Template
     */
    public function ajaxReportAction()
    {
        $request = $this->getRequest();
        $author = $this->get('security.context')->getToken()->getUser();
        $entityType = $request->get('type', false);
        $entityId = $request->get('id', false);
        $categoryId = $request->get('category', false);
        $comment = $request->get('comment', null);

        $response = array(
            'error' => true
        );

        if ($entityType) {
            try {
                $entity = $this->getRepository($entityType)->find($entityId);
                $category = $this->getRepository('ComplaintCategory')->find($categoryId);

                $complaint = new Complaint();
                $complaint->setAuthor($author);
                
                $setEntity = "set" . ucfirst($entityType);
                
                $complaint->$setEntity($entity);
                $complaint->setContent($comment);
                $complaint->setComplaintCategory($category);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($complaint);
                $em->flush();
                
                $response['error'] = false;
            } catch (Exception $exc) {
                $response['error'] = true;
                $response['message'] = $exc->getMessage();
            }
        }

        return $this->jsonResponse($response);
    }
    
    /**
     * report form view
     * @Route("/report/{entityType}/{entityId}", name= "complaint_form")
     * @Template
     */
    public function reportAction($entityType, $entityId)
    {
        $categories = $this->getRepository('ComplaintCategory')->findBy(array(), array());
        $entity = $this->getRepository($entityType)->find($entityId);
        $reported = false;
        
        $user = $this->get('security.context')->getToken()->getUser();
        
        $complaintReported = $this->getRepository('Complaint')->findOneBy(array('author' => $user->getId(), $entityType => $entityId));
        if($complaintReported){
            $reported = true;
        }
        
        return array(
            'categories' => $categories,
            'entityType' => $entityType,
            'entityId' => $entity->getId(),
            'reported' => $reported
        );
    }

}
