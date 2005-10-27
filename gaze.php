<?php
/* 
 * THIS FILE WAS AUTOMATICALLY GENERATED BY ./rabxtophp.pl, DO NOT EDIT DIRECTLY
 * 
 * gaze.php:
 * Implementation of Gaze
 *
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * WWW: http://www.mysociety.org
 *
 * $Id: gaze.php,v 1.9 2005/10/27 14:59:14 francis Exp $
 *
 */

require_once('rabx.php');

/* gaze_get_error R
 * Return FALSE if R indicates success, or an error string otherwise. */
function gaze_get_error($e) {
    if (!rabx_is_error($e))
        return FALSE;
    else
        return $e->text;
}

/* gaze_check_error R
 * If R indicates failure, displays error message and stops procesing. */
function gaze_check_error($data) {
    if ($error_message = gaze_get_error($data))
        err($error_message);
}

$gaze_client = new RABX_Client(OPTION_GAZE_URL);


/* gaze_find_places COUNTRY STATE QUERY [MAXRESULTS [MINSCORE]]

  Search for places in COUNTRY (ISO code) which match the given search
  QUERY. The country must be from the list returned by
  get_gaze_find_places_countries. STATE, if specified, is a customary code for a
  top-level administrative subregion within the given COUNTRY; at present,
  this is only useful for the United States, and should be passed as undef
  otherwise. 

  Returns a reference to a list of [NAME, IN, NEAR, LATITUDE, LONGITUDE,
  STATE, SCORE]. When IN is defined, it gives the name of a region in which
  the place lies; when NEAR is defined, it gives a short list of other
  places near to the returned place. These allow nonunique names to be
  disambiguated by the user. LATITUDE and LONGITUDE are in decimal degrees,
  north- and east-positive, in WGS84. Earlier entries in the returned list
  are better matches to the query. 

  At most MAXRESULTS (default, 20) results, and only results with score at
  least MINSCORE (default 0, percentage from 0 to 100) are returned. The
  MAXRESULTS limit is ignored when the top results all have the same
  relevancy. They are all returned. So for example, this means that if you
  search for Cambridge in the US with MAXRESULTS of 5, it will return all
  the Cambridges, even though there are more than 5 of them.

  On error, throws an exception. */
function gaze_find_places($country, $state, $query, $maxresults = null, $minscore = null) {
    global $gaze_client;
    $params = func_get_args();
    $result = $gaze_client->call('Gaze.find_places', $params);
    return $result;
}

/* gaze_get_find_places_countries

  Return list of countries which find_places will work for. */
function gaze_get_find_places_countries() {
    global $gaze_client;
    $params = func_get_args();
    $result = $gaze_client->call('Gaze.get_find_places_countries', $params);
    return $result;
}

/* gaze_get_country_from_ip ADDRESS

  Return the country code for the given IP address, or undef if none could
  be found. */
function gaze_get_country_from_ip($address) {
    global $gaze_client;
    $params = func_get_args();
    $result = $gaze_client->call('Gaze.get_country_from_ip', $params);
    return $result;
}


?>
