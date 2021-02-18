<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Useful lobac functions for history Attestoodle.
 *
 * @package    tool_history_attestoodle
 * @copyright  2021 Pole de Ressource Numerique de l'Université du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Purges the page by deleting training launches that no longer exist.
 *
 * @param int $page the page to be purged.
 * @param int $perpage the number of items per page.
 * @param string $order the sorting order applied.
 *
 * @return bool true if the complete page is deleted, false in other cases.
 */
function delete_launch($page, $perpage, $order) {
    global $DB;
    $cpt = 0;
    $req = 'select * from {tool_attestoodle_launch_log} ' . $order;
    $rs = $DB->get_recordset_sql($req, array(), $page * $perpage, $perpage);

    $idin = "";
    foreach ($rs as $result) {
        $rqtraining = 'select distinct(trainingid) as trainingid from {tool_attestoodle_certif_log} where launchid =?';
        $record = $DB->get_recordset_sql($rqtraining, array($result->id));
        $trainingid = -1;
        foreach ($record as $inf) {
            $trainingid = $inf->trainingid;
        }
        if ($trainingid == -1) {
            $idin .= $result->id . ",";
            $cpt++;
        }
    }

    if ($cpt > 0) {
        $req = 'delete from {tool_attestoodle_launch_log} where id in ( '. substr($idin, 0, -1) .')';
        $DB->execute($req);
    }
    if ($cpt == $perpage) {
        return true;
    }
    return false;
}

/**
 * Builds the HTML structure for changing the number of items to be displayed per page.
 * All possible choices 10, 50, 100, 500, 1000.
 *
 * @param object $OUTPUT the output associated with the page.
 * @param int $perpage the number of items per page.
 *
 * @return string the chain carrying the structure of choice.
 */
function choiceperpage($OUTPUT, $perpage) {
    $ret = $OUTPUT->box_start('generalbox mdl-align');
    $ret .= '<form id="chxnbp">';
    $ret .= '<label for="idnbperpage">'. get_string('perpage') .' : </label>';
    $ret .= '<select name="nbpp" id="idnbperpage" onchange="changeperpage(this)">';
    if ($perpage == 10) {
        $ret .= '<option value="10" selected>10</option>';
    } else {
        $ret .= '<option value="10">10</option>';
    }
    if ($perpage == 50) {
        $ret .= '<option value="50" selected>50</option>';
    } else {
        $ret .= '<option value="50">50</option>';
    }
    if ($perpage == 100) {
        $ret .= '<option value="100" selected>100</option>';
    } else {
        $ret .= '<option value="100">100</option>';
    }
    if ($perpage == 500) {
        $ret .= '<option value="500" selected>500</option>';
    } else {
        $ret .= '<option value="500">500</option>';
    }
    if ($perpage == 1000) {
        $ret .= '<option value="1000" selected>1000</option>';
    } else {
        $ret .= '<option value="1000">1000</option>';
    }
    $ret .= '</select>';
    $ret .= '</form>';
    $ret .= $OUTPUT->box_end();
    return $ret;
}

/**
 * Eliminates the need to launch a generation of certificates and the certificates issued from this generation.
 *
 * @param int $launchid the technical identifier of the launch to be deleted.
 */
function deleteallcertif($launchid) {
    global $DB;
    // Delete generate files.
    $sql = "SELECT distinct filename, learnerid
              FROM {tool_attestoodle_certif_log}
             where launchid = :launchid";
    $result = $DB->get_records_sql($sql, ['launchid' => $launchid]);
    $fs = get_file_storage();
    foreach ($result as $record) {
        $fileinfo = array(
                'contextid' => $record->learnerid,
                'component' => 'tool_attestoodle',
                'filearea' => 'certificates',
                'filepath' => '/',
                'itemid' => 0,
                'filename' => $record->filename
            );
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($file) {
                $file->delete();
        }
    }

    // Delete log.
    $DB->delete_records('tool_attestoodle_launch_log', array('id' => $launchid));
    $sql = "DELETE from {tool_attestoodle_value_log}
             WHERE certificateid IN (SELECT id
                                FROM {tool_attestoodle_certif_log}
                               WHERE launchid = :launchid)";
    $DB->execute($sql, ['launchid' => $launchid]);
    $DB->delete_records('tool_attestoodle_certif_log', array('launchid' => $launchid));
}

/**
 * Deletes a certificate and all its attributes.
 *
 * @param int $userid the technical identifier of the learner concerned by the certificate.
 * @param int $certifid the technical identifier of the certificate to be deleted.
 */
function deletecertif($userid, $certifid) {
    global $DB;
    // Delete generate file.
    $sql = "SELECT distinct filename, learnerid
              FROM {tool_attestoodle_certif_log}
             where id = :certifid";
    $result = $DB->get_record_sql($sql, ['certifid' => $certifid]);
    $fs = get_file_storage();
    $fileinfo = array(
                'contextid' => $userid,
                'component' => 'tool_attestoodle',
                'filearea' => 'certificates',
                'filepath' => '/',
                'itemid' => 0,
                'filename' => $result->filename
            );
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    if ($file) {
        $file->delete();
    }

    // Delete log.
    $sql = "DELETE from {tool_attestoodle_value_log}
             WHERE certificateid = :certifid";
    $DB->execute($sql, ['certifid' => $certifid]);
    $DB->delete_records('tool_attestoodle_certif_log', array('id' => $certifid));
}
