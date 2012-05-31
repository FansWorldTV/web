<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class TwitterUserToken extends AbstractToken
{
    public function __construct($uid = '', array $roles = array())
    {
        parent::__construct($roles);

        $this->setUser($uid);

        if (!empty($uid)) {
            $this->setAuthenticated(true);
        }
    }

    public function getCredentials()
    {
        return '';
    }
}
