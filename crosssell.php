<?
/*
 * crosssell.php:
 * Adverts from one site to another site.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: crosssell.php,v 1.1 2006/04/28 17:14:10 francis Exp $
 * 
 */

// Config parameters site needs set to call these functions:
// OPTION_AUTH_SHARED_SECRET
// OPTION_HEARFROMYOURMP_BASE_URL
// MaPit and DaDem

require_once 'dadem.php';
require_once 'mapit.php';

function crosssell_display_hfymp_advert($user_email, $user_name, $postcode) {
    $auth_signature = auth_sign_with_shared_secret($user_email, OPTION_AUTH_SHARED_SECRET);

    // See if already signed up
    $already_signed = file_get_contents(OPTION_HEARFROMYOURMP_BASE_URL.'/authed?email='.urlencode($user_email)."&sign=".urlencode($auth_signature));
    if ($already_signed != 'not signed') 
        return false;

    // If not, display advert
?>
<h2 style="padding: 1em; font-size: 200%" align="center">
Meanwhile... Start a
<a href="<?=OPTION_HEARFROMYOURMP_BASE_URL?>/?name=<?=urlencode($user_name)?>&email=<?=urlencode($user_email)?>&pc=<?=urlencode($postcode)?>&sign=<?=urlencode($auth_signature)?>">long term relationship</a><br> with your MP
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

function crosssell_display_twfy_alerts_advert($user_email, $postcode) {
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

    $person_id = str_replace("uk.org.publicwhip/person/", "", $rep_info['parlparse_person_id']);
    if (!$person_id) {
        return false;
    }
?>

<h2 style="padding-top: 1em; font-size: 200%">Would you like to be emailed when your MP talks in parliament?</h2>
<form style="text-align: center" action="http://www.theyworkforyou.com/alert">
                <strong>Your email:</strong> <input type="text" name="email" value="<?=$user_email ?>" maxlength="100" size="30" />
                <input type="hidden" name="pid" value="<?=$person_id?>">            
                <input type="submit" value="Sign me up!" />
                <input type="hidden" name="submitted" value="true" />
                <input type="hidden" name="pg" value="alert" />
</form>

<p><a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>, which sends these email alerts, is 
another <a href="http://www.mysociety.org">mySociety</a> site and we will treat
your data with the same diligence as we do on all our sites.  Obviously, you
can unsubscribe at any time.
<?  
    return true;
}

// Choose appropriate advert and display it.
// $this_site is stop a site advertising itself.
function crosssell_display_advert($this_site, $user_email, $user_name, $postcode) {
    if ($this_site != "hfymp") 
        if (crosssell_display_hfymp_advert($user_email, $user_name, $postcode))
            return;
    if ($this_site != "twfy")
        if (crosssell_display_twfy_alerts_advert($user_email, $postcode))
            return;
    /* if ($this_site != "pb")
        if (crosssell_display_pb_local_pledges($postcode))
            return; */
    if ($this_site != "pb") {
?>
<h2 style="padding: 1em; font-size: 200%" align="center">
Have you ever wanted to <a href="http://www.pledgebank.com">change the world</a> but stopped short because no-one would help?</h2>
<?
    }
}

