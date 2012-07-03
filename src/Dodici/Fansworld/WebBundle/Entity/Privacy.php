<?php

namespace Dodici\Fansworld\WebBundle\Entity;

class Privacy
{
	const FRIENDS_ONLY = 1;
	const EVERYONE = 2;
	const ONLY_ME = 3;
	
	public static function getOptions() {
		return array(
			self::FRIENDS_ONLY => 'friends_only',
			self::EVERYONE => 'everyone',
			self::ONLY_ME => 'only_me'
		);
	}
	
	public static function getFields() {
	    return array(
	        'email', 'address', 'firstname', 'lastname', 'sex', 'birthday', 'country', 'city', 'phone', 'content', 'facebook', 'twitter'
	    );
	}
}