<?php
namespace DataFactory\FeedBundle\Feeder;

use Symfony\Bundle\DoctrineBundle\Registry;

class XmlRequest {
    private $api_url;

    public function __construct($api_url)
    {
        $this->api_url = $api_url;
    }
    
    public function request($params = array())
    {
        try {
            $file = utf8_encode(file_get_contents($this->to_url($params)));
            $document = new \SimpleXMLElement($file);
            return $document;
        } catch (\Exception $e) {
            throw new \Exception('Invalid XML response');
        }
    }
    
	/**
 	 * parses the url and rebuilds it to be
 	 * scheme://host/path
 	 */
    private function get_normalized_http_url() {
        $parts = parse_url($this->api_url);
    
        $port = @$parts['port'];
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = @$parts['path'];
    
        $port or $port = ($scheme == 'https') ? '443' : '80';
    
        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
          $host = "$host:$port";
        }
        return "$scheme://$host$path";
    }

    /**
     * builds a url usable for a GET request
     */
    private function to_url($params) {
        $post_data = RequestUtil::build_http_query($params);
        $out = $this->get_normalized_http_url();
        if ($post_data) {
            $out .= '?'.$post_data;
        }
        return $out;
    }
}