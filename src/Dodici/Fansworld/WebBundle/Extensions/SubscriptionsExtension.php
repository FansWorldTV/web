<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\Subscriptions;

class SubscriptionsExtension extends \Twig_Extension
{
    protected $subscriptions;

    function __construct(Subscriptions $subscriptions) {
        $this->subscriptions = $subscriptions;
    }

    public function getGlobals() {
        return array(
            'subscriptions' => $this->subscriptions
        );
    }

    public function getName()
    {
        return 'subscriptions';
    }

}