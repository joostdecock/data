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

    public function getDir($userHandle, $type='user', $typeHandle=null)
    {
        $base = '/users/'.substr($userHandle,0,1).'/'.$userHandle;
        switch($type) {
        case 'model':
            return $base.'/'.$type.'s/'.$typeHandle;
        break;
        default:
            return $base.'/account';
        }
    }
    
    public function getDiskDir($userHandle, $type='user', $typeHandle=null)
    {
        return $this->container['settings']['storage']['static_path'].$this->getDir($userHandle, $type, $typeHandle);
    }
    
    public function getWebDir($userHandle, $type='user', $typeHandle=null)
    {
        return $this->container['settings']['app']['static_path'].$this->getDir($userHandle, $type, $typeHandle);
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
    public function create($userHandle,$type='user', $typeHandle=null) 
    {
        // Load avatar with random colours
        $svg = str_replace(
            ['000000','FFFFFF'], 
            [dechex(rand(0x000000, 0xFFFFFF)), dechex(rand(0x000000, 0xFFFFFF))], 
            file_get_contents($this->container['settings']['renderer']['template_path'].'avatar.svg')
        );
        ($typeHandle === null) ? $handle = $userHandle : $handle = $typeHandle;

        return $this->saveAvatar("$handle.svg", $svg, $userHandle, $type, $typeHandle);
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
    public function createFromMmp($img,$userHandle,$type='user', $typeHandle=null) 
    {
        return $this->createFromUri($this->container['settings']['mmp']['public_path'].substr($img->uri,8), $userHandle, $type, $typeHandle);
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
    public function createFromUri($uri, $userHandle,$type='user', $typeHandle=null) 
    {
        ($typeHandle === null) ? $handle = $userHandle : $handle = $typeHandle;

        // If it's one of our auto-generated SVGs, simply copy
        if(substr($uri,-4) == '.svg') return $this->saveAvatar($handle.'.svg', file_get_contents($uri), $userHandle, $type, $typeHandle);

        // Imagick instance with the user's picture
        $imagick = new \Imagick($uri);

        $imagick = $this->thumbnail($imagick);
        
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
        return $this->saveAvatar($handle.$ext, $imagick->getImageBlob(), $userHandle, $type, $typeHandle);
    }

    /**
     * Loads an avater from a data string
     *
     * @param string $data The original data string
     * @param string $handle The user handle
     * @param string $type One of user/model/draft
     *
     * @return string The avatar filename
     */
    public function createFromDataString($data, $userHandle,$type='user', $typeHandle=null) 
    {
        $base64data = substr($data,strpos($data,'base64,')+7);
        $filepath = $this->container['settings']['storage']['temp_path']."/$userHandle.tmp";
        if ($handle = fopen($filepath, 'w')) {
            fwrite($handle, base64_decode($base64data));
            fclose($handle);
        } else {
            echo "could not write to file";
            return false;
        }
        
        // Imagick instance with the user's picture
        $imagick = new \Imagick();
        $handle = fopen($filepath, 'r');
        $imagick->readImageFile($handle);
        fclose($handle);

        $imagick = $this->thumbnail($imagick);

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
        ($typeHandle === null) ? $handle = $userHandle : $handle = $typeHandle;
        // Save avatar and return filename
        return $this->saveAvatar($handle.$ext, $imagick->getImageBlob(), $userHandle, $type, $typeHandle);
    }

    private function thumbnail($image, $size=500)
    {
        $w = $image->getImageWidth();
        $h = $image->getImageHeight();

        if ($w > $h) {
            $resize_w = $w * $size / $h;
            $resize_h = $size;
        } else {
            $resize_w = $size;
            $resize_h = $h * $size / $w;
        }
        $image->resizeImage($resize_w, $resize_h, \Imagick::FILTER_LANCZOS, 0.9);
        $image->cropImage($size, $size, ($resize_w - $size) / 2, ($resize_h - $size) / 2);

        return $image;
    }

    private function saveAvatar($filename, $data, $userHandle, $type='user', $typeHandle=null) 
    {
        // Create directory. If handle is joost, directory is static/users/j/joost
        $dir = $this->getDiskDir( $userHandle, $type, $typeHandle);
        if(!is_dir($dir)) mkdir($dir, 0755, true);
        ($typeHandle === null) ? $handle = $userHandle : $handle = $typeHandle;
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
