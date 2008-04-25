<?
/*
 * crosssell.php:
 * Adverts from one site to another site.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: crosssell.php,v 1.20 2008/01/31 11:56:05 twfy-live Exp $
 * 
 */

// Config parameters site needs set to call these functions:
// OPTION_AUTH_SHARED_SECRET
// MaPit and DaDem

require_once 'auth.php';
require_once 'mapit.php';
require_once 'dadem.php';
require_once 'debug.php'; # for getmicrotime()

# Global
$crosssell_voting_areas = array();

/* Random adverts, text supplied by caller */

function crosssell_display_random_twfy_alerts_advert($email, $name, $postcode, $text, $this_site) {
    $check = crosssell_check_twfy($email, $postcode);
    if (is_bool($check)) return false;
    list($person_id, $auth_signature) = $check;

    $text = str_replace('[form]', '
<form action="http://www.theyworkforyou.com/alert/" method="post">
    <strong>Your email:</strong> <input type="text" name="email" value="' . $email . '" maxlength="100" size="30">
    <input type="hidden" name="pid" value="' . $person_id . '">
    <input type="hidden" name="submitted" value="true">
    <input type="hidden" name="sign" value="' . $auth_signature . '">
    <input type="hidden" name="site" value="' . $this_site . '">
    <input type="submit" value="', $text);
    $text = str_replace('[/form]', '"></form>', $text);
    $text = str_replace('[button]', '
<form action="http://www.theyworkforyou.com/alert/" method="post">
    <input type="hidden" name="email" value="' . $email . '">
    <input type="hidden" name="pid" value="' . $person_id . '">
    <input type="hidden" name="sign" value="' . $auth_signature . '">
    <input type="hidden" name="site" value="' . $this_site . '">
    <input style="font-size:150%" type="submit" value="', $text);
    $text = str_replace('[/button]', '"></p>', $text);

    echo '<div id="advert_thin" style="text-align:center">', $text, '</div>';
    return true;
}

/* Okay, now the static adverts, not being shown at random */

# XXX: Needs to say "Lord" when the WTT message was to a Lord!
function crosssell_display_twfy_alerts_advert($this_site, $email, $postcode) {
    $check = crosssell_check_twfy($email, $postcode);
    if (is_bool($check)) return false;
    list($person_id, $auth_signature) = $check;
?>

<h2 style="border-top: solid 3px #9999ff; font-weight: normal; padding-top: 1em; font-size: 150%">Seeing as you're interested in your MP, would you also like to be emailed when they say something in parliament?</h2>
<form style="text-align: center" action="http://www.theyworkforyou.com/alert/">
    <strong>Your email:</strong> <input type="text" name="email" value="<?=$email ?>" maxlength="100" size="30">
    <input type="hidden" name="pid" value="<?=$person_id?>">            
    <input type="submit" value="Sign me up!">
    <input type="hidden" name="submitted" value="true">
    <input type="hidden" name="sign" value="<?=$auth_signature?>">
    <input type="hidden" name="site" value="<?=$this_site?>">
</form>

<p>Parliament email alerts are a free service of <a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>,
another <a href="http://www.mysociety.org">mySociety</a> site. We will treat
your data with the same diligence as we do on all our sites, and obviously you
can unsubscribe at any time.
<?  
    return true;
}

/* Checking functions for sites, to see if you're already signed up or whatever */

function crosssell_check_twfy($email, $postcode) {
    if (!defined('OPTION_AUTH_SHARED_SECRET') || !$postcode)
        return false;

    // Look up who the MP is
    global $crosssell_voting_areas;
    if (!$crosssell_voting_areas)
        $crosssell_voting_areas = mapit_get_voting_areas($postcode);
    mapit_check_error($crosssell_voting_areas);
    if (!array_key_exists('WMC', $crosssell_voting_areas)) {
        return false;
    }
    $reps = dadem_get_representatives($crosssell_voting_areas['WMC']);
    dadem_check_error($reps);
    if (count($reps) != 1) {
        return false;
    }
    $rep_info = dadem_get_representative_info($reps[0]);
    dadem_check_error($rep_info);

    if (!array_key_exists('parlparse_person_id', $rep_info)) {
        return false;
    }
    $person_id = str_replace('uk.org.publicwhip/person/', '', $rep_info['parlparse_person_id']);
    if (!$person_id) {
        return false;
    }

    $auth_signature = auth_sign_with_shared_secret($email, OPTION_AUTH_SHARED_SECRET);
    // See if already signed up
    $already_signed = crosssell_fetch_page('www.theyworkforyou.com', '/alert/authed.php?pid='.$person_id.'&email='.urlencode($email).'&sign='.urlencode($auth_signature));
    if ($already_signed != 'not signed')
        return false;

    return array($person_id, $auth_signature);
}

function crosssell_fetch_page($host, $url) {
    $fp = fsockopen($host, 80, $errno, $errstr, 5);
    if (!$fp)
        return false;
    stream_set_blocking($fp, 0);
    stream_set_timeout($fp, 5);
    $sockstart = getmicrotime();
    fputs($fp, "GET $url HTTP/1.0\r\nHost: $host\r\n\r\n");
    $response = '';
    $body = false;
    while (!feof($fp) and (getmicrotime() < $sockstart + 5)) {
        $s = fgets($fp, 1024);
        if ($body)
            $response .= $s;
        if ($s == "\r\n")
            $body = true;
    }
    fclose($fp);
    return $response;
}
