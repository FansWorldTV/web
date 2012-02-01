<?php

namespace Dodici\Fansworld\WebBundle\Entity;

class Privacy
{
	const FRIENDS_ONLY = 1;
	const EVERYONE = 2;
	
	public static function getOptions() {
		return array(
			self::FRIENDS_ONLY => 'Sólo amigos',
			self::EVERYONE => 'Público',
		);
	}
}