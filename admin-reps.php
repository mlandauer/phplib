<?php
/*
 * Representatives admin page.
 * 
 * Copyright (c) 2004 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-reps.php,v 1.1 2004/12/20 18:03:48 francis Exp $
 * 
 */

require_once "dadem.php";
require_once "mapit.php";

class ADMIN_PAGE_REPS {
    function ADMIN_PAGE_REPS () {
        $this->id = "reps";
        $this->name = "Reps";
        $this->navname = "Representative Data";
    }

    function display($self_link) {
        $form = new HTML_QuickForm('adminMaPitForm', 'post', $self_link);

        // Input data
        $pc = get_http_var('pc');
        $rep_id = get_http_var('rep_id');
        if (get_http_var('cancel') != "") 
            $rep_id = null;
        if (get_http_var('done') != "") {
            $newdata['name'] = get_http_var('name');
            $newdata['party'] = get_http_var('party');
            $newdata['method'] = get_http_var('method');
            $newdata['email'] = get_http_var('email');
            $newdata['fax'] = get_http_var('fax');
            $editor = $_SERVER["REMOTE_USER"];
            if (!$editor) $editor = "*unknown*";
            $result = dadem_admin_edit_representative($rep_id, $newdata, $editor, get_http_var('note'));
            dadem_check_error($result);
            print "<p><i>Successfully updated representative $rep_id</i></i>";
            $rep_id = null;
        }

        // Postcode box
        $form->addElement('header', '', 'Search');
        $buttons[] =& HTML_QuickForm::createElement('text', 'pc', null, array('size' => 10, 'maxlength' => 255));
        $buttons[] =& HTML_QuickForm::createElement('submit', 'go', 'go postcode');
        $form->addElement('hidden', 'page', $this->id);
        $form->addGroup($buttons, 'stuff', null, '&nbsp', false);

        // Conditional parts: 
        if ($rep_id) {
            // Edit representative
            $repinfo = dadem_get_representative_info($rep_id);
            dadem_check_error($repinfo);

            $form->setDefaults(
                array('name' => $repinfo['name'],
                'party' => $repinfo['party'],
                'method' => $repinfo['method'],
                'email' => $repinfo['email'],
                'fax' => $repinfo['fax']));

            $form->addElement('header', '', 'Edit Representative');
            $form->addElement('static', 'note1', null, "Edit only
            the values which you need to, it makes it easier to feed data
            back to GovEval.");
            $form->addElement('text', 'name', "Full name:", array('size' => 60));
            $form->addElement('text', 'party', "Political party:", array('size' => 60));
            $form->addElement('static', 'note2', null, "Make sure you
            update contact method when you change email or fax
            numbers.");
            $form->addElement('select', 'method', "Contact method to use:", 
                    array('either' => 'Fax or Email', 'fax' => 'Fax only', 
                        'email' => 'Email only', 'shame' => 'Shame!  Doesn\'t want contacting',
                        'unknown' => 'We don\'t know contact details'
                        ));
            $form->addElement('text', 'email', "Email address:", array('size' => 60));
            $form->addElement('text', 'fax', "Fax number:", array('size' => 60));
            $form->addElement('textarea', 'note', "Note to add to log:
            (where new data was from etc.)", array('rows' => 3, 'cols' => 60));
            $form->addElement('hidden', 'pc', $pc);
            $form->addElement('hidden', 'rep_id', $rep_id);

            $finalgroup[] = &HTML_QuickForm::createElement('submit', 'done', 'Done');
            $finalgroup[] = &HTML_QuickForm::createElement('submit', 'cancel', 'Cancel');
            $form->addGroup($finalgroup, "finalgroup", "",' ', false);
        } else if ($pc) {
            // Postcode search
            $voting_areas = mapit_get_voting_areas($pc);
            mapit_check_error($voting_areas);
            $areas = array_values($voting_areas);
            $areas_info = mapit_get_voting_areas_info($areas);
            mapit_check_error($areas_info);
            foreach ($areas_info as $area=>$area_info) {
                $va_id = $area;

                // One voting area
                $reps = dadem_get_representatives($va_id);
                dadem_check_error($reps);
                $reps = array_values($reps);
                $html .= "<p><b>" . $area_info['name'] . " (" .  $area_info['type_name'] . ") </b></p>"; 
                $info = dadem_get_representatives_info($reps);
                dadem_check_error($info);

                foreach ($info as $rep => $repinfo) {
                    if ($repinfo['edited']) {
                        $html .= "<i>edited</i> ";
                    }
                    $html .= "<a href=\"$self_link&pc=" . urlencode($pc). "&rep_id=" . $rep .  "\">" . $repinfo['name'] . " (". $repinfo['party'] . ")</a> \n";
                    $html .= "prefer " . $repinfo['method'];
                    if ($repinfo['email']) 
                        $html .= ", " .  $repinfo['email'];
                    if ($repinfo['fax']) 
                        $html .= ", " .  $repinfo['fax'];
                    $html .= "<br>";
                }
            }
            $form->addElement('static', 'bytype', null, $html);
        }
        else {
            // General Statistics

            // MaPit
            $form->addElement('header', '', 'Postcode/Area Statistics (MaPit)');
            $mapitstats = mapit_admin_get_stats();
            $form->addElement('static', 'mapitstats', "Areas: ", $mapitstats['area_count']);
            $form->addElement('static', 'mapitstats', "Postcodes: ",  $mapitstats['postcode_count']);
            
            // DaDem
            $form->addElement('header', '', 'Representative Statistics (DaDem)');
            $dademstats = dadem_admin_get_stats();
            dadem_check_error($dademstats);
            $form->addElement('static', 'dademstats', "Representatives: ",  $dademstats['representative_count']);
            $form->addElement('static', 'dademstats', "Voting Areas: ", $dademstats['area_count']);

            $form->addElement('static', 'dademstats', "Fax or Email Coverage: ", 
                    round(100*$dademstats['either_present']/$dademstats['representative_count'],2) .  "% (" . $dademstats['either_present'] . ")");
            $form->addElement('static', 'dademstats', "Email Coverage: ", 
                    round(100*$dademstats['email_present']/$dademstats['representative_count'],2) .  "% (" . $dademstats['email_present'] . ")");
            $form->addElement('static', 'dademstats', "Fax Coverage: ", 
                    round(100*$dademstats['fax_present']/$dademstats['representative_count'],2) .  "% (" . $dademstats['fax_present'] . ")");

            // MaPit counts by Area Type
            $form->addElement('header', '', 'MaPit Counts by Area Type');
            $html = "<table>";
            foreach ($mapitstats as $k=>$v) {
                preg_match("/area_count_([A-Z]+)/", $k, $matches);
                if ($matches) {
                    $html .= "<tr><td>" . $matches[1] . "</td><td>$v</td></tr>\n";
                }
            }
            $html .= "</table>";
            $form->addElement('static', 'bytype', null, $html);
        }

        admin_render_form($form);
        $form = new HTML_QuickForm('adminRepsForm', 'get', $self_link);
   }
}


?>
