<?php
/** App\Tools\AvatarKit class */
namespace App\Tools;

/**
 * The AvatarKit class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class AvatarKit 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }

    /** 
     * Creates and stores an avatar
     *
     * This creates a avatar.svg file that is saved to the default location
     *
     * @param string $handle The user handle
     * @param string $type One of user/model/draft
     *
     * @return string|false The filename of the avatar, or false of there's a problem
     */
    public function create($handle, $type) 
    {
        // Load avatar with random colours
        $svg = str_replace(
            ['000000','FFFFFF'], 
            [dechex(rand(0x000000, 0xFFFFFF)), dechex(rand(0x000000, 0xFFFFFF))], 
            file_get_contents($this->container['settings']['renderer']['template_path'].'/avatar.svg')
        );

        return $this->saveAvatar("$handle.svg", $svg, $handle, $type);
    }

    /**
     * Loads an avatar from an MMP uri
     *
     * These uris look like: public://path/to/file.jpg
     *
     * @param string $img a Drupal public uri from MMP
     * @param string $handle The user handle
     * @param string $type One of user/model/draft
     *
     * @return string The avatar filename
     */
    public function createFromMmp($img,$handle,$type) 
    {
        return $this->createFromUri($this->container['settings']['mmp']['public_path'].substr($img->uri,8), $handle, $type);
    }

    /**
     * Loads an avater from a uri
     *
     * @param string $url Either a web url or local file path
     * @param string $handle The user handle
     * @param string $type One of user/model/draft
     *
     * @return string The avatar filename
     */
    public function createFromUri($uri, $handle, $type) 
    {
        // Imagick instance with the user's picture
        $imagick = new \Imagick($uri);

        // Max side?
        $w = $imagick->getImageWidth();
        $h = $imagick->getImageHeight();
        $min = ($w < $h) ? $w : $h;

        if($w != $h || $min > 500) {
            // Generate square/small version
            if($min > 500) $imagick->thumbnailImage(500,500, true);
            else $imagick->thumbnailImage($min,$min, true);
        }
        switch($imagick->getImageMimeType()) {
            case 'image/png':
                $ext = '.png';
            break;
            case 'image/gif':
                $ext = '.gif';
            break;
            case 'image/bmp':
                $ext = '.bmp';
            break;
            default:
                $ext = '.jpg';
        }
        // Save avatar and return filename
        return $this->saveAvatar($handle.$ext, $imagick->getImageBlob(), $handle, $type);
    }

    private function saveAvatar($filename, $data, $handle, $type) 
    {
        // Create directory. If handle is joost, directory is static/[type]s/j/joost
        $dir = $this->container['settings']['storage']['static_path'].'/'.$type.'s/'.substr($handle,0,1).'/'.$handle;
        if(!is_dir($dir)) mkdir($dir, 0755, true);
        
        $filepath = $dir."/$filename";
        if ($handle = fopen($filepath, 'w')) {
            if (fwrite($handle, $data)) {
                fclose($handle);
                return $filename;
            }
            fclose($handle);
        }

        return false;
    }

}
