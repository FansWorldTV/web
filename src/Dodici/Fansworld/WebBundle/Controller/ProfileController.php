<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Profile;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Team;
use Dodici\Fansworld\WebBundle\Entity\IdolCareer;
use Application\Sonata\UserBundle\Entity\Notification;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

/**
 * Profile controller
 */
class ProfileController extends SiteController
{

    const LIMIT_PROFILES_HOME = 20;

    /**
     * @Route("/profiles", name="profiles_index")
     * @Template()
     */
    public function listAction()
    {
        return array(
            'genres'   => $this->getRepository('Genre')->getParents(),
            'profiles' => $this->getRepository('Idol')->findBy(array(), null, 30)
        );
    }

    /**
     *  Get params:
     *  - type: 'all' | 'idol' | 'team'
     *  - filterby: 'popular' | 'activity'
     *  - genre: (Int) genreId of parent(genre) | genreId of child(subgenre) | null
     *  - page: (Int) | null
     *  @Route("/profiles/ajax/get", name="profile_ajaxgetprofiles")
     */
    public function ajaxGetAction()
    {
        $request = $this->getRequest();
        $serializer = $this->get('serializer');

        $type     = $request->get('type', 'all');
        $filterBy = $request->get('filterby', 'popular');
        $genre    = $request->get('genre');
        $page     = $request->get('page', 1);

        $offset = ($page - 1) * self::LIMIT_PROFILES_HOME;

        if (!$type)
            $type     = 'all';
        if (!$filterBy)
            $filterBy = 'popular';

        $entities = $this->getRepository('Profile')->latestOrPopular($type, $filterBy, $genre, self::LIMIT_PROFILES_HOME, $offset);

        $response = array(
            'profiles' => array()
        );

        foreach ($entities as $entity) {
            $realEntity = $this->getRepository(ucfirst($entity['type']))->find($entity['id']);

            $profile = array(
                'id'            => $entity['id'],
                'type'          => $entity['type'],
                'title'         => $entity['title'],
                'slug'          => $entity['slug'],
                'url'           => $this->generateUrl($entity['type'] . '_land', array('slug' => $entity['slug'])),
                'videoCount'    => $entity['videocount'],
                'fanCount'      => $entity['fancount'],
                'image'         => $this->getImageUrl($entity['imageid'], 'big_square'),
                'imageDouble'  => $this->getImageUrl($entity['imageid'], 'huge_square'),
                'splash'        => isset($entity['splashid']) ? $this->getImageUrl($entity['splashid'], 'big_square') : null,
                'splashDouble' => isset($entity['splashid']) ? $this->getImageUrl($entity['splashid'], 'huge_square') : null,
                'highlight'     => false,
                'isFan' => $this->get('fanmaker')->isFan($realEntity)
            );

            if ($filterBy == 'popular') {
                $profile['dataCount'] = $profile['fanCount'];
                $profile['dataText']  = 'fans';
            } else {
                $profile['dataCount'] = $profile['videoCount'];
                $profile['dataText']  = 'videos';
            }

            $response['profiles'][$profile['id']] = $profile;
        }

        if (isset($response['profiles']) && count($response['profiles']) > 0) {
            if ($filterBy == 'activity') {
                $highlights = array();

                foreach ($response['profiles'] as $profile)
                    $highlights[$profile['id']] = $profile['videoCount'];

                arsort($highlights);
                $top5 = array_slice($highlights, 0, 5, true);

                foreach ($top5 as $key => $profile) {
                    $iProfile = &$response['profiles'][$key]; // Var passed by reference to simplify the code

                    $entityProfile = $this->getRepository(ucfirst($iProfile['type']))->find($iProfile['id']);
                    $lastVideo = $this->getRepository('Video')->getVideosTaggedWith($entityProfile, 1);

                    $iProfile['lastVideo'] = $serializer->values(reset($lastVideo), 'small');
                    $iProfile['highlight'] = true;
                }
            }
    

            $i = 0;
            foreach ($response['profiles'] as $k => $profile) {
                $response['profiles'][$i] = $profile;
                unset($response['profiles'][$k]);
                $i++;
            }
        }

        return $this->jsonResponse($response);
    }

}