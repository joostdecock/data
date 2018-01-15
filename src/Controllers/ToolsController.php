<?php
/** Freesewing\Data\Controllers\ToolsController class */
namespace Freesewing\Data\Controllers;

use Symfony\Component\Yaml\Yaml;

/**
 * Handes tools such as the on-demand tiler
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class ToolsController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /** Tile an SVG */
    public function tile($request, $response, $args) 
    {
        $formats = ['pdf','ps','a4','a3','a2','a1','a0','letter','tabloid'];
        $in = new \stdClass();
        $in->format = $this->scrub($request,'format');
        $in->svg = $this->scrub($request,'svg');

        if(!in_array(strtolower($in->format),$formats)) 
            return $this->prepResponse($response, [ 'result' => 'error', 'reason' => 'invalid_format' ], 400);

        // Decode SVG data string
        if(strpos($in->svg,'data:image/svg+xml;base64,') === 0) $svg = base64_decode(substr($in->svg, 26));
        else return $this->prepResponse($response, [ 'result' => 'error', 'reason' => 'invalid_svg' ], 400);
        
        // Store on disk
        $hash = sha1($svg);
        $filename = "$hash.svg";
        $path = $this->container['settings']['storage']['static_path']."/export/tiler/$hash";
        if(!file_exists($path)) mkdir($path, 0755, true);

        $handle = fopen("$path/$filename", 'w');
        fwrite($handle, $svg);
        fclose($handle);
        
        // Full-size PDF is just a simple Inkscape export
        if($in->format == 'pdf') {
            $cmd = "/usr/bin/inkscape --export-pdf=$path/$hash.pdf $path/$filename";
            $link = $this->container['settings']['app']['data_api']."/static/export/tiler/$hash/$hash.pdf";
        } else if($in->format == 'ps') {
            $cmd = "/usr/bin/inkscape --export-ps=$path/$hash.ps $path/$filename";
            $link = $this->container['settings']['app']['data_api']."/static/export/tiler/$hash/$hash.ps";
        } else {
            // Get Postscript file
            $cmd = "/usr/bin/inkscape --export-ps=$path/$hash.ps $path/$filename";
            shell_exec($cmd);
            // Tile postscript to required format
            $cmd = "/usr/local/bin/tile -a -m".$in->format." -s1 -t\"On-demand tiler\" -h\"../tools/tiler\" $path/$hash.ps > $path/$hash.".strtolower($in->format).".ps";
            // Convert to PDF
            $cmd .= " ; /usr/bin/ps2pdf14 $path/$hash.".strtolower($in->format).".ps $path/$hash.".strtolower($in->format).".pdf";
            $link = $this->container['settings']['app']['data_api']."/static/export/tiler/$hash/$hash.".strtolower($in->format).".pdf";
        }
        shell_exec($cmd);
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'link' => $link,
        ]);
    }
    
    private function scrub($request, $key)
    {
        $val = false;
        if(isset($request->getParsedBody()[$key])) $val = filter_var($request->getParsedBody()[$key], FILTER_SANITIZE_STRING);
        
        return $val;
    }
    
    private function prepResponse($response, $data, $status=200)
    {
        return $response
            ->withStatus($status)
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
   
}
