<?php
/*
 * utility.php:
 * General utility functions. Taken from the TheyWorkForYou.com source
 * code, and licensed under a BSD-style license.
 * 
 * Mainly: Copyright (c) 2003-2004, FaxYourMP Ltd 
 * Parts are: Copyright (c) 2004 UK Citizens Online Democracy
 *
 * $Id: utility.php,v 1.26 2005/01/12 14:49:23 francis Exp $
 * 
 */

/*
 * Magic quotes: these are a unique and unedifying feature of the whole PHP
 * trainwreck. Here we do our best to undo any damage we may have sustained,
 * at the small cost of inserting bowdlerised profanities into our code.
 */

/* unfck VAL
 * If VAL is a scalar, return the result of stripslashes(VAL) (i.e. with any
 * character preceded by a backslash replaced by that character). If VAL is an
 * array, return an array whose elements are the result of this function
 * applied to each element of that array. */
function unfck($v) {
    return is_array($v) ? array_map('unfck', $v) : stripslashes($v);
}

/* unfck_gpc
 * Apply the unfck function to elements of the global POST, GET, REQUEST and
 * COOKIE arrays, in place. */
function unfck_gpc() {
    foreach (array('POST', 'GET', 'REQUEST', 'COOKIE') as $gpc)
    $GLOBALS["_$gpc"] = array_map('unfck', $GLOBALS["_$gpc"]);
}

/* If magic_quotes_gpc is ON (in which case values in the global GET, POST and
 * COOKIE arrays will have been "escaped" by arbitrary insertion of
 * backslashes), try to undo this. */
if (get_magic_quotes_gpc()) unfck_gpc();

/* Make some vague effort to turn off the "magic quotes" nonsense. */
set_magic_quotes_runtime(0);

/*
 * Actually useful functions begin below here.
 */

/* debug HEADER TEXT [VARIABLE]
 * Print, to the page, a debugging variable, if a debug=... parameter is
 * present. The message is prefixed with the HEADER and consists of the passed
 * TEXT and an optional (perhaps array or class) VARIABLE which, if present, is
 * also dumped to the page. Display of items is dependent on the integer value
 * of the debug query variable and the passed HEADER, according to the table in
 * $levels below. */
function debug ($header, $text="", $complex_variable=null) {

	// We set ?debug=n in the URL.
	// n is a number from (currently) 1 to 4.
	// This sets what amount of debug information is shown.
	// For level '1' we show anything that is passed to this function
	// with a $header in $levels[1].
	// For level '2', anything with a $header in $levels[1] AND $levels[2].
	// Level '4' shows everything.
    // $complex_variable is dumped in full, so you can put arrays/hashes here
	
	//$debug_level = get_http_var("debug");  // disabled - information revealing security hole

    $debug_level = OPTION_PHP_DEBUG_LEVEL;
	
	if ($debug_level != '') {
	
		// Set which level shows which types of debug info.
		$levels = array (
			1 => array ('FRONTEND', 'WARNING', 'MAPIT', 'DADEM', 'QUEUE', 'TIMESTAMP'),
			2 => array ('MAPITRESULT', 'DADEMRESULT', 'RATTY'), 
			3 => array ('XMLRPC', 'RABX', 'RATTYRESULT'),
			4 => array ('RABXWIRE', 'SERIALIZE'),
		);
	
		// Store which headers we are allowed to show.
		$allowed_headers = array();
		
		if ($debug_level > count($levels)) {
			$max_level_to_show = count($levels);
		} else {
			$max_level_to_show = $debug_level;
		}
		
		for ($n = 1; $n <= $max_level_to_show; $n++) {
			$allowed_headers = array_merge ($allowed_headers, $levels[$n] );
		}
		
		// If we can show this header, then, er, show it.
		if ( in_array($header, $allowed_headers) || $debug_level >= 4) {
            	
			print "<p><span style=\"color:#039;\"><strong>$header</strong></span> $text";
            if (isset($complex_variable)) {
                print "</p><p>";
                vardump($complex_variable);
            }
            print "</p>\n";	
		}
	}
}


