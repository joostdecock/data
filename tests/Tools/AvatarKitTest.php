<?php

namespace Freesewing\Data\Tests\Tools;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Tools\AvatarKit;
use Freesewing\Data\Objects\User;

class AvatarKitTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testGetDirUser()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $this->assertEquals($obj->getDir('handl'), '/users/h/handl/account');
    }

    public function testGetDirModel()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $this->assertEquals($obj->getDir('handl', 'model', 'queen'), '/users/h/handl/models/queen');
    }

    public function testGetDiskDirUser()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['storage']['static_path'];
        $this->assertEquals($obj->getDiskDir('handl'), "$static/users/h/handl/account");
    }

    public function testGetDiskDirModel()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['storage']['static_path'];
        $this->assertEquals($obj->getDiskDir('handl', 'model', 'queen'), "$static/users/h/handl/models/queen");
    }

    public function testGetWebDirUser()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['app']['static_path'];
        $this->assertEquals($obj->getWebDir('handl'), "$static/users/h/handl/account");
    }

    public function testGetWebDirModel()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['app']['static_path'];
        $this->assertEquals($obj->getWebDir('handl', 'model', 'queen'), "$static/users/h/handl/models/queen");
    }

    public function testCreate()
    {
        $obj = new AvatarKit($this->app->getContainer());
        $file = $obj->create('handl');

        $this->assertEquals($file, 'handl.svg');
        $this->assertTrue(file_exists($obj->getDiskDir('handl')));
    }

    public function testCreateFromUri()
    {
        // Own SVG
        $obj1 = new AvatarKit($this->app->getContainer());
        $file1 = $obj1->create('origi');
        $uri1 = $obj1->getDiskDir('origi').'/origi.svg';
        
        $obj2 = new AvatarKit($this->app->getContainer());
        $file2 = $obj2->createFromUri($uri1, 'cloned');
        $uri2 = $obj2->getDiskDir('cloned').'/cloned.svg';
        
        $this->assertEquals(md5(file_get_contents($uri1)), md5(file_get_contents($uri2)));

        $handle = 'fetch';
        // PNG
        $obj = new AvatarKit($this->app->getContainer());
        $src = 'file://'.__DIR__.'/../fixtures/avatar.png';
        $obj->createFromUri($src, $handle);
        $uri = $obj->getDiskDir($handle)."/$handle.png";
        $this->assertTrue(is_file($uri));

        // JPG
        $obj = new AvatarKit($this->app->getContainer());
        $src = 'file://'.__DIR__.'/../fixtures/avatar.jpg';
        $obj->createFromUri($src, $handle);
        $uri = $obj->getDiskDir($handle)."/$handle.jpg";
        $this->assertTrue(is_file($uri));
        
        // GIF
        $obj = new AvatarKit($this->app->getContainer());
        $src = 'file://'.__DIR__.'/../fixtures/avatar.gif';
        $obj->createFromUri($src, 'fetch');
        $uri = $obj->getDiskDir('fetch').'/fetch.gif';
        $this->assertTrue(is_file($uri));
        
        $handle = 'loadw';
        // Load from web
        $obj = new AvatarKit($this->app->getContainer());
        $src = 'https://freesewing.org/img/logo/logo-flag.png';
        $obj->createFromUri($src, $handle);
        $uri = $obj->getDiskDir($handle)."/$handle.png";
        $this->assertTrue(is_file($uri));
    }

    public function testCreateFromDataString()
    {
        $data = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAACnVBMVEUAAAC3NyeCSxQAAAAAAADSZiMAAAB4v+7MWCcEAAC+jTx+xPHOcChVpt6iJSNsuuxsuuzXXydsuuzGTieLIyB4GhpuHxxxvOxqGRdRExK4OicLAgItCgrxxx3edijghieJzfV8yPbc1L2bgUWTPy+wMCd8t+LbZifGmC/w311teZvJTyiXMCfGYjPOVCjG19lWl9OEHB3CSijntyvmmSe1NieBIBy0QCW+TCifPS3HkWWZIyJoreGezu3cayfZrUmqKya/RCj75BK9m4NLDxHUaijggCj86Q7gfyqzYCnDVSq9hiw2DgsuDAuzQSnfcCeTMh6IwOlgFxbVWyfXYifISifRUSfBQSjSVSctVp7YbCjYZifUVyfMTCcdG027OyevLSZYsOhQrOMuf8QydLo0arP875MoRIYlOXgqndolj88xX6glRY0iM28fJlwqHEXIaS6VOC7glSjdgijadCjbbijGRijJVidetOlCqOJJqOE5pN4kmtk2jc1DickzYq1XcqIqS5AlQIOvroAzQXb56W777GYiLWMfIVU2Mk+8qkkbFkVdNz+kRzOORjHHXi+xUi702y3RbCvbeingnyjfjijjgCi9RijjmibmqiTvxR1Dodq8yNWQss5Nks6Mpc0qhskwk8dCnMVfkMX788NchsA0iryxv7pirLnd3LVKj6/z661SeKw7Y6dGaqZMeaJ8hqAoUZ3r4pvfx5paf5ppj5EsTZCtk4yBqYr/8XxcZHd7cXY7SXJLUm2PdGV3dV1LSFvRf1lqTln87VT861CNXkj01kP540GfYEAkG0Ctdz3NdD19bTxTHjy/lTtsODq3eTjNiDbMdjVpWzVwJzWUVjH00DCqVS+rhC3UjizVgirehynpvCLqsiL12RyCIu6zAAAAU3RSTlMA/gQcERAIRy8m/TL++vbz69nRxMKrqJmXc09CPDErHRcJ/ff08/Ls6ufm5ubl5dzb0c7KxcG5uLe2sqyqoZqTjo2LioiGfGVWVFJRTEw/PTQpHrmhBvoAAAIFSURBVCjPYoADBW52dm4FBjSgyC4uJMDPLyAkzq6IEGVi4LbhrUpKrKhITOJVsw2Eict5sBvU1S5akZSYmFQ1e95cfWdWiISrrlbD+q1dO6orK6sza+sWL1eykgcbZBFacOnilK6FNZmZNVnJKUvnrN5szwSUYBUOKS0pnsG1ZdWyrKwFySmp85ekacoCJfzVo6NKiot4+k/vTgYKp2avW5l22B0oYRcSElUyeeqtg9vXpKRmR0ZGbmve2ebEwCAvGBwSHTX59ox9a+vrsyMb09MbmlvTjDkY5AQjgkOiogpO7doYCRRuysjdsKktjcuTgUMvLDgkZHpvS3pOTg5QuD1v/5HjrcregDFwaMdGBJeH9h/KyGjZuwco3nHm2MSJGrJA15aFxUQER086cfTCTa4DeXGd567dm2UC9LxjeGhYTDDQBSHlhT2dHXGX7866M8UN6FwpRrBMeeGVs/lxcXEnb8ycWcTjBfK5KWN4LFBmwvn87u78nqtFU4sLVPwYQFpUQXoigoMn9PVdnzRtWlR0ryUrOBQdEhjDy0CaQkKiS0tDQiKMOCDhHmSdwBgfHhobFgF0RHBEqHkANAJZZET5QFJloaGxsdMLRWRYmCAybMzSLoZ8CUC5+HhGHTFfZjZY5LKxcEpLSoiKmIlJSPpwskDEocaxsTAzc3IyM7OwQY0BANeqq70ox7BgAAAAAElFTkSuQmCC';
        $obj = new AvatarKit($this->app->getContainer());
        $obj->createFromDataString($data, 'dspng');
        $uri = $obj->getDiskDir('dspng').'/dspng.png';
        $this->assertTrue(is_file($uri));

        $data = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAgAAZABkAAD/7AARRHVja3kAAQAEAAAAZAAA/+4ADkFkb2JlAGTAAAAAAf/bAIQAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQICAgICAgICAgICAwMDAwMDAwMDAwEBAQEBAQECAQECAgIBAgIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMD/8AAEQgAFwAXAwERAAIRAQMRAf/EAHkAAAMBAQAAAAAAAAAAAAAAAAYJCgQLAQEAAgMAAAAAAAAAAAAAAAAHBAYCAwUQAAEFAQEAAwEBAAAAAAAAAAUCAwQGBwEIEhMJERURAAIBAgQEBQMFAQAAAAAAAAECAxEEIRITBQAxQQZRcSIUB2GRMoFCciMWFf/aAAwDAQACEQMRAD8A33X0fcNooHorRthN7xaPTFn9g6vWwFcCJ9DQJVZyGrXwvSc7oWX1ijtQ68JqcKAOit8Zht9JEpzqpUjrz0hbvaJ2pedxr3v8g/8AU1Ye2rz4ta12tpAhgl3g7vG8lvaFq0v2tI0eqZZFjDZXAMgKX8kbdsH+o+B/8SySWdlNudz3U0JXJBLK95HaHdQxBymEQaEbhogpSRVBcsRZ3QmJAJgLAvJKwGVlideXU6r6ZvNm0Hp+vROTbHUxwQBoM5q03UFB4p2WECzSZtrnOpTFW6laE0247Q+RrfbB3DLa3g2eViqSZ61YcwVDk4fxpzAxBom2vdvxlc7+dji3HbWv0AZo9JVGWvMMYwKHkDUDljiDwr/y3sP6j4fe9DdKG/cbEPbKR6gyQ9SLxW96JZ7YanasX1VedlA8A2Ed/wAjRa2cgDpYCYOdYJpVxMXnVfe624lO+2rtccVgs/vDG+t6ZBRNECvrxaQyVGVMQRVajNwYqNxfe5Tu72gsVkQ25zWpjM/uVKiMQkssAgDGV58sfJScxUCs71vn71t/Kr1poeP1KtQ/WcPL9JD53sMUSPh6sMZdvh+pHogG9dQ2UBlZeeS5waIQQ8y/DZlJ5x5tCOKRB2m8WORkkA0/cGvlqHGvPEYHnTpxyd1tGuJxp/mYBQdCdIZajz5da04g/wAYtOlSM+r+O2igXKDmkSw2aSIS5NmwbBSLq6zAcpBSjQI8mCeomgUW1jGVuE+tttjB6H3ZPxa4p1atPPIm2r7qF/ZO5OqcV0yAUWM8vTTBQSBUjKDiRsWW0azwbete/tQZSAwcdGLilMhX8mJoy060HHQx9ObTsD3hjzO69eDsTaXqJnpaZbW5s1os5o0DKbgRFXGQlPOurKSykOKU4vrfXFOrSvqeK78eFku6LLexR1OkrE88cFbHzp9v14XrOxKRXDEKJTHQYYVLKKfxLHl4CnAlR71oWNidPyq8ZOf0rPgVk1FQrW8rm0jQsmvuZGbZZi8czLJRLYntcIjhJFcE8KKMMKhTokjiVPxuoeVXLlL61vJUt1E0BdyGR0OBY1BGYEEHAgg414sYFneQRT6hhuNNAUdXDAqoFR6SGDUzKfA+OHCjheefnGA2KNpnCM82zGMslg+bkb9mk2sNGFvx3YbToaXqy2CUZt9bP0wn0vRf59aeNfHiOckvPv5tcmjN7boarT645qeZ+/GbR2eo39sGthWivm+n7K/fry4Zlbrps2t69mHHcptmX03MbGT1KwWTVCdHqZm5GKjRbjJpWSZJUJVzesWhaZotkfjx4seOw3FixW33XX+O8ZZc59pb3kszTz6cYSGQqpkjLu2RsoADUA8enOppxqk9nb25iR2leR1WqrJlQF1zO7ZRTLTz+mPH/9k=';

        $obj = new AvatarKit($this->app->getContainer());
        $obj->createFromDataString($data, 'dsjpg');
        $uri = $obj->getDiskDir('dsjpg').'/dsjpg.jpg';
        $this->assertTrue(is_file($uri));

        $data = 'data:image/gif;base64,R0lGODlhFAAUAJEDAMzMzLOzs39/f////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFCgADACwAAAAAFAAUAAACPJyPqcuNItyCUJoQBo0ANIxpXOctYHaQpYkiHfM2cUrCNT0nqr4uudsz/IC5na/2Mh4Hu+HR6YBaplRDAQAh+QQFCgADACwEAAIADAAGAAACFpwdcYupC8BwSogR46xWZHl0l8ZYQwEAIfkEBQoAAwAsCAACAAoACgAAAhccMKl2uHxGCCvO+eTNmishcCCYjWEZFgAh+QQFCgADACwMAAQABgAMAAACFxwweaebhl4K4VE6r61DiOd5SfiN5VAAACH5BAUKAAMALAgACAAKAAoAAAIYnD8AeKqcHIwwhGntEWLkO3CcB4biNEIFACH5BAUKAAMALAQADAAMAAYAAAIWnDSpAHa4GHgohCHbGdbipnBdSHphAQAh+QQFCgADACwCAAgACgAKAAACF5w0qXa4fF6KUoVQ75UaA7Bs3yeNYAkWACH5BAUKAAMALAIABAAGAAwAAAIXnCU2iMfaRghqTmMp1moAoHyfIYIkWAAAOw==';
        
        $obj = new AvatarKit($this->app->getContainer());
        $obj->createFromDataString($data, 'dsgif');
        $uri = $obj->getDiskDir('dsgif').'/dsgif.gif';
        $this->assertTrue(is_file($uri));
    }
}
