<?php

namespace Dodici\Fansworld\WebBundle\Entity;

class Privacy
{

    const FRIENDS_ONLY = 1;
    const EVERYONE = 2;
    const ONLY_ME = 3;

    public static function getOptions()
    {
        return array(
            self::FRIENDS_ONLY => 'friends_only',
            self::EVERYONE => 'everyone',
            self::ONLY_ME => 'only_me'
        );
    }

    public static function getFields()
    {
        return array(
            'email', 'address', 'sex', 'birthday', 'country', 'city', 'phone', 'content', 'facebook', 'twitter'
        );
    }

    public static function getDefaultFieldPrivacy()
    {
        $fields = self::getFields();
        $default = array();

        foreach ($fields as $f) {
            $default[$f] = self::ONLY_ME;
        }

        $default['sex'] = self::EVERYONE;
        $default['content'] = self::EVERYONE;

        $default['birthday'] = self::FRIENDS_ONLY;
        $default['country'] = self::FRIENDS_ONLY;
        $default['city'] = self::FRIENDS_ONLY;
        $default['facebook'] = self::FRIENDS_ONLY;
        $default['twitter'] = self::FRIENDS_ONLY;
        
        return $default;
    }

}