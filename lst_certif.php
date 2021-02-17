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
 * lists all the attestations of a generation (one launch).
 *
 * @package    tool_history_attestoodle
 * @copyright  2019 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once(dirname(__FILE__) . '/locallib.php');

define('DEFAULT_PAGE_SIZE', 10);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$order = optional_param('tsort', 0, PARAM_INT);

$launchid = optional_param('launchid', 0, PARAM_INT);
$ppage = optional_param('ppage', 0, PARAM_INT);
$pperpage = optional_param('pperpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$launchlabel = optional_param('launchlabel', '', PARAM_RAW);
$certifid = optional_param('cid', 0, PARAM_INT);

$userid = optional_param('userid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

if ($delete != 0 && $delete == $userid) { // Deleted confirmed.
    deletecertif($userid, $certifid);
    // Test si num page ok.
    if ($page > 0) {
        $req = 'select count(id) from {tool_attestoodle_certif_log} where launchid = ?';
        $matchcount = $DB->count_records_sql($req, array($launchid));
        if (($matchcount / $perpage) - 1 <= $page) {
            $redirecturl = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php',
                                        array('page' => $page - 1, 'perpage' => $perpage,
                                            'launchid' => $launchid, 'ppage' => $ppage,
                                            'pperpage' => $pperpage, 'launchlabel' => $launchlabel));
            redirect($redirecturl);
            die;
        }
    }
}

if ($delete == -1) {
    // Confirm or not this delete ?
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleteonecertif' , 'tool_history_attestoodle'));
    $optionsyes = array('delete' => $userid, 'userid' => $userid, 'cid' => $certifid, 'sesskey' => sesskey());
    $returnurl = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php', array('page' => $page, 'perpage' => $perpage,
                                            'launchid' => $launchid, 'ppage' => $ppage,
                                            'pperpage' => $pperpage, 'launchlabel' => $launchlabel));
    $deleteurl = new moodle_url($returnurl, $optionsyes);

    $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

    $info = $DB->get_record('tool_attestoodle_certif_log', array('id' => $certifid));
    $infouser = $DB->get_record('user', array('id' => $userid));
    $infotraining = $DB->get_record('tool_attestoodle_training', array('id' => $info->trainingid));

    $text = new \stdClass();
    $text->user = $infouser->lastname .  ' ' . $infouser->firstname;
    $text->training = $infotraining->name;

    $confirm = get_string('confirmdeletecertif' , 'tool_history_attestoodle', $text);

    echo $OUTPUT->confirm($confirm, $deletebutton, $returnurl);
    echo $OUTPUT->footer();
    die;
}

// Navbar.
$titlepage = get_string('certificatfor', 'tool_history_attestoodle', $launchlabel);
$PAGE->navbar->ignore_active();

$navhome = get_string('myhome');
$urlhome = new moodle_url('/my', array());
$PAGE->navbar->add($navhome, $urlhome, array());

$navprofil = get_string('profile');
$urlprofil = new moodle_url('/user/profile.php', array('id' => $USER->id));
$PAGE->navbar->add($navprofil, $urlprofil, array());

$navlevel = get_string('history' , 'tool_history_attestoodle');
$urlhisto = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php', array('page' => $ppage, 'perpage' => $pperpage));
$PAGE->navbar->add($navlevel, $urlhisto, array());

$navlevel2 = get_string('certificats', 'tool_history_attestoodle');
$url2 = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php',
           array('launchid' => $launchid, 'ppage' => $ppage, 'pperpage' => $pperpage, 'launchlabel' => $launchlabel));
$PAGE->navbar->add($navlevel2, $url2, array());

$PAGE->set_url($url2);
$PAGE->set_title($titlepage);
$PAGE->set_heading($titlepage);

echo $OUTPUT->header();

$req = 'select count(id) from {tool_attestoodle_certif_log} where launchid = ?';
$matchcount = $DB->count_records_sql($req, array($launchid));

echo $OUTPUT->heading(get_string('nbcertif', 'tool_history_attestoodle', $matchcount));
// Per page.
echo choiceperpage($OUTPUT, $perpage);

