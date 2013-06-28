<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Complaint;
use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\ApiV1\BaseController;

/**
 * API controller - Complaint
 * V1
 * @Route("/api_v1")
 */
class ComplaintController extends BaseController
{
    /**
     * [signed] List
     *
     * @Route("/report/{entityType}/{entityId}", name= "api_v1_complaint")
     * @Method({"POST"})
     *
     * Entity types: video|photo|comment
     *
     * Post params:
     * - user_id: int
     * - [user token]
     * - [signature params]
     * - category: int
     * - comment: string
     *
     */
    public function reportAction($entityType, $entityId)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                if (!in_array($entityType, array('video','photo','comment'))) throw new HttpException(401, 'Invalid entity type');

                $userid = $request->get('user_id');
                // $user = $this->getRepository('User')->find($userid);
                $user = $this->checkUserToken($userid, $request->get('user_token'));

                $report = $this->getRepository('Complaint')->findOneBy(array($entityType => $entityId, 'author' => $userid));
                if ($report) throw new HttpException(420, 'User already reported this ' . $entityType);

                $categoryId = $request->get('category');
                $comment = $request->get('comment');

                $entity = $this->getRepository($entityType)->find($entityId);
                if (!$entity) throw new HttpException(401, 'Entity not found');

                $category = $this->getRepository('ComplaintCategory')->find($categoryId);
                if (!$category) throw new HttpException(401, 'Category not found');

                $complaint = new Complaint();
                $complaint->setAuthor($user);

                $setEntity = "set" . ucfirst($entityType);

                $complaint->$setEntity($entity);
                $complaint->setContent($comment);
                $complaint->setComplaintCategory($category);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($complaint);
                $em->flush();

                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

    /**
     * Complaint - categories list
     *
     * @Route("/complaint/categories", name="api_v1_complaint_category_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> sort: 'title' (default: title)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     *
     * @return
     * array (
     *      array (
     *          id: int,
     *          title: string
     *      ),
     *      ...
     *      )
     *
     */
    public function categoriesAction()
    {
        try {
            $categories = $this->getRepository('ComplaintCategory')->findAll();

            $return = $this->get('serializer')->values($categories);

            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

}

