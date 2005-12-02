<?php
/*
 * tracking.php:
 * Interface to our web tracking stuff.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: tracking.php,v 1.1 2005/12/02 18:48:55 chris Exp $
 * 
 */

require_once('utility.php');

/* track_code [EXTRA]
 * Return some HTML which will cause the page visit to be
 * recorded (by a browser suffering from the relevant privacy problem this
 * exploits). If specified, EXTRA should be a simple string which will be
 * recorded with the visit. */
function track_code($extra = null) {
    if (!OPTION_TRACKING)
        return '';
    $salt = sprintf('%08x', rand());
    $url = invoked_url();
    $sign = null;
    $img = null;
    if (is_null($extra)) {
        $img = new_url(OPTION_TRACKING_URL, false,
                    "salt", $salt,
                    "url", $url,
                    "sign", $sign
                );
        $sign = sha1(OPTION_TRACKING_SECRET . "\0$salt\0$url\0");
    } else {
        $sign = sha1(OPTION_TRACKING_SECRET . "\0$salt\0$url\0$other\0");
        $img = new_url(OPTION_TRACKING_URL, false,
                    "salt", $salt,
                    "url", $url,
                    "extra", $extra,
                    "sign", $sign
                );
    }

    return '<img alt="" src="' . $img . '">'
}

/* track_event [EXTRA]
 * Like track_code, above, but actually output the HTML immediately. */
function track_event($extra = null) {
    print track_code($extra);
}

?>
