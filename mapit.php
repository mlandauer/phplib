<?
# this part from rabxtophp-all.sh 

require_once('votingarea.php');

/* Error codes */
define('MAPIT_BAD_POSTCODE', 2001);        /* not in the format of a postcode */
define('MAPIT_POSTCODE_NOT_FOUND', 2002);  /* postcode not found */
define('MAPIT_AREA_NOT_FOUND', 2003);      /* not a valid voting area id */
?>
<?php
/* 
 * THIS FILE WAS AUTOMATICALLY GENERATED BY ./rabxtophp.pl, DO NOT EDIT DIRECTLY
 * 
 * mapit.php:
 * Implementation of MaPit
 *
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * WWW: http://www.mysociety.org
 *
 * $Id: mapit.php,v 1.16 2005/06/25 07:30:10 francis Exp $
 *
 */

require_once('rabx.php');

/* mapit_get_error R
 * Return FALSE if R indicates success, or an error string otherwise. */
function mapit_get_error($e) {
    if (!rabx_is_error($e))
        return FALSE;
    else
        return $e->text;
}

/* mapit_check_error R
 * If R indicates failure, displays error message and stops procesing. */
function mapit_check_error($data) {
    if ($error_message = mapit_get_error($data))
        err($error_message);
}

$mapit_client = new RABX_Client(OPTION_MAPIT_URL);


/* mapit_get_generation

  Return current MaPit data generation. */
function mapit_get_generation() {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_generation', $params);
    return $result;
}

/* mapit_get_voting_areas POSTCODE

  Return voting area IDs for POSTCODE. */
function mapit_get_voting_areas($postcode) {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_voting_areas', $params);
    return $result;
}

/* mapit_get_voting_area_info AREA

  Return information about the given voting. Return value is a reference to
  a hash containing elements,

  * type

    OS-style 3-letter type code, e.g. "CED" for county electoral division;

  * name

    name of voting area;

  * parent_area_id

    (if present) the ID of the enclosing area. */
function mapit_get_voting_area_info($area) {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_voting_area_info', $params);
    return $result;
}

/* mapit_get_voting_areas_info ARY */
function mapit_get_voting_areas_info($ary) {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_voting_areas_info', $params);
    return $result;
}

/* mapit_get_example_postcode ID

  Given an area ID, returns one postcode that maps to it. */
function mapit_get_example_postcode($id) {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_example_postcode', $params);
    return $result;
}

/* mapit_get_voting_area_children ID */
function mapit_get_voting_area_children($id) {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_voting_area_children', $params);
    return $result;
}

/* mapit_get_location POSTCODE

  Return the location of the given POSTCODE. The return value is a reference to
  a hash containing elements,

  * coordsyst
  * easting
  * northing

    Coordinates of the point in a UTM coordinate sys� tem. The coordinate
    system is identified by the coordsyst element, which is "G" for OSGB (the
    Ord� nance Survey "National Grid" for Great Britain) or "I" for the Irish
    Grid (used in the island of Ireland).

  * wgs84_lat
  * wgs84_lon

    Latitude and longitude in the WGS84 coordinate system, expressed as decimal
    degrees, north- and east-positive. */

function mapit_get_location($postcode) {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.get_location', $params);
    return $result;
}

/* mapit_admin_get_stats */
function mapit_admin_get_stats() {
    global $mapit_client;
    $params = func_get_args();
    $result = $mapit_client->call('MaPit.admin_get_stats', $params);
    return $result;
}


?>
