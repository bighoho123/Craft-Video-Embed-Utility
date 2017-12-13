<?php
/**
 * Craft Video Embed Utility plugin for Craft CMS 3.x
 *
 * Craft v3 port for https://github.com/Staplegun-US/craft-video-embed-utility
 *
 * @link      https://simple.com.au
 * @copyright Copyright (c) 2017 Jinzhe Li
 */

namespace simpleteam\craftvideoembedutility\twigextensions;

use simpleteam\craftvideoembedutility\CraftVideoEmbedUtility;

use Craft;

define("VIMEO",'vimeo.com');
define("YOUTUBE",'youtube.com');
define("YOUTUBE_SHORT",'youtu.be');
define("FACEBOOK",'facebook.com');
/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    Jinzhe Li
 * @package   CraftVideoEmbedUtility
 * @since     0.0.1
 */
class CraftVideoEmbedUtilityTwigExtension extends \Twig_Extension
{
    private static $KNOWN_HOSTS = array(VIMEO,YOUTUBE,YOUTUBE_SHORT,FACEBOOK);
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'CraftVideoEmbedUtility';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('videoPlayerUrl', [$this, 'videoPlayerUrl']),
            new \Twig_SimpleFilter('videoEmbed', [$this, 'videoEmbed']),
            new \Twig_SimpleFilter('videoHost', [$this, 'videoHost']),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
    * @return array
     */
    public function getFunctions()
    {
        return [
        ];
    }

    /**
     * Returns a string indicating where this video is hosted (youtube, vimeo, etc.)
     *
     * @param string $videoUrl
     * @return string
     */
    public function videoHost($videoUrl) {
        $host = parse_url($videoUrl, PHP_URL_HOST);
        // return a sanitized value (no leading www, etc) if it's one we know.
        foreach($this::$KNOWN_HOSTS as $known) {
            if( strpos($videoUrl,$known) !== FALSE ) {
                return $known;
            }
        }
        return $host;
    }

    public function videoId($videoUrl) {
        $host = $this->videoHost($videoUrl);
        switch($host) {
            case VIMEO:
                if(preg_match('/\/([0-9]+)\/*(\?.*)?$/',$videoUrl,$matches) !== false) {
                    return $matches[1];
                }
                break;

            case YOUTUBE:
            case YOUTUBE_SHORT:
                if(preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',$videoUrl,$matches) !== false)
                    return $matches[1];
                break;
        }
        return "";
    }

    public function videoPlayerUrl($input) {
        $videoId = $this->videoId($input);
        switch($this->videoHost($input)) {
            case VIMEO:
                return "//player.vimeo.com/video/$videoId?";
                break;

            case YOUTUBE:
            case YOUTUBE_SHORT:
                return "//www.youtube.com/embed/$videoId?";
                break;

            case FACEBOOK:
                return '//www.facebook.com/plugins/video.php?href=' . urlencode($input) . '&show_text=0';
                break;
        }
        return "";
    }

    /**
     * Returns a boolean indicating whether the string $haystack ends with the string $needle.
     * @param string $haystack the string to be searched
     * @param string $needle the substring we're looking for
     * return boolean
     */
    private function endsWith($haystack, $needle) {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    public function videoEmbed($input, $options = array()) {
        $width = '100%';
        $height = '148';
        $class = '';
        $url = $this->videoPlayerUrl($input);

        if(!empty($url)) {
            if(!empty($options)) {
                if(isset($options['width'])) {
                    $width = $options['width'];
                    unset($options['width']);
                }

                if(isset($options['height'])) {
                    $height = $options['height'];
                    unset($options['height']);
                }

                if(isset($options['class'])) {
                    $class = $options['class'];
                    unset($options['class']);
                }

                $url .= '&' . http_build_query($options);
            }

            $originalPath = Craft::$app->view->getTemplatesPath();
            $myPath = Craft::$app->path->getVendorPath() .DIRECTORY_SEPARATOR. 'simple-team/craft-video-embed-utility/src/templates/';
            Craft::$app->view->setTemplatesPath($myPath);

            $markup = Craft::$app->view->renderTemplate('_vimeoEmbed.html', array(
                'player_url' => $url,
                'width' => $width,
                'height' => $height,
                'class' => $class
            ));
            Craft::$app->view->setTemplatesPath($originalPath);
            return \craft\helpers\Template::raw($markup);
        }
    }
}