/* vardump VARIABLE
 * Dump VARIABLE to the page, properly escaped and wrapped in <pre> tags. */
function vardump($blah) {
    /* Miserable. We need to encode entities in the output, which means messing
     * about with output buffering. */
    ob_start();
    var_dump($blah);
    $d = ob_get_contents();
    ob_end_clean();
    print "<pre>" . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . "</pre>";
}



/* validate_email STRING
 * Return TRUE if the passed STRING may be a valid email address. */
function validate_email ($string) {
	if (!ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.
		'@'.
		'[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
		'[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $string)) {
		return false;
	} else {
		return true;
	}
}

/* validate_postcode POSTCODE
 * Return true is POSTCODE is in the proper format for a UK postcode. Does not
 * require spaces in the appropriate place. */
function validate_postcode ($postcode) {
    // See http://www.govtalk.gov.uk/gdsc/html/noframes/PostCode-2-1-Release.htm
    $in  = 'ABDEFGHJLNPQRSTUWXYZ';
    $fst = 'ABCDEFGHIJKLMNOPRSTUWYZ';
    $sec = 'ABCDEFGHJKLMNOPQRSTUVWXY';
    $thd = 'ABCDEFGHJKSTUW';
    $fth = 'ABEHMNPRVWXY';
    $num = '0123456789';
    $nom = '0123456789';
    $gap = '\s\.';	

    if (preg_match("/^[$fst][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$num][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$num][$thd][$gap]*[$nom][$in][$in]$/i", $postcode) ||
        preg_match("/^[$fst][$sec][$num][$fth][$gap]*[$nom][$in][$in]$/i", $postcode)) {
        return true;
    } else {
        return false;
    }
}

/* getmicrotime
 * Return time since the epoch, including fractional seconds. */
function getmicrotime() {
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];

    return $mtime;
}

/* strip_tags_tospaces TEXT
 * Return a copy of TEXT in which certain block-level HTML tags have been
 * replaced by single spaces, and other HTML tags have been removed. */
function strip_tags_tospaces($text) {
    $text = preg_replace("#\<(p|br|div|td|tr|th|table)[^>]*\>#i", " ", $text);
    return strip_tags(trim($text)); 
}

/* trim_characters TEXT START LENGTH
 * Return a copy of TEXT with (optionally) chararacters stripped from the
 * beginning and/or end. HTML tags are first stripped from TEXT and/or replaced
 * with spaces per strip_tags_tospaces; then, if START is positive, whole words
 * are removed from the beginning of TEXT until at least START characters have
 * been removed. Any removed characters are replaced with "...". If the length
 * of the resulting string exceeds LENGTH, then whole words are removed from
 * the end of TEXT until its total length is smaller than LENGTH, including an
 * ellipsis ("...") which is appended to the end. Long words (i.e. runs of
 * nonspace characters) have spaces inserted in them for neater
 * line-wrapping. */
function trim_characters ($text, $start, $length) {
    $text = strip_tags_tospaces($text);

    // Split long strings up so they don't go too long.
    // Mainly for URLs which are displayed, but aren't links when trimmed.
    $text = preg_replace("/(\S{60})/", "\$1 ", $text);

    // Otherwise the word boundary matching goes odd...
    $text = preg_replace("/[\n\r]/", " ", $text);

    // Trim start.
    if ($start > 0) {
        $text = substr($text, $start);

        // Word boundary.         
        if (preg_match ("/.+?\b(.*)/", $text, $matches)) {
            $text = $matches[1];
            // Strip spare space at the start.
            $text = preg_replace ("/^\s/", '', $text);
        }
        $text = '...' . $text;
    }

    // Trim end.
    if (strlen($text) > $length) {

        // Allow space for ellipsis.
        $text = substr($text, 0, $length - 3); 

        // Word boundary.         
        if (preg_match ("/(.*)\b.+/", $text, $matches)) {
            $text = $matches[1];
            // Strip spare space at the end.
            $text = preg_replace ("/\s$/", '', $text);
        }
        // We don't want to use the HTML entity for an ellipsis (&#8230;), because then 
        // it screws up when we subsequently use htmlentities() to print the returned
        // string!
        $text .= '...'; 
    }

    return $text;
}

/* convert_to_unix_newlines TEXT
 * Return a copy of TEXT in which all DOS/RFC822-style line-endings (CRLF,
 * "\r\n") have been converted to UNIX-style line-endings (LF, "\n"). */
function convert_to_unix_newlines($text) {
    $text = preg_replace("/(\r\n|\n|\r)/\n", "\n", $text);
    return $text;
}

/* get_http_var NAME [DEFAULT]
 * Return the value of the GET or POST parameter with the given NAME; or, if no
 * such parameter is present, DEFAULT; or, if DEFAULT is not specified, the
 * empty string (""). */
function get_http_var($name, $default='') {
    global $_GET, $_POST;
    if (array_key_exists($name, $_GET))
        return $_GET[$name];
    else if (array_key_exists($name, $_POST))
        return $_POST[$name];
    else 
        return $default;
}

/* make_plural NUMBER SINGULAR PLURAL
 * If NUMBER is 1, return SINGULAR; if NUMBER is not 1, return PLURAL
 * if it's there, otherwise WORD catenated with "s". */
function make_plural($number, $singular, $plural='') {
	if ($number == 1)
		return $singular;
	if ($plural)
		return $plural;
	return $singular . 's';
}

/* debug_timestamp
 * Output a timestamp since the page was started. */
$timestamp_last = $timestamp_start = getmicrotime();
function debug_timestamp() {
    global $timestamp_last, $timestamp_start;
    $t = getmicrotime();
    debug("TIMESTAMP", sprintf("%f seconds since start; %f seconds since last",
            $t - $timestamp_start, $t - $timestamp_last));
    $timestamp_last = $t;
}

/* invoked_url
 * Return the URL under which the script was invoked. The port is specified
 * only if it is not the default (i.e. 80 for HTTP and 443 for HTTPS). */
function invoked_url() {
    $url = 'http';
    $ssl = FALSE;
    if (array_key_exists('SSL', $_SERVER)) {
        $url .= "s";
        $ssl = TRUE;
    }
    $url .= "://" . $_SERVER['SERVER_NAME'];

    if ((!$ssl && $_SERVER['SERVER_PORT'] != 80)
        || ($ssl && $_SERVER['SERVER_PORT'] != 443))
        $url .= ":" . $_SERVER['SERVER_PORT'];

    $url .= preg_replace("/\?.*/", "", $_SERVER['REQUEST_URI']);

    return $url;
}

/* javascript_focus_set FORM ELEMENT
 * Return a bit of JavaScript which will set the user's input focus to the
 * input element of the given FORM (id) and ELEMENT (name). */
function javascript_focus_set($form, $elt) {
    return "document.$form.$elt.focus();";
}

/* check_is_valid_regexp STRING
 * Return true if STRING is (approximately) a valid Perl5 regular
 * expression. */
function check_is_valid_regexp($regex) {
    $result = preg_match("/" . str_replace("/", "\/", $regex) .  "/", "");
    return ($result !== FALSE);
}

/* new_url PAGE RETAIN [PARAM VALUE ...]
 * Return a new URL for PAGE with added parameters. If RETAIN is true, then all
 * of the parameters with which the page was originally invoked will be
 * retained in the original URL; additionally, any PARAM VALUE pairs will be
 * added. If a PARAM is specified it overrides any retained parameter value; if
 * a VALUE is null, any retained PARAM is removed. If a VALUE is an array,
 * multiple URL parameters will be added. If PAGE is null the URL under which
 * this page was invoked is used. */
