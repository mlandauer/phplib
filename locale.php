<?php
/*
 * locale.php:
 * Functions to change locale; for display of text (via gettext), dates
 * times and so on in different human languages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: locale.php,v 1.6 2005/11/14 16:59:23 matthew Exp $
 * 
 */

/* locale_negotiate_language OVERRIDE CONFIG
 * Sets global variable $lang to negotiated language.
 * CONFIG is string from config file containing list of available languages. 
 *        e.g. 'en-gb,English,en_GB|pt-br,Portugu&ecirc;s (Brasil),pt_BR'
 * OVERRIDE is override language, such as from cookie or domain name.  Set to
 * null to force negotiation of language from browser, using HTTP headers. */
function locale_negotiate_language($available_language_config, $override_langage) {
    global $langs, $langmap, $lang;

    $opt_langs = explode('|', $available_language_config);
    $langs = array(); $langmap = array();
    foreach ($opt_langs as $opt_lang) {
        list($code, $verbose, $locale) = explode(',', $opt_lang);
        $langs[$code] = $verbose;
        $langmap[$code] = $locale;
    }
    if ($override_langage && array_key_exists($override_langage, $langs)) {
        $lang = $override_langage;
    } else {
        $lang = negotiateLanguage($langs); # local copy, see further down this file
        if ($lang=='en-US' || !$lang || !array_key_exists($lang, $langmap)) {
            $lang = 'en-gb'; # Default override
        }
    }
}

/* Note: To get a language working from PHP on Unix, you also need
to install the system locale for that language. In Debian this is done
using "dpkg-reconfigure locales". You may need to restart Apache also. */

/* locale_change LANG
 * Change human language to display text, dates, numbers etc. in. LANG is the
 * keys from the available language string previously passed to
 * locale_negotiate_language. Leave unset to use the default negotiated language.
 */
$locale_current = null;
function locale_change($l = "") {
    global $langmap, $lang, $locale_current;
    if ($l == "")
        $l = $lang;
    if ($l == $locale_current)
        return;
    putenv('LANGUAGE='); # clear this if set
    putenv('LANG='.$langmap[$l].'.UTF-8');
    $os_locale = $langmap[$l].'.UTF-8';
    $ret = setlocale(LC_ALL, $os_locale);
    if ($ret != $os_locale)
        err("setlocale failed for $os_locale");
    $locale_current = $l;
    // Clear gettext's cache - you have to do this when
    // you change environment variables.
    textdomain(textdomain(NULL));
}

/* locale_push LANG, locale_pop
 * Change locale using a stack system, so you can easily restore to whatever
 * locale was previously set. */
$locale_stack = array();
function locale_push($l) {
    global $locale_stack, $locale_current;
    array_push($locale_stack, $locale_current);
    locale_change($l);
}
function locale_pop() {
    global $locale_stack;
    $l = array_pop($locale_stack);
    locale_change($l);
}

/* locale_gettext_domain DOMAIN
 * Set gettext domain. e.g. 'PledgeBank' */
function locale_gettext_domain($domain) {
    bindtextdomain($domain, '../../locale');
    textdomain($domain);
    bind_textdomain_codeset($domain, 'UTF-8');
}

# PHP's own negotiateLanguage in HTTP.php is broken in old versions, so we use a copy
function negotiateLanguage(&$supported) {
    $supported = array_change_key_case($supported, CASE_LOWER);
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $accepted = preg_split('/\s*,\s*/', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
        for ($i = 0; $i < count($accepted); $i++) {
            if (preg_match('/^([a-z_-]+);\s*q=([\d\.]+)/', $accepted[$i], $arr)) {
                $q = (double)$arr[2];
                $l = $arr[1];
            } else {
                $q = 1;
                $l = $accepted[$i];
            }
            if ($q > 0.0) {
                if (!empty($supported[$l])) {
                    if ($q == 1) {
                        return $l;
                    }
                    $candidates[$l] = $q;
                } else {
                    $l = preg_quote($l);
                    foreach (array_keys($supported) as $value) {
                        if (preg_match("/^$l-/",$value)) {
                            if ($q == 1) {
                                return $value;
                            }
                            $candidates[$value] = $q;
                            break;
                        }
                    }
                }
            }
        }
        if (isset($candidates)) {
            arsort($candidates);
            reset($candidates);
            return key($candidates);
        }
    }
}

