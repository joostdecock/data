<?php
/*
 * You'll need to set some environment variables in your shell
 * for this test script to work. Something like
 *
 * export FREESEWING_DATA_API="https://joost.data.freesewing.org"
 * export FREESEWING_DATA_TEST_PASS="your-password-here"
 * export FREESEWING_DATA_TEST_USER="test@freesewing.org"
 *
 */

$dir = getcwd();
chdir($dir);
chdir('..');

// Failure counter
$fail = 0;


h1("Checking configuration");
// Do we all login info availabe?
$user = getenv('FREESEWING_DATA_TEST_USER');
$pwd = getenv('FREESEWING_DATA_TEST_PASS');
$api = getenv('FREESEWING_DATA_API');
h2('Checking for API config');
p('API URL');
if($api === false) {
    ko();
    p("Please set the FREESEWING_DATA_API environment variable to the DATA API URL");
    $fail++;
} else ok();
h2('Checking for login credentials');
p('Username');
if($user === false) {
    ko();
    p("Please set the FREESEWING_DATA_TEST_USER environment variable with a valid email address to login to the API");
    $fail++;
} else  ok();
p('Password');
if($pwd === false) {
    if($user !== false) ko();
    p("Please set the FREESEWING_DATA_TEST_PASS environment variable with a valid password to login to the API");
    $fail++;
} else  ok();

h1("Checking syntax");
foreach(['settings','routes','middleware', 'dependencies','referrals'] as $file) {
    p("src/$file.php");
    if(substr(`php -l ./src/$file.php`,0,25) != 'No syntax errors detected') {
        ko();
        p("Please fix your $file.php file.");
        $fail++;
    } else ok();
}

h1("Testing DATA API");
h2("Testing anonymous routes");

p('Loading most recent comment');
$fail += testAnon('GET', '/comments/recent/1');

p('Loading Patron list');
$fail += testAnon('GET', '/patrons/list');

h2("Loggin in");
p('Testing login');
$ACCOUNT = login($user, $pwd);
if($ACCOUNT !== false) ok();
else { ko(); $fail++; }

h2("Testing authenticated routes");
p('Loading account data');
$r = testAuth('GET','/account', false, true);
if(is_object($r->account)) {
    ok();
    $ACCOUNT->id = $r->account->id;
    $ACCOUNT->username = $r->account->username;
    $ACCOUNT->email = $r->account->email;
    $ACCOUNT->handle = $r->account->handle;
} else { ko(); $fail++; }

p('Changing username');
$fail += testAuth('PUT','/account',['username' => 'Set by test script', 'email' => $ACCOUNT->email]);

//print_r($r);

// Wrapping up
if($fail > 0) {
    h1("\033[31mTests failed :(\033[0m");
    h2("We encountered $fail warnings/failures.");
    echo "\n\n";
    exit(1);
} else {
    h1("\033[32mTests completed :)\033[0m");
    h2("No warning, no failures. Well done!");
    echo "\n\n";
    exit(0);
}

die();

function testAuth($method, $path, $data=FALSE, $load=false) 
{
    GLOBAL $api;
    GLOBAL $ACCOUNT;
    $dir = getcwd();
    chdir($dir);
    $query = '';
    $cmd = 'curl -s -X '.strtoupper($method)." ";
    if($data) {
        foreach($data as $key => $value) $query .= urlencode($key).'='.urlencode($value).'&';
        $cmd .= " -d \"$query\"";
        }
    $cmd .= " $api$path -H \"Authorization:Bearer ".$ACCOUNT->token."\"";
    $r = json_decode(`$cmd`);
    if($load) return $r;
    if($r->result == 'ok') { ok(); return 0;}
    else { ko(); return 1;}
}

function testAnon($method, $path, $data=FALSE) 
{
    GLOBAL $api;
    $dir = getcwd();
    chdir($dir);
    $query = '';
    $cmd = 'curl -s -X '.strtoupper($method)." ";
    if($data) {
        foreach($data as $key => $value) $query .= urlencode($key).'='.urlencode($value).'&';
        $cmd .= " -d \"$query\"";
        }
    $cmd .= " $api$path";
    $r = json_decode(`$cmd`);
    if($r->result == 'ok') { ok(); return 0;}
    else { ko(); return 1;}
}

function login($user, $pwd) 
{
    GLOBAL $api;
    $dir = getcwd();
    chdir($dir);
    $query = '';
    $cmd = 'curl -s -X POST -d "login-email='.urlencode($user).'&login-password='.urlencode($pwd)."\" $api/login";
    $r = json_decode(`$cmd`);
    if($r->result == 'ok') { 
        $ACCOUNT = new stdClass();
        $ACCOUNT->token = $r->token;
        $ACCOUNT->id = $r->userid;
        return $ACCOUNT;
    } else  return false;
}

function h1($string)
{
    echo "\n\n\033[33m$string\033[0m";
    echo "\n\n\033[33m".str_pad('-', 72, '-')."\033[0m";
}

function h2($string)
{
    echo "\n\n\033[33m  $string\033[0m";
}

function h3($string)
{
    echo "\n\n\033[33m    $string\033[0m";
}

function p($string)
{
    echo "\n".str_pad('    '.$string, 69, ' ');
}

function ok()
{
    echo "\033[32m OK \033[0m";
}

function ko()
{
    echo "\033[31m Problem! \033[0m";
}

function warn()
{
    echo "\033[33m Warning \033[0m";
}