function new_url($page, $retain) {
    if (!isset($page))
        $page = invoked_url();
    $url = "$page";

    $params = array();
    if ($retain)
        /* GET takes priority over POST. This isn't the usual behaviour but is
         * consistent with other bits of the code (see fyr/phplib/forms.php) */
        $params = array_merge($_POST, $_GET);

    if (func_num_args() > 2) {
        if ((func_num_args() % 2) != 0)
            die("call to new_url with odd number of arguments");
        for ($i = 2; $i < func_num_args(); $i += 2) {
            $k = func_get_arg($i);
            $v = func_get_arg($i + 1);
            if (array_key_exists($k, $params))
                unset($params[$k]);
            $params[func_get_arg($i)] = func_get_arg($i + 1);
        }
    }
    
    if (count($params) > 0) {
        $keyvalpairs = array();
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                for ($i = 0; $i < count($val); ++$i)
                    $keyvalpairs[] = urlencode($key) . '=' . urlencode($val[$i]);
            } elseif ($val)
                $keyvalpairs[] = urlencode($key) . '=' . urlencode($val);
        }
        $url .= '?' . join('&', $keyvalpairs);
    }

    return $url;
}

/* http_auth_user
 * Return the user name authenticated by HTTP, or *unknown* if none.
 * XXX should this not return null? */
function http_auth_user() {
    $editor = null;
    if (array_key_exists("REMOTE_USER", $_SERVER))
        $editor = $_SERVER["REMOTE_USER"];
    if (!$editor) 
        $editor = "*unknown*";
    return $editor;
}

/* add_tooltip TEXT TIP
 * Return an HTML <span>...</span> containing TEXT with TIP passed as the title
 * attribute of the span, so that it appears as a tooltip in common graphical
 * browsers. */
function add_tooltip($text, $tip) {
    return "<span title=\"" . htmlspecialchars($tip) . "\">$text</span>";
}

/*

// some tests of the above

define('OPTION_PHP_DEBUG_LEVEL', 4);
print "debug(...)\n";
debug('header', 'text');
debug('header', 'text', array(1, 2, 3));
print "done\n";

print "vardump(...)\n";
vardump(array(1, 2, 3));
print "done\n";

foreach (array('chris@ex-parrot.com', 'fish soup') as $e) {
    print "validate_email('$e') = " . validate_email($e) . "\n";
}

foreach (array('CB4 1EP', 'fish soup') as $pc) {
    print "validate_postcode('$pc') = " . validate_postcode($pc) . "\n";
}

print "getmicrotime() = " . getmicrotime() . "\n";

$text = 'I returned and saw under the sun, that the race is not to the swift, nor the battle to the strong, neither yet bread to the wise, nor yet riches to men of understanding, nor yet favour to men of skill; but time and chance happeneth to them all.';

print "\$text = '$text'\n";
print "trim_characters(\$text, 50, 999) = '" . trim_characters($text, 15, 999) . "'\n";
print "trim_characters(\$text, 0, 50) = '" . trim_characters($text, 0, 50) . "'\n";

$text = "fish\r\nsoup";
print "\$text = '$text'\n";
print "convert_to_unix_newlines(\$text) = '" . convert_to_unix_newlines($text) . "'\n";

// hard to test get_http_var in this environment

print "make_plural(1, 'fish', 'fishes') = '" . make_plural(1, 'fish', 'fishes') . "'\n";
print "make_plural(-1, 'fish', 'fishes') = '" . make_plural(-1, 'fish', 'fishes') . "'\n";

print "debug_timestamp():";
debug_timestamp();
sleep(1);
debug_timestamp();

print "invoked_url() = '" . invoked_url() . "'\n";

foreach (array('\w*\s*\w*', 'fish soup', '**') as $re) {
    print "check_is_valid_regexp('$re') = " . check_is_valid_regexp($re) . "\n";
}

print "new_url('http://www.microsoft.com', 0, 'fish', 'soup') = '" . new_url('http://www.microsoft.com', 0, 'fish', 'soup')  . "'\n";

print "http_auth_user() = '" . http_auth_user() . "'\n";

print "add_tooltip('fish', '\"soup\"') = '" . add_tooltip('fish', '"soup"') . "'\n";

*/

?>
