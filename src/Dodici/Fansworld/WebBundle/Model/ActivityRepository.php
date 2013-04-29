<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Activity;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * ActivityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ActivityRepository extends CountBaseRepository
{
    public function latest(User $user = null, $limit = 10, $lastid = null)
    {
        $query = $this->_em->createQuery('
    	SELECT ac, aca, acv
		FROM \Dodici\Fansworld\WebBundle\Entity\Activity ac
		LEFT JOIN ac.author aca
		LEFT JOIN ac.video acv
		WHERE

		'.($user ? ('
			(
				(ac.author = :user)
				OR
				(ac.author IN (
				SELECT ufxt.id FROM \Dodici\Fansworld\WebBundle\Entity\Friendship ufx JOIN ufx.target ufxt
        		WHERE ufx.author = :user AND ufx.active = true
				))
				OR
				(
					(ac.id IN (
						SELECT huxac.id FROM \Dodici\Fansworld\WebBundle\Entity\HasUser hux JOIN hux.activity huxac WHERE hux.target IN (
            				SELECT ufxxt.id FROM \Dodici\Fansworld\WebBundle\Entity\Friendship ufxx JOIN ufxx.target ufxxt
                    		WHERE ufxx.author = :user AND ufxx.active = true
        				)
					))
					OR
					(
    					(ac.type IN ('.Activity::TYPE_NEW_VIDEO.', '.Activity::TYPE_NEW_PHOTO.'))
						AND
						(ac.author IS NULL OR (ac.author IN (
            				SELECT ufxat.id FROM \Dodici\Fansworld\WebBundle\Entity\Friendship ufxa JOIN ufxa.target ufxat
                    		WHERE ufxa.author = :user AND ufxa.active = true
        				)))
    					AND
						(ac.id IN (
    						SELECT htxac.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTeam htx JOIN htx.activity htxac WHERE htx.team IN (
                				SELECT tshxtm.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship tshx JOIN tshx.team tshxtm
                        		WHERE tshx.author = :user
            				)
    					))
					)
					OR
					(
						(ac.type IN ('.Activity::TYPE_NEW_VIDEO.', '.Activity::TYPE_NEW_PHOTO.'))
						AND
						(ac.author IS NULL OR (ac.author IN (
            				SELECT ufxbt.id FROM \Dodici\Fansworld\WebBundle\Entity\Friendship ufxb JOIN ufxb.target ufxbt
                    		WHERE ufxb.author = :user AND ufxb.active = true
        				)))
						AND
						(ac.id IN (
    						SELECT hixac.id FROM \Dodici\Fansworld\WebBundle\Entity\HasIdol hix JOIN hix.activity hixac WHERE hix.idol IN (
                				SELECT ishxid.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship ishx JOIN ishx.idol ishxid
                        		WHERE ishx.author = :user
            				)
    					))
					)
				)
			)
		') : '
			ac.author IS NULL
		').'

		'.($lastid ? '
			AND ac.id < :lastid
		' : '').'
			ORDER BY ac.id DESC
    	');

        if ($user instanceof User) $query = $query->setParameter('user', $user->getId());

        if ($lastid !== null) $query = $query->setParameter('lastid', $lastid);

        if ($limit !== null) $query = $query->setMaxResults($limit);

        return $query->getResult();
    }
}