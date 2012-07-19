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
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;

/**
 * Comment controller.
 * @Route("/template")
 */
class TemplateController extends SiteController
{
	/**
	 * Get Comment Teplate
	 * @Route("/ajax/get/comment", name="template_comment")
	 * @Template
	 */
	public function CommentAction()
	{
		$request = $this->getRequest();
		$type = $request->get('type');
		
		return array(
				'typename' => $type
		);
	}   
}
