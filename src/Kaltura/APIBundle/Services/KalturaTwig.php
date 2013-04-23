<?php

namespace Kaltura\APIBundle\Services;

use Dodici\Fansworld\WebBundle\Services\Visitator;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Kaltura\APIBundle\Services\Kaltura;

class KalturaTwig {

  protected $kaltura;
  protected $visitator;
  protected $container;
  protected $player;

  function __construct(Kaltura $kaltura, Visitator $visitator, $container, $player) {
    $this->kaltura = $kaltura;
    $this->visitator = $visitator;
    $this->container = $container;
    $this->player = $player;
  }

  public function getPlayer(Video $video, $autoplay = false) {
    $this->visitator->visit($video);
    if ($video->getYoutube()) {
      return $this->container->get('templating')->render(
                      'KalturaAPIBundle::iframe.html.twig', array('idvideo' => $video->getYoutube()));
    } elseif ($video->getVimeo()) {
      return $this->container->get('templating')->render(
                      'KalturaAPIBundle::iframe.html.twig', array(
                  'url' => sprintf('http://player.vimeo.com/video/%1$s', $video->getVimeo())
              ));
    } else {
      return $this->container->get('templating')->render(
                      'KalturaAPIBundle::player.html.twig', array(
                  'video' => $video,
                  'partner' => $this->kaltura->getPartnerId(),
                  'subpartner' => $this->kaltura->getSubPartnerId(),
                  'player' => $this->player,
                  'autoplay' => $autoplay
                      )
      );
    }
  }

  public function getKs() {
    return $this->kaltura->getKs();
  }

  public function getPartnerId() {
    return $this->kaltura->getPartnerId();
  }

  public function getSubPartnerId() {
    return $this->kaltura->getSubPartnerId();
  }

  public function getPlayerId() {
    return $this->player;
  }

}