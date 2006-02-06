<?php
/*
 * stash.php:
 * Stash and retrieve request parameters, for deferred activities like login.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: stash.php,v 1.4 2006/02/06 16:39:13 chris Exp $
 * 
 */

require_once 'rabx.php';   /* for serialise/unserialise */
require_once 'utility.php';

require_once 'db.php';

/* stash_request [EXTRA]
 * Stash details of the request (i.e., method, URL, and any URL-encoded form
 * parameters in the content) in the database, and return a key for the stashed
 * data. EXTRA is an optional extra string stored with the stashed request. */
function stash_request($extra = null) {
    $url = invoked_url();
    if (!is_null($_SERVER['QUERY_STRING']))
        $url .= "?${_SERVER['QUERY_STRING']}";
    $v = null;
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        $v = $_POST;
    return stash_new_request($_SERVER['REQUEST_METHOD'], $url, $v, $extra);
}

/* stash_new_request METHOD URL PARAMS [EXTRA]
 * Return a stash key for a new METHOD request to URL with the given PARAMS.
 * If METHOD is "GET", and PARAMS is not null, then any query part of URL will
 * be reconstructed from the variables.  This function lets you create a stash
 * which represents that request, rather than the current one. */
function stash_new_request($method, $url, $params, $extra = null) {
    $key = bin2hex(random_bytes(8));
    if ($method == 'GET' || $method == 'HEAD') {
        if (!is_null($params)) {
            /* Strip query. */
            $url = preg_replace('/\?.*$/', '', $url);
            $a = array();
            foreach ($params as $k => $v) {
                /* XXX doesn't handle multiple parameters */
                array_push($a, urlencode($k) . '=' . urlencode($v));
            }
            if (count($a) > 0) {
                $url .= '?' . implode('&', $a);
            }
        }
        db_query('
                insert into requeststash (key, method, url, extra)
                values (?, ?, ?, ?)',
                array($key, 'GET', $url, $extra));
    } else if ($method == 'POST') {
        $ser = '';
        rabx_wire_wr($params, $ser);
        db_query('
                insert into requeststash (key, method, url, post_data, extra)
                values (?, ?, ?, ?, ?)',
                array($key, 'POST', $url, $ser, $extra));
    } else
        err("Cannot stash request for method '$method'");

    /* Also take this opportunity to remove old stashed state from the db. We
     * do this as two queries, one to produce the threshold time and another to
     * actually do the delete because PG isn't smart enough (in 7.3.x, anyway)
     * to use the index for the query if the RHS of the < is nonconstant. */
    $t = db_getOne("select pb_current_timestamp() - '365 days'::interval");
    db_query("delete from requeststash where whensaved < ?", $t);

    return $key;
}

/* stash_redirect KEY
 * Redirect the user (either by means of an HTTP redirect, for a GET request,
 * or by constructing a form, for a POST request) into the context of the
 * stashed request identified by KEY. */
function stash_redirect($key) {
    list($method, $url, $post_data) = db_getRow_list('select method, url, post_data from requeststash where key = ?', $key);
    if (is_null($method))
        err(_("If you got the email more than a year ago, then your request has probably expired.  Please try doing what you were doing from the beginning."));
    if (headers_sent())
        err("Headers have already been sent in stash_redirect('$key')");
    if ($method == 'GET') {
        /* should we ob_clean here? */
        header("Location: $url");
        exit();
    } else { // POST
        /* add token on end so can pull out POST params after redirect */
        if (strstr($url, "?")) {
            $url .= "&";
        } else {
            $url .= "?";
        }
        $url .= "stashpost=" . $key;
        header("Location: $url");
        #print "Going to $url";
        exit();
    }
}

/* stash_check_for_post_redirect
 * If we are in the middle of a POST redirect, stuffs the appropriate
 * data into $_POST. */
function stash_check_for_post_redirect() {
    /* Are we doing a POST redirect? */
    $key = get_http_var('stashpost');
    if (!$key) {
        return;
    }

    /* Extract the post data */
    list($method, $url, $post_data) = db_getRow_list('select method, url, post_data from requeststash where key = ?', $key);
    if (is_null($method))
        err(_("If you got the email more than a year ago, then your request has probably expired.  Please try doing what you were doing from the beginning."));

    /* Postgres/PEAR DB BYTEA madness -- see comment in auth.php. */
    $post_data = pg_unescape_bytea($post_data);
    $pos = 0;
    $stashed_POST = rabx_wire_rd(&$post_data, &$pos);
    if (rabx_is_error($stashed_POST))
        err("Bad serialised POST data in stash_redirect('$key')");

    /* Fix $_POST to make this look like one */
    $_POST = $stashed_POST;
    # print_r($stashed_POST);
}

/* stash_get_extra KEY
 * Return any extra data from that stashed with KEY. */
function stash_get_extra($key) {
    return db_getOne('select extra from requeststash where key = ?', $key);
}

/* stash_delete KEY
 * Delete any stashed request identified by KEY. */
function stash_delete($key) {
    db_query('delete from requeststash where key = ?', $key);
}

?>
