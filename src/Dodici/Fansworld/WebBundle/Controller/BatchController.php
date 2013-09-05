<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\EventTweet;
use Kaltura\Client\Enum\EntryStatus;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Batch controller.
 * @Route("/batch")
 */
class BatchController extends SiteController
{

    /**
     * Feed the Event fixture
     * @Route("/eventfeeding", name="admin_batch_eventfeeding")
     */
    public function eventFeedingAction()
    {
        set_time_limit(600);
        $df = $this->get('feeder.event');
        $df->feed();
		$df->pending();

		return new Response('Ok');
    }

	/**
     * Feed event incidents
     * @Route("/eventminutefeeding", name="admin_batch_eventminutefeeding")
     */
    public function eventMinuteFeedingAction()
    {
        $df = $this->get('feeder.event.minute');
        $df->feed();
		$df->pending();

		return new Response('Ok');
    }

    /**
     * Finish open, expired events (sanity check)
     * @Route("/eventfinishing", name="admin_batch_eventfinishing")
     */
    public function eventFinishingAction()
    {
        $events = $this->getRepository('Event')->expired();
        $em = $this->getDoctrine()->getEntityManager();

        if ($events) {
            foreach ($events as $event) {
                $event->setFinished(true);
                $em->persist($event);
            }

            $em->flush();
        }

        return new Response('Ok');
    }

	/**
     * Retrieve event tweets
     * @Route("/eventtweets", name="admin_batch_eventtweets")
     */
    public function eventTweetsAction()
    {
        $teams = $this->getRepository('Team')->withEvents(1);
        $eventtweetrepo = $this->getRepository('EventTweet');
        $em = $this->getDoctrine()->getEntityManager();

        $topush = array();

        foreach ($teams as $t) {
            $team = $t['team'];
            $event = $t['event'];

            $maxtweetid = $eventtweetrepo->maxExternal($team);
            $twitter = $team->getTwitter();

            $apptwitter = $this->get('app.twitter');

            $latest = $apptwitter->latestSinceId($twitter, $maxtweetid);

            foreach ($latest as $l) {
                if ($l && is_object($l)) {
                    $date = new \DateTime($l->created_at);
                    $external = $l->id_str;
                    $content = $l->text;

                    $exists = $eventtweetrepo->countBy(array('external' => $external));

                    if (!$exists) {
                        $et = new EventTweet();
                        $et->setTeam($team);
                        $et->setEvent($event);
                        $et->setCreatedAt($date);
                        $et->setExternal($external);
                        $et->setContent($content);

                        $em->persist($et);

                        $topush[] = $et;
                    }
                }
            }

            $em->flush();
        }

        $meteor = $this->get('meteor');
        foreach ($topush as $tp) $meteor->push($tp);

		return new Response('Ok');
    }

	/**
     * Process pending videos (thumbnail, upload, etc)
     * @Route("/videoprocessing", name="admin_batch_videoprocessing")
     */
    public function videoProcessingAction()
    {
        set_time_limit(600);
        $videos = $this->getRepository('Video')->pendingProcessing(10);
        $uploader = $this->get('video.uploader');
        $kaltura = $this->get('kaltura');
        $em = $this->getDoctrine()->getEntityManager();

        foreach ($videos as $video) {
            try {
                $entry = $kaltura->getEntry($video->getStream());
                if ($entry && ($entry->status == EntryStatus::READY)) {
                    $uploader->process($video, $entry->thumbnailUrl, $entry->msDuration);
                    if ($video->getHighlight()) $kaltura->setSiteMetadata($video->getStream());
                    else $kaltura->setUserMetadata($video->getStream());
                }
            } catch (\Exception $e) {
                // entry doesn't exist or something went wrong, do nothing for now
            }
            $video->setProcessingTries($video->getProcessingTries()+1);
            $em->persist($video);
            $em->flush();
        }

        return new Response('Ok');
    }

	/**
     * Clean up timed out users from "watching video" lists
     * @Route("/videoaudienceclean", name="admin_batch_videoaudienceclean")
     */
    public function videoAudienceCleanAction()
    {
        $this->get('video.audience')->cleanup();

        return new Response('Ok');
    }

    /**
     * Convert CSV fixture files to YML
     * Ask before running
     * @Route("/fixturecsvtoyml", name="admin_batch_csvtoyml")
     */
    public function convertCSVtoYML()
    {
        $this->get('fixture.csvtoyml')->convertAll();

        return new Response('Ok');
    }

