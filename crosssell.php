<?
/*
 * crosssell.php:
 * Adverts from one site to another site.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: crosssell.php,v 1.16 2007/11/19 14:07:48 matthew Exp $
 * 
 */

// Config parameters site needs set to call these functions:
// OPTION_AUTH_SHARED_SECRET
// OPTION_HEARFROMYOURMP_BASE_URL
// MaPit and DaDem

require_once 'auth.php';
require_once 'mapit.php';
require_once 'dadem.php';
require_once 'debug.php';

/* At present, this advert will always display if picked */
function crosssell_display_gny_advert() {
?>

<h2 style="font-size: 200%" align="center">
Are you a member of a local email list or other local online community?
</h2>

<p style="font-size: 150%;">
If so, please help the charity that runs this site by giving us two
minutes of your time to <a href="http://www.groupsnearyou.com/add/about/">add
the group to our new site GroupsNearYou</a>.
</p>

<?
    return true;
}

function crosssell_display_hfymp_advert($user_email, $user_name, $postcode) {
    if (!defined('OPTION_AUTH_SHARED_SECRET') || !defined('OPTION_HEARFROMYOURMP_BASE_URL'))
        return false;

    $auth_signature = auth_sign_with_shared_secret($user_email, OPTION_AUTH_SHARED_SECRET);

    // See if already signed up
    $already_signed = @file_get_contents(OPTION_HEARFROMYOURMP_BASE_URL.'/authed?email='.urlencode($user_email)."&sign=".urlencode($auth_signature));
    if ($already_signed != 'not signed') 
        return false;

    // If not, display advert
?>
<form action="<?=OPTION_HEARFROMYOURMP_BASE_URL?>" method="post">
<input type="hidden" name="name" value="<?=htmlspecialchars($user_name)?>">
<input type="hidden" name="email" value="<?=htmlspecialchars($user_email)?>">
<input type="hidden" name="pc" value="<?=htmlspecialchars($postcode)?>">
<input type="hidden" name="sign" value="<?=htmlspecialchars($auth_signature)?>">
<h2 style="padding: 1em; font-size: 200%" align="center">
Meanwhile...<br>
<input style="font-size:100%" type="submit" value="Start a long term relationship"><br> with your MP
</h2>
<?
    return true;
}

function crosssell_display_pb_local_pledges($postcode) {
    $local_pledges = file_get_contents('http://www.pledgebank.com/rss?postcode=' . urlencode($postcode));
    preg_match_all('#<link>(.*?)</link>\s+<description>(.*?)</description>#', $local_pledges, $m, PREG_SET_ORDER);
    $local_num = count($m) - 1;
    if ($local_num > 5) $local_num = 5;
    if ($local_num) {
        print '<div id="pledges"><h2>Recent pledges local to ' . canonicalise_postcode($postcode) . '</h2>';
        print '<p style="margin-top:0; text-align:right; font-size: 89%">These are pledges near you made by users of <a href="http://www.pledgebank.com/">PledgeBank</a>, another mySociety site. We thought you might be interested. N.B. mySociety does not endorse specific pledges.</p> <ul>';
        for ($p=1; $p<=$local_num; ++$p) {
            print '<li><a href="' . $m[$p][1] . '">' . $m[$p][2] . '</a>';
        }
        print '</ul><p align="center"><a href="http://www.pledgebank.com/alert?postcode='.$postcode.'">Get emails about local pledges</a></p></div>';
    } else {
        return false;
    }
    return true;
}

# XXX: Needs to say "Lord" when the WTT message was to a Lord!
function crosssell_display_twfy_alerts_advert($this_site, $user_email, $postcode) {
    // Look up who the MP is
    $voting_areas = mapit_get_voting_areas($postcode);
    mapit_check_error($voting_areas);
    if (!array_key_exists('WMC', $voting_areas)) {
        return false;
    }
    $reps = dadem_get_representatives($voting_areas['WMC']);
    dadem_check_error($reps);
    if (count($reps) != 1) {
        return false;
    }
    $rep_info = dadem_get_representative_info($reps[0]);
    dadem_check_error($rep_info);

    if (!array_key_exists('parlparse_person_id', $rep_info)) {
        return false;
    }
    $person_id = str_replace("uk.org.publicwhip/person/", "", $rep_info['parlparse_person_id']);
    if (!$person_id) {
        return false;
    }

    $auth_signature = auth_sign_with_shared_secret($user_email, OPTION_AUTH_SHARED_SECRET);
    // See if already signed up
    $fp = fsockopen('www.theyworkforyou.com', 80, $errno, $errstr, 5);
    if (!$fp)
        return false;
    stream_set_blocking($fp, 0);
    stream_set_timeout($fp, 5);
    $sockstart = getmicrotime();
    fputs($fp, 'GET /alert/authed.php?pid='.$person_id.'&email='.urlencode($user_email)."&sign=".urlencode($auth_signature)." HTTP/1.0\r\nHost: www.theyworkforyou.com\r\n\r\n");
    $already_signed = '';
    while (!feof($fp) and (getmicrotime() < $sockstart + 5)) {
        $already_signed .= fgets($fp, 1024);
    }
    fclose($fp);
    if ($already_signed != 'not signed')
        return false;
?>

<h2 style="border-top: solid 3px #9999ff; font-weight: normal; padding-top: 1em; font-size: 150%">Seeing as you're interested in your MP, would you also like to be emailed when they say something in parliament?</h2>
<form style="text-align: center" action="http://www.theyworkforyou.com/alert/">
    <strong>Your email:</strong> <input type="text" name="email" value="<?=$user_email ?>" maxlength="100" size="30">
    <input type="hidden" name="pid" value="<?=$person_id?>">            
    <input type="submit" value="Sign me up!">
    <input type="hidden" name="submitted" value="true">
    <input type="hidden" name="pg" value="alert">
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

// Choose appropriate advert and display it.
// $this_site is stop a site advertising itself.
function crosssell_display_advert($this_site, $user_email, $user_name, $postcode) {
    if ($this_site != 'gny' && $this_site == 'wtt') # Only WTT at the moment, will always show.
        if (crosssell_display_gny_advert())
            return 'gny';
    if ($this_site != "hfymp") 
        if (crosssell_display_hfymp_advert($user_email, $user_name, $postcode))
            return 'hfymp';
    if ($this_site != "twfy") {
        if (crosssell_display_twfy_alerts_advert($this_site, $user_email, $postcode))
            return 'twfy';
    } else {
        return 'other-twfy-alert-type';
    }
    /* if ($this_site != "pb")
        if (crosssell_display_pb_local_pledges($postcode))
            return; */
    if ($this_site != "pb") {
?>
<h2 style="padding: 1em; font-size: 200%" align="center">
Have you ever wanted to <a href="http://www.pledgebank.com">change the world</a> but stopped short because no-one would help?</h2>
<?
        return 'pb';
    }
    return '';
}

