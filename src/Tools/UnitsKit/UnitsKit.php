<?php
/** App\Tools\UnitsKit class */
namespace App\Tools;

/**
 * The UnitsKit class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class UnitsKit 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }
    
    /**
     * Returns input as a float
     *
     * The frontend allows both decimals (6.5) or fractions (6 1/2)
     * This normalizes that input to a float (6.5)
     *
     * @param string $value The user input
     *
     * @return float The input as float
     */
    public function asFloat($value) 
    {
        // Do we have a fraction?
        if(!strpos($value,'/')) return floatval(rtrim($value));
        $fp = fopen('/tmp/debug.log/','w');
        fwrite($fp, "Val is $val");


        $parts = $this->asScrubbedArray($value,'/');

        fwrite($fp,print_r($parts,1));
        $divider = $parts[1];
        $parts = $this->asScrubbedArray($parts[0],' ');
        $inches = $parts[0];
        $fraction = $parts[1];
        fwrite($fp,print_r($parts,1));
        fclose($fp);

        return $inches + ($fraction/$divider);
    }

    private function asScrubbedArray($data, $separator = ' ')
    {
        $array = explode($separator, $data);
        foreach ($array as $value) {
            if (rtrim($value) != '') {
                $return[] = rtrim($value);
            }
        }
        if (isset($return)) {
            return $return;
        } else {
            return false;
        }
    }

}