    /**
     * Update video/photocounts
     * @Route("/updatecounts", name="admin_batch_updatecounts")
     */
    public function updateCounts()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $idols = $this->getRepository('Idol')->findAll();
        foreach ($idols as $idol) {
            $cntvideo = $this->getRepository('Idol')->countTagged($idol, 'video');
            $cntphoto = $this->getRepository('Idol')->countTagged($idol, 'photo');
            $cntfans = $this->getRepository('Idolship')->countBy(array('idol' => $idol->getId()));
            $idol->setVideoCount($cntvideo);
            $idol->setPhotoCount($cntphoto);
            $idol->setFanCount($cntfans);
            $em->persist($idol);
        }

        $teams = $this->getRepository('Team')->findAll();
        foreach ($teams as $team) {
            $cntvideo = $this->getRepository('Team')->countTagged($team, 'video');
            $cntphoto = $this->getRepository('Team')->countTagged($team, 'photo');
            $cntfans = $this->getRepository('Teamship')->countBy(array('team' => $team->getId()));
            $team->setVideoCount($cntvideo);
            $team->setPhotoCount($cntphoto);
            $team->setFanCount($cntfans);
            $em->persist($team);
        }

        $users = $this->getRepository('User')->findBy(array('enabled' => true));
        foreach ($users as $user) {
            $cntvideo = $this->getRepository('Video')->countBy(array('author' => $user->getId(), 'active' => true));
            $cntphoto = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));
            $cntfans = $this->getRepository('Friendship')->countBy(array('target' => $user->getId(), 'active' => true));
            $user->setVideoCount($cntvideo);
            $user->setPhotoCount($cntphoto);
            $user->setFanCount($cntfans);
            $em->persist($user);
        }

        $em->flush();

        return new Response('Ok');
    }

    /**
     * Generate some yaml
     * @Route("/generate-yaml-idol-sports-other", name="admin_batch_generate_yaml_idol_sports_other")
     */
    public function generateYamlIdolSportsOtherAction()
    {
        $request = $this->getRequest();
        $result = '';
        $youtubeVideos = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');

            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  id: ".($exp[0]+10000)."\n";
                $result .= "  firstname: ".$exp[1]."\n";
                $result .= "  lastname: ".$exp[2]."\n";
                $date = $exp[3];
                $birthday = null;
                if ($date) {
                    $bdayxp = explode('/', $date);
                    $birthday = '"'.$bdayxp[2] . '-' . $bdayxp[0] . '-' . $bdayxp[1].'"';
                }
                $result .= "  birthday: ".$birthday."\n";
                $result .= "  nicknames: ".$exp[4]."\n";
                $result .= "  country: ".$exp[6]."\n";
                $result .= "  twitter: ".$exp[7]."\n";
                $result .= "  genre: ".($exp[8]+10)."\n";
                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[9]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";

                if ($exp[5]) {
                    $result .= "  teams:\n";
                    $xpteams = explode("\n", $exp[5]);
                    foreach ($xpteams as $xpteam) {
                        $result .= "      -\n";
                        if (intval($xpteam))
                        $result .= "        id: ".(intval($xpteam)+10000)."\n";
                        else
                        $result .= "        name: $xpteam\n";

                        $result .= "        debut: false\n";
                        $result .= "        actual: true\n";
                        $result .= "        highlight: true\n";
                        $result .= "        manager: false\n";
                    }
                }

                if ($exp[12]) {
                    $links = explode(",", $exp[12]);
                    foreach ($links as $link) {
                        $youtubeVideos .= "-\n";
                        $youtubeVideos .= "  author: ".(rand(11,20))."\n";
                        $youtubeVideos .= "  url: ". trim($link)."\n";
                        $youtubeVideos .= "  videocategory: ".(rand(2,7))."\n";
                        $youtubeVideos .= "  genre: 19\n";
                        $youtubeVideos .= "  highlight: false\n";
                        $youtubeVideos .= "  tagidols: \n";
                        $youtubeVideos .= "    - ".($exp[0]+10000)."\n";
                    }
                }

            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea><h1>Youtube</h1><textarea>'.$youtubeVideos.'</textarea>');
    }

    /**
     * Generate some yaml
     * @Route("/generate-yaml-team-sports-other", name="admin_batch_generate_yaml_team_sports_other")
     */
    public function generateYamlTeamSportsOtherAction()
    {
        $request = $this->getRequest();
        $result = '';
        $youtubeVideos = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');

            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  id: ".($exp[0]+10000)."\n";
                $result .= "  title: ".$exp[1]."\n";
                $result .= "  foundedAt: ".$exp[2]."-01-01\n";
                $result .= "  nicknames: ".$exp[3]."\n";
                $result .= "  twitter: ".$exp[4]."\n";
                $result .= "  genre: ".($exp[5]+10)."\n";
                $result .= "  country: ".$exp[6]."\n";
                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[7]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";

                if ($exp[10]) {
                    $links = explode(",", $exp[10]);
                    foreach ($links as $link) {
                        $youtubeVideos .= "-\n";
                        $youtubeVideos .= "  author: ".(rand(11,20))."\n";
                        $youtubeVideos .= "  url: ". trim($link)."\n";
                        $youtubeVideos .= "  videocategory: ".(rand(2,7))."\n";
                        $youtubeVideos .= "  genre: ".($exp[5]+10)."\n";
                        $youtubeVideos .= "  highlight: false\n";
                        $youtubeVideos .= "  tagteams: \n";
                        $youtubeVideos .= "    - ".($exp[0]+10000)."\n";
                    }
                }
            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea><h1>Youtube</h1><textarea>'.$youtubeVideos.'</textarea>');
    }

    //-------------------------------------------------------------------------------------------------------

    /**
     * Generate some yaml
     * @Route("/generate-yaml-idol-music", name="admin_batch_generate_yaml_idol_music")
     */
    public function generateYamlIdolMusicAction()
    {
        $request = $this->getRequest();
        $result = '';
        $youtubeVideos = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');


            foreach ($file as $exp) {

                $result .= "-\n";
                $result .= "  id: ".($exp[0]+20000)."\n";
                $result .= "  firstname: ".$exp[1]."\n";
                $result .= "  lastname: ".$exp[2]."\n";
                $result .= "  birthday: ".$exp[3]."\n";
                $result .= "  nicknames: ".$exp[4]."\n";

                if ($exp[5]) {
                    $result .= "  achievements: |\n";
                    $xpc = explode("\n", $exp[5]);
                    foreach($xpc as $xc) $result .= "    ". $xc;
                    $result .= "\n";
                }

                $result .= "  country: ".$exp[7]."\n";
                $result .= "  twitter: ".$exp[8]."\n";

                $genres = array();
                $genresxp = explode("\n", $exp[9]);
                foreach ($genresxp as $gx) if (intval($gx)) $genres[] = (intval($gx) + 20);
                $result .= "  genre: [".(implode(', ', $genres))."]\n";
                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[10]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";

                if ($exp[6]) {
                    $result .= "  teams:\n";
                    $xpteams = explode("\n", $exp[6]);
                    foreach ($xpteams as $xpteam) {
                        if (intval($xpteam))
                            $result .= "      -\n"."        id: ".(intval($xpteam)+20000)."\n";
                        else
                            if (strlen($xpteam) > 1) $result .= "      -\n"."        name: $xpteam\n";

                        if (intval($xpteam) || strlen($xpteam) > 1 ) {
                            $result .= "        debut: false\n";
                            $result .= "        actual: true\n";
                            $result .= "        highlight: true\n";
                            $result .= "        manager: false\n";
                        }
                    }
                }

                if ($exp[13]) {
                    $links = explode(",", $exp[13]);
                    foreach ($links as $link) {
                        $youtubeVideos .= "-\n";
                        $youtubeVideos .= "  author: ".(rand(11,20))."\n";
                        $youtubeVideos .= "  url: ". trim($link)."\n";
                        $youtubeVideos .= "  videocategory: ".(rand(2,7))."\n";
                        $youtubeVideos .= "  genre: ".($genresxp[0]+20)."\n";
                        $youtubeVideos .= "  highlight: false\n";
                        $youtubeVideos .= "  tagidols: \n";
                        $youtubeVideos .= "    - ".($exp[0]+20000)."\n";
                    }
                }

            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea><h1>Youtube</h1><textarea>'.$youtubeVideos.'</textarea>');
    }

    /**
     * Generate some yaml
     * @Route("/generate-yaml-team-music", name="admin_batch_generate_yaml_team_music")
     */
    public function generateYamlTeamMusicAction()
    {
        $request = $this->getRequest();
        $result = '';
        $youtubeVideos = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');

            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  id: ".($exp[0]+20000)."\n";
                $result .= "  title: ".$exp[1]."\n";
                $result .= "  foundedAt: ".$exp[2]."-01-01\n";
                $result .= "  nicknames: ".$exp[3]."\n";
                if ($exp[4]) {
                    $result .= "  achievements: |\n";
                    $xpc = explode("\n", $exp[4]);
                    foreach($xpc as $xc) $result .= "    ". $xc;
                    $result .= "\n";
                }
                $result .= "  twitter: ".$exp[5]."\n";

                $genres = array();
                $genresxp = explode("\n", $exp[6]);
                foreach ($genresxp as $gx) if (intval($gx)) $genres[] = (intval($gx) + 20);
                $result .= "  genre: [".(implode(', ', $genres))."]\n";

                $result .= "  country: ".$exp[7]."\n";
                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[8]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";

                if ($exp[9]) {
                    $links = explode(",", $exp[9]);
                    foreach ($links as $link) {
                        $youtubeVideos .= "-\n";
                        $youtubeVideos .= "  author: ".(rand(11,20))."\n";
                        $youtubeVideos .= "  url: ". trim($link)."\n";
                        $youtubeVideos .= "  videocategory: ".(rand(2,7))."\n";
                        $youtubeVideos .= "  genre: ".($genresxp[0]+20)."\n";
                        $youtubeVideos .= "  highlight: false\n";
                        $youtubeVideos .= "  tagteams: \n";
                        $youtubeVideos .= "    - ".($exp[0]+20000)."\n";
                    }
                }

            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea><h1>Youtube</h1><textarea>'.$youtubeVideos.'</textarea>');
    }

    /**
     * Generate some yaml
     * @Route("/generate-yaml-team-futbol", name="admin_batch_generate_yaml_team_futbol")
     */
    public function generateYamlTeamFutbolAction()
    {
        $request = $this->getRequest();
        $result = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');

            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  id: ".($exp[0])."\n";
                $result .= "  title: ".$exp[1]."\n";
                $result .= "  foundedAt: ".$exp[2]."\n";
                $result .= "  genre: 11\n";
                $result .= "  nicknames: ".$exp[3]."\n";
                $result .= "  letters: ".$exp[4]."\n";
                $result .= "  shortname: ".$exp[1]."\n";
                $result .= "  stadium: ".$exp[5]."\n";
                $result .= "  website: ".$exp[6]."\n";
                $result .= "  twitter: ".$exp[7]."\n";
                $result .= "  country: ".$exp[10]."\n";

                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[11]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";

            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea>');
    }

    /**
     * Generate some yaml
     * @Route("/generate-yaml-videos-music", name="admin_batch_generate_yaml_videos_music")
     */
    public function generateYamlVideosMusicAction()
    {
        $request = $this->getRequest();
        $result = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\tmp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');


            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  author: ".$exp[0]."\n";
                $result .= "  stream: ".$exp[1]."\n";
                $result .= "  highlight: ".$exp[2]."\n";
                $result .= "  videocategory: ".$exp[3]."\n";
                $result .= "  genre: ".($exp[4]+20)."\n";

                $createAt = null;
                if ($exp[5]) {
                    $cadayxp = explode('/', $exp[5]);
                    $createAt = $cadayxp[2] . '-' . $cadayxp[0] . '-' . $cadayxp[1];
                }
                $result .= "  createdAt: ".$createAt."\n";
                $result .= "  title: ".$exp[6]."\n";


                if ($exp[7]) {
                    $result .= "  tagidols: \n";
                    $tagidolsExp = explode(",", $exp[7]+20000);
                    foreach($tagidolsExp  as $idolId) $result .= "    - ".trim($idolId)."\n";
                }

                if ($exp[8]) {
                    $result .= "  tagteams: \n";
                    $tagteamsExp = explode(",", $exp[8]+20000);
                    foreach($tagteamsExp  as $teamId) $result .= "    - ".trim($teamId)."\n";
                }

                if ($exp[9]) {
                    $result .= "  tagtexts: \n";
                    $tagtextExp = explode(",", $exp[9]);
                    foreach($tagtextExp  as $text) if (strlen($text > 1)) $result .= "    - ".trim($text)."\n";
                }

                if ($exp[10]) {
                    $result .= "  content: |\n";
                    $xpc = explode("\n", $exp[10]);
                    foreach($xpc as $xc) $result .= "    ". $xc;
                    $result .= "\n";
                }
            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea>');
    }


     /**
     * Generate some yaml
     * @Route("/generate-yaml-idol-futbolLocal", name="admin_batch_generate_yaml_idol_futbolLocal")
     */
    public function generateYamlIdolFutbolLocalAction()
    {
        $request = $this->getRequest();
        $result = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');


            foreach ($file as $exp) {

                $result .= "-\n";
                $result .= "  id: ".($exp[0])."\n";
                $result .= "  firstname: ".$exp[1]."\n";
                $result .= "  lastname: ".$exp[2]."\n";
                $date = $exp[8];
                $birthday = null;
                if ($date) {
                    $bdayxp = explode('/', $date);
                    if (count($bdayxp) == 3) {
                        $birthday = $bdayxp[2] . '-' . $bdayxp[1] . '-' . $bdayxp[0];
                        $result .= "  birthday: ".$birthday."\n";
                    }
                }
                $result .= "  nicknames: ".$exp[7]."\n";

                $result .= "  country: ".$exp[9]."\n";
                $result .= "  twitter: ".$exp[10]."\n";

                $result .= "  genre: [11]\n";

                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[11]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";


                if ($exp[3] || $exp[5]) {
                    $result .= "  teams:\n";

                    if ($exp[3]) {
                        $teamPlayer = explode("\n", $exp[3]);
                        foreach ($teamPlayer as $team) {
                            if (intval($team))
                                $result .= "      -\n"."        id: ".(intval($team))."\n";
                            else
                                if (strlen($team) > 1 ) $result .= "      -\n"."        name: $team\n";

                            if (intval($team) || strlen($team) > 1 ) {
                                $result .= "        debut: false\n";
                                $result .= "        actual: true\n";
                                $result .= "        highlight: true\n";
                                $result .= "        manager: false\n";
                            }
                        }
                    }

                    if ($exp[5]) {
                        $teamDt = explode("\n", $exp[5]);
                        foreach ($teamDt as $team) {
                            if (intval($team))
                                $result .= "      -\n"."        id: ".(intval($team))."\n";
                            else
                                if (strlen($team) > 1 )  $result .= "      -\n"."        name: $team\n";

                            if (intval($team) || strlen($team) > 1 ) {
                                $result .= "        debut: false\n";
                                $result .= "        actual: true\n";
                                $result .= "        highlight: true\n";
                                $result .= "        manager: true\n";
                            }
                        }
                    }

                }
            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea>');
    }

     /**
     * Generate some yaml
     * @Route("/generate-yaml-idol-futbolInternacional", name="admin_batch_generate_yaml_idol_futbolInternacional")
     */
    public function generateYamlIdolFutbolInternacionalAction()
    {
        $request = $this->getRequest();
        $result = '';
        $youtubeVideos = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\temp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');


            foreach ($file as $exp) {

                $result .= "-\n";
                $result .= "  id: ".($exp[0])."\n";
                $result .= "  firstname: ".$exp[1]."\n";
                $result .= "  lastname: ".$exp[2]."\n";
                $date = $exp[3];
                $birthday = null;
                if ($date) {
                    $bdayxp = explode('/', $date);
                    if (count($bdayxp) == 3) {
                        $birthday = $bdayxp[2] . '-' . $bdayxp[1] . '-' . $bdayxp[0];
                        $result .= "  birthday: ".$birthday."\n";
                    }
                }

                $result .= "  nicknames: ".$exp[4]."\n";
                $result .= "  country: ".$exp[6]."\n";
                $result .= "  twitter: ".$exp[7]."\n";
                $result .= "  genre: [11]\n";

                if ($exp[5]) {
                    $result .= "  teams:\n";
                    $teamPlayer = explode("\n", $exp[5]);
                    foreach ($teamPlayer as $team) {
                        $result .= "      -\n";
                        if (intval($team))
                            $result .= "        id: ".(intval($team))."\n";
                        else
                            $result .= "        name: $team\n";

                        $result .= "        debut: false\n";
                        $result .= "        actual: true\n";
                        $result .= "        highlight: true\n";
                        $result .= "        manager: false\n";
                    }
                }

                if ($exp[12]) {
                    $links = explode(",", $exp[12]);
                    foreach ($links as $link) {
                        $youtubeVideos .= "-\n";
                        $youtubeVideos .= "  author: ".(rand(11,20))."\n";
                        $youtubeVideos .= "  url: ". trim($link)."\n";
                        $youtubeVideos .= "  videocategory: ".(rand(2,7))."\n";
                        $youtubeVideos .= "  genre: 11\n";
                        $youtubeVideos .= "  highlight: false\n";
                        $youtubeVideos .= "  tagidols: \n";
                        $youtubeVideos .= "    - ".$exp[0]."\n";
                    }
                }

                $result .= "  content: |\n";
                $xpc = explode("\n", $exp[9]);
                foreach($xpc as $xc) $result .= "    ". $xc;
                $result .= "\n";

            }

        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><h1>Info</h1><textarea>'.$result.'</textarea><h1>Videos Youtube</h1><textarea>'.$youtubeVideos.'</textarea>');
    }

     /**
     * Generate some yaml
     * @Route("/generate-yaml-videos-futbol", name="admin_batch_generate_yaml_videos_futbol")
     */
    public function generateYamlVideosFutbolAction()
    {
        $request = $this->getRequest();
        $result = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\tmp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');


            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  author: ".$exp[0]."\n";
                $result .= "  stream: ".$exp[1]."\n";
                $result .= "  highlight: ".$exp[2]."\n";
                $result .= "  videocategory: ".$exp[3]."\n";
                $result .= "  genre: ".($exp[4] + 10)."\n";

                $createAt = null;
                if ($exp[5]) {
                    $cadayxp = explode('/', $exp[5]);
                    $createAt = $cadayxp[2] . '-' . $cadayxp[0] . '-' . $cadayxp[1];
                }
                $result .= "  createdAt: ".$createAt."\n";

                $result .= "  title: ".$exp[6]."\n";

                if ($exp[7]) {
                    $result .= "  tagidols: \n";
                    $tagidolsExp = explode(",", $exp[7]);
                    foreach($tagidolsExp  as $idolId) $result .= "    - ".trim($idolId)."\n";
                }

                if ($exp[8]) {
                    $result .= "  tagteams: \n";
                    $tagteamsExp = explode(",", $exp[8]);
                    foreach($tagteamsExp  as $teamId) $result .= "    - ".trim($teamId)."\n";
                }

                if ($exp[9]) {
                    $result .= "  tagtexts: \n";
                    $tagtextExp = explode(",", $exp[9]);
                    foreach($tagtextExp  as $text) if (strlen($text) > 1) $result .= "    - ".trim($text)."\n";
                }

                if ($exp[10]) {
                    $result .= "  content: |\n";
                    $xpc = explode("\n", $exp[10]);
                    foreach($xpc as $xc) $result .= "    ". $xc;
                    $result .= "\n";
                }
            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea>');
    }

      /**
     * Generate some yaml
     * @Route("/generate-yaml-videos-sports-other", name="admin_batch_generate_yaml_videos_sports_other")
     */
    public function generateYamlVideosSportsOtherAction()
    {
        $request = $this->getRequest();
        $result = '';
        if ($request->getMethod() == 'POST') {
            $string = $request->get('string');

            $archivo = 'C:\\imple\\tmp.csv';
            file_put_contents($archivo, $string);

            $file = new \SplFileObject($archivo, 'rb');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',', '"', '\\');


            foreach ($file as $exp) {
                $result .= "-\n";
                $result .= "  author: ".$exp[0]."\n";
                $result .= "  stream: ".$exp[1]."\n";
                $result .= "  highlight: ".$exp[2]."\n";
                $result .= "  videocategory: ".$exp[3]."\n";
                $result .= "  genre: ".($exp[4] + 10)."\n";

                $createAt = null;
                if ($exp[5]) {
                    $cadayxp = explode('/', $exp[5]);
                    $createAt = $cadayxp[2] . '-' . $cadayxp[1] . '-' . $cadayxp[0];
                }
                $result .= "  createdAt: ".$createAt."\n";

                $result .= "  title: ".$exp[6]."\n";


                if ($exp[7]) {
                    $result .= "  tagidols: \n";
                    $tagidolsExp = explode(",", $exp[7]);
                    foreach($tagidolsExp  as $idolId) $result .= "    - ". $idolId."\n";
                }

                if ($exp[8]) {
                    $result .= "  tagteams: \n";
                    $tagteamsExp = explode(",", $exp[8]);
                    foreach($tagteamsExp  as $teamId) $result .= "    - ". $teamId."\n";
                }

                if ($exp[9]) {
                    $result .= "  tagtexts: \n";
                    $tagtextExp = explode(",", $exp[9]);
                    foreach($tagtextExp  as $text) $result .= "    - ". $text."\n";
                }

                if ($exp[10]) {
                    $result .= "  content: |\n";
                    $xpc = explode("\n", $exp[10]);
                    foreach($xpc as $xc) $result .= "    ". $xc;
                    $result .= "\n";
                }
            }
        }

        return new Response('<form action="" method="post"><textarea name="string"></textarea><input type="submit"></form><br><br><textarea>'.$result.'</textarea>');
    }



}
