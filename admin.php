<?
/*
 * Infrastructure for administration pages.
 * 
 * Copyright (c) 2004 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin.php,v 1.8 2004/11/15 15:07:25 fyr Exp $
 * 
 */

require_once "utility.php";

require_once "admin-ratty.php";
require_once "admin-phpinfo.php";
require_once "admin-serverinfo.php";

require_once "HTML/QuickForm.php";
require_once "HTML/QuickForm/Rule.php";
require_once "HTML/QuickForm/Renderer/Default.php";

function admin_page_display($site_name, $pages) {
    // generate navigation bar
    $navlinks = "";
    foreach ($pages as $page) {
        $navlinks .= "<a href=\"?page=". $page->id."\">" . $page->navname. "</a><br>";
    }

    // find page
    $id = get_http_var("page");
    if ($id == "") 
        $id = $pages[0]->id;
    foreach ($pages as $page) {
        if ($page->id == $id) {
            break;
        }
    } 
   
    // display
    $title = $page->navname . " &mdash; $site_name Admin Pages";
    admin_html_header($title);
    $self_link = "?page=$id";
    $page->display($self_link);
    admin_html_footer($navlinks);
}


// Header at start of page
function admin_html_header($title) {
?>
<html>
<head>
<title><?=$title?></title>
<style type="text/css"><!--
body {background-color: #ffffff;  color: #000000; }
body,  td,  th,  h1,  h2 {font-family: sans-serif; }
pre {margin: 0px;  font-family: monospace; }
a:link {color: #000099;  text-decoration: none;  background-color: #ffffff; }
a:hover {text-decoration: underline; }
table {border-collapse: collapse; }
.center {text-align: center; }
.center table { margin-left: auto;  margin-right: auto;  text-align: left; }
.center th { text-align: center !important;  }
td,  th { font-size: 75%; vertical-align: * baseline; }
h1 {font-size: 150%; }
h2 {font-size: 125%; }
.p {text-align: left; }
.e {background-color: #ccccff;  font-weight: bold;  color: #000000; }
.h {background-color: #9999cc;  font-weight: bold;  color: #000000; }
.v {background-color: #cccccc;  color: #000000; }
i {color: #666666;  background-color: #cccccc; }
img {float: right;  border: 0px; }
hr {width: 600px;  background-color: #cccccc;  border: 0px;  height: 1px;  color: #000000; }
//--></style>
</head>
<body>
<h1><?=$title?></h1>
<table border=1 cellpadding=10 width=100%><tr><td width=80%>
<?
}

// Footer at bottom
function admin_html_footer($navlinks) {
?>
<p><a href="http://www.mysociety.org/"><img src="http://www.mysociety.org/mysociety_sm.gif" border="0" alt="mySociety"></a></p>
</td>
<td valign=top>
<?=$navlinks?>
</td></tr></table>
</body>
</html>
<?
}

// Set colours and details of rendering here
function admin_render_form($form) {
    //$form->display();
    //return;
    $renderer =& $form->defaultRenderer();

    $form->setRequiredNote('<font color="#FF0000">*</font> shows the required fields.');
    $form->setJsWarnings('Those fields have errors :', 'Thanks for correcting them.');

    $renderer->setFormTemplate('<table width="100%" border="0" cellpadding="3" cellspacing="2" bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
    $renderer->setHeaderTemplate('<tr><td style="white-space:nowrap;background:#996;color:#ffc;" align="left" colspan="2"><b>{header}</b></td></tr>');

// Use for labels on specific groups:
//    $renderer->setGroupTemplate('<table><tr>{content}</tr></table>', ***);
//    $renderer->setGroupElementTemplate('<td>{element}<br /><span style="font-size:10px;"><!-- BEGIN required --><span style="color: #f00">*</span><!-- END required --><span style="color:#996;">{label}</span></span></td>', ***);

    $form->accept($renderer);
    echo $renderer->toHtml();
}

?>