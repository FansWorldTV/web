<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

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
 * API controller - Profile
 * V1
 * @Route("/api_v1")
 */
class ProfileController extends BaseController
{
	/**
     * Profiles - follow
     *
     * @Route("/profiles/follow", name="api_v1_profile_follow")
     * @Method({"GET"})
     *
     * Get params:
     * - <required> user_id: int
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     *
     * @return
     * array(array('user'=> array(), 'idol'=> arrray(), 'team' => array()))
     */
    public function followListAction()
    {
        try {
            $request = $this->getRequest();
            $user_id = $request->get('user_id');
            $user = $this->getRepository('User')->find($user_id);
            if (!$user) throw new HttpException(404, 'User not found');
            $pagination = $this->pagination();

            $followingProfiles =
                $this->getRepository('Profile')->followingProfiles($user_id, $pagination['limit'], $pagination['offset']);

            $return = array();
            foreach ($followingProfiles as $fp) {
                $entity = $this->getRepository(ucwords($fp['type']))->findBy(array('id' => $fp['target']));
                $return[$fp['type']][] =  $this->get('Serializer')->values($entity);
            }

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}