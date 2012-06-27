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
}