<?php
$file = '../src/referrals.php';
include($file);

$r = getReferralGroups();
$sites = array_keys($r);
natcasesort($sites);
$s8 = '        ';
$s12 = $s8.'    ';
$s16 = $s8.$s8;
$s20 = $s16.'    ';

$sort = "<?php

if(!function_exists('getReferralGroups')) {
    function getReferralGroups()
    {
        return [";

foreach($sites as $nr => $handle) {
    $site = $r[$handle];
    $sort .= "\n$s12\"$handle\" => [";
    foreach($site as $key => $val) {
        if(is_array($val)) {
            $sort .= "\n$s16'$key' => [";
            foreach($val as $v) $sort .= "\n$s20\"$v\","; 
            $sort .= "\n$s16],";
        } else $sort .= "\n$s16'$key' => '$val',";
    }
    $sort .= "\n$s12],";
}


$sort .= "
        ];
    }
}
";

$handle = fopen($file, 'w');
fwrite($handle, $sort);
fclose($handle);