// Table.
$baseurl = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php',
           array('page' => $page, 'perpage' => $perpage, 'launchid' => $launchid,
                 'ppage' => $ppage, 'pperpage' => $pperpage, 'launchlabel' => $launchlabel));
$table = new flexible_table('admin_tool_certifhistory');

$tablecolumns = array('filename', 'name', 'timecredited', 'actions');
$tableheaders = array(get_string('file' , 'tool_history_attestoodle'),
                      get_string('learner' , 'tool_history_attestoodle'),
                      get_string('creditTime' , 'tool_history_attestoodle'),
                      get_string('actions' , 'tool_history_attestoodle'));

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl->out());
$table->sortable(true, 'name', SORT_DESC);
$table->no_sorting('actions');
$table->no_sorting('timecredited');

$table->set_attribute('width', '90%');
$table->set_attribute('class', 'generaltable');
$table->setup();

$table->pagesize($perpage, $matchcount);
$order = " order by " . $table->get_sql_sort();

$req = "select c.id as id, c.filename as filename, concat (u.lastname, ' ', u.firstname) as name, learnerid
          from {tool_attestoodle_certif_log} c
          JOIN {user} u ON u.id = c.learnerid
          where launchid = " . $launchid . " " . $order;
$rs = $DB->get_recordset_sql($req, array(), $table->get_page_start(), $table->get_page_size());

$rows = array();

$hasselect = false;
$fs = get_file_storage();

foreach ($rs as $result) {
    $reqtime = 'select sum(creditedtime) as creditedtime from {tool_attestoodle_value_log}
                where certificateid = ?';
    $row = $DB->get_record_sql($reqtime, array($result->id));
    $timecred = 0;
    if (isset($row->creditedtime)) {
        $timecred = $row->creditedtime;
    }

    $nomfichier = $result->filename;
    $fileexist = 0;
    if ($DB->record_exists('user', ['id' => $result->learnerid, 'deleted' => 0])) {
        $usercontext = \context_user::instance($result->learnerid);

        $file = $fs->get_file(
                $usercontext->id,
                'tool_attestoodle',
                'certificates',
                0,
                '/',
                $result->filename);
        if ($file) {
            $url = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $file->get_filename());

            $nomfichier = "<a href='" . $url . "' target='_blank'>" .
                    get_string('learner_details_download_certificate_link', 'tool_attestoodle') .
                    "</a>";
            $fileexist = 1;
        }
    } else {
        $nomfichier = "utilisateur supprimé !!";
    }
    $delete = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php',
                          ['userid' => $result->learnerid,
                          'delete' => -1,
                          'ppage' => $page,
                          'pperpage' => $perpage,
                          'launchlabel' => $launchlabel,
                          'cid' => $result->id,
                          'fileexist' => $fileexist,
                          'action' => 'delete',
                          'launchid' => $launchid]);
    $dellink = "<a href=" . $delete . "><i class='fa fa-trash'></i></a>&nbsp;&nbsp;";

    $rows[] = array('filename' => $nomfichier,
            'name' => $result->name,
            'timecredited' => $timecred,
            'actions' => $dellink
            );
}

foreach ($rows as $row) {
    $table->add_data(
            array(
                $row['filename'], $row['name'], $row['timecredited'],
                $row['actions']));
}
$table->print_html();

echo "<br>";

$parameters['page'] = $page;

// Button cancel.
$attributes = array('class' => 'btn btn-default attestoodle-button');

$parameters['action'] = 'cancel';
$label = get_string('cancel');
$btncancel = \html_writer::link($urlhisto, $label, $attributes);

echo "<br/><br/>" . $btncancel;


$urlchg = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php',
           array('launchid' => $launchid,
                'ppage' => $ppage, 'pperpage' => $pperpage, 'launchlabel' => $launchlabel,
                'page' => $page));

echo '<script language="javascript">function changeperpage(choix) {
    var value = "'. htmlspecialchars_decode($urlchg->out()) .'&perpage=" + choix.value;
    window.location.href = value;
}</script>';

echo $OUTPUT->footer();
