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
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Comment controller.
 */
class ComplaintController extends SiteController
{
    const LIMIT_LIST = 10;

    /**
     * @Route("/ajax/report", name= "complaint_make")
     * @Template
     */
    public function ajaxReportAction()
    {
        $request = $this->getRequest();
        $author = $this->getUser();
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
            } catch (\Exception $exc) {
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

        $user = $this->getUser();

        $complaintReported = $this->getRepository('Complaint')->findOneBy(array('author' => $user->getId(), $entityType => $entityId));
        if($complaintReported){
            $reported = true;
        }

        return array(
            'categories' => $categories,
            'entityType' => $entityType,
            'entityId' => $entity->getId(),
            'reported' => $reported,
            'user' => $user
        );
    }

    /**
     * report form view
     * @Route("/reportPage/{entityType}/{entityId}", name= "complaint_formPage")
     * @Template
     */
    public function reportPageAction($entityType, $entityId)
    {
        $categories = $this->getRepository('ComplaintCategory')->findBy(array(), array());
        $entity = $this->getRepository($entityType)->find($entityId);
        $reported = false;

        $user = $this->getUser();

        $complaintReported = $this->getRepository('Complaint')->findOneBy(array('author' => $user->getId(), $entityType => $entityId));
        if($complaintReported){
            $reported = true;
        }

        return array(
            'categories' => $categories,
            'entityType' => $entityType,
            'entityId' => $entity->getId(),
            'reported' => $reported,
            'user' => $user
        );
    }


    /**
     * list complaints
     * @Route("/reports", name="complaint_list" )
     * @Template
     * @Secure("ROLE_ADMIN")
     */
    public function listAction()
    {
        $complaints = $this->getRepository('Complaint')->getByEntity(self::LIMIT_LIST);
        $countAll = $this->getRepository('Complaint')->countBy(array());

        $addMore = $countAll > self::LIMIT_LIST ?  true : false;

        return array('complaints' => $complaints, 'addMore' => $addMore);
    }

    /**
     * ajax list complaints
     * @Route("/ajax/reports", name="complaint_ajaxlist")
     */
    public function ajaxListComplaintsAction()
    {
        $request = $this->getRequest();
        $complaintType = $request->get('type', null);
        $page = $request->get('page', 1);
        $page = (int) $page;
        $offset = ( $page -1 ) * self::LIMIT_LIST;

        if(empty($complaintType)){
            $complaintType = null;
        }

        $response = array();

        $complaints = $this->getRepository('Complaint')->getByEntity(self::LIMIT_LIST, $offset, $complaintType);
        $countAll = $this->getRepository('Complaint')->countBy(array());

        $pageCount= $countAll / self::LIMIT_LIST;
        $response['addMore'] = $pageCount > $page ? true : false;

        foreach($complaints as $complaint){
            $response['complaints'][] = array(
                'author' => (string) $complaint->getAuthor(),
                'category' => (string) $complaint->getComplaintCategory(),
                'content' => $complaint->getContent(),
                'createdAt' => $complaint->getCreatedAt()->format('d/m/Y H:i'),
                'active' => $complaint->getActive(),
                'target' => $complaint->getTarget() ? $complaint->getTarget()->getId() : false
            );
        }

        return $this->jsonResponse($response);
    }

}
