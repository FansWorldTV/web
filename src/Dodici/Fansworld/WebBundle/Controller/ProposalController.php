<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Dodici\Fansworld\WebBundle\Entity\Proposal;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Proposal controller.
 * @Route("/proposal")
 */
class ProposalController extends SiteController
{
	/**
     * @Route("/{id}/{slug}", name= "proposal_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     */
    public function showAction($id)
    {
        //TODO: todo
    	$proposal = $this->getRepository('Proposal')->find($id);

        $this->securityCheck($proposal);

        return new Response('TODO');
    }

    /**
     * @Route("/list", name= "proposal_list")
     * @Secure(roles="ROLE_USER")
     * @Template     
     */
    public function listAction()
    {
        $proposals = $this->getRepository('Proposal')->findBy(array('active' => true), array('likeCount' => 'DESC'));

        return array('proposals' => $proposals);
    }    

    /**
     * @Route("/new", name= "proposal_new")
     * @Secure(roles="ROLE_USER")
     * @Template     
     */
    public function newAction()
    {
        $request = $this->getRequest();

        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $proposal = null;

        $defaultData = array();

        $proposalTypes = array();

        $proposalTypes = Proposal::getTypeList();

        $collectionConstraint = new Collection(array(
                    'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'type' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($proposalTypes)))             
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => true, 'label' => 'Título'))
                ->add('content', 'text', array('required' => true, 'label' => 'Descripción'))
                ->add('type', 'choice', array('required' => true, 'choices' => $proposalTypes, 'label' => 'Tipo', 'expanded' => true))
                ->getForm();

        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $proposal = new proposal();
                    $proposal->setAuthor($user);
                    $proposal->setTitle($data['title']);
                    $proposal->setContent($data['content']);
                    $proposal->setType($data['type']);

                    $em->persist($proposal);
                    $em->flush();

                    $this->get('session')->setFlash('success', $this->trans('Se creo'));
                }
            } catch (\Exception $e) {

                $form->addError(new FormError($e->getMessage()));
            }
        }

        return array('form' => $form->createView());
    }

}
