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
 * lists all the attestations of a student.
 *
 * @package    tool_history_attestoodle
 * @copyright  2021 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/searchstudent_form.php');

define('DEFAULT_PAGE_SIZE', 10);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$order = optional_param('tsort', 0, PARAM_INT);
$ppage = optional_param('ppage', 0, PARAM_INT);
$pperpage = optional_param('pperpage', DEFAULT_PAGE_SIZE, PARAM_INT);
// For delete one certificat.
$certifid = optional_param('cid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

// Navbar.
$titlepage = get_string('studentcertificats', 'tool_history_attestoodle');

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

$url2 = new moodle_url('/admin/tool/history_attestoodle/learner_certif.php',
           array('ppage' => $ppage, 'pperpage' => $pperpage));
$PAGE->navbar->add($titlepage, $url2, array());

$PAGE->set_url($url2);
$PAGE->set_title($titlepage);
$PAGE->set_heading($titlepage);

// If userid == 0 display form searchstudent_form.
if ($userid == 0) {
    $mform = new searchstudent_form();
    $data = $mform->get_data();
    if (isset($data->user)) {
        $userid = $data->user;
    } else {
        if ($mform->is_cancelled()) {
            redirect($urlhisto);
        }
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('studentcriteria', 'tool_history_attestoodle'));
        $mform->display();
        echo $OUTPUT->footer();
        die();
    }
}

$learner = $DB->get_record('user', array('id' => $userid));

if ($delete != 0 && $delete == $userid) { // Deleted confirmed.
    deletecertif($userid, $certifid);

    $req = 'select count(id) from {tool_attestoodle_certif_log} where learnerid = ?';
    $matchcount = $DB->count_records_sql($req, array($userid));
    if ($matchcount == 0) {
        redirect($urlhisto);
        die;
    }

    if ($page > 0) {
        if (($matchcount / $perpage) - 1 <= $page) {
            $redirecturl = new moodle_url('/admin/tool/history_attestoodle/learner_certif.php',
                                        array('page' => $page - 1, 'perpage' => $perpage,
                                            'launchid' => $launchid, 'ppage' => $ppage,
                                            'pperpage' => $pperpage, 'userid' => $userid));
            redirect($redirecturl);
            die;
        }
    }
}

$dformat = get_string('timecreatedformat', 'tool_history_attestoodle');
if ($delete == -1) {
    // Confirm or not this delete ?
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleteonecertif' , 'tool_history_attestoodle'));
    $optionsyes = array('delete' => $userid, 'userid' => $userid, 'cid' => $certifid, 'sesskey' => sesskey());
    $returnurl = new moodle_url('/admin/tool/history_attestoodle/learner_certif.php', array('page' => $page,
                                        'perpage' => $perpage, 'launchid' => $launchid,
                                        'ppage' => $ppage, 'pperpage' => $pperpage, 'userid' => $userid));
    $deleteurl = new moodle_url($returnurl, $optionsyes);

    $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

    $info = $DB->get_record('tool_attestoodle_certif_log', array('id' => $certifid));
    $infouser = $DB->get_record('user', array('id' => $userid));
    $infotraining = $DB->get_record('tool_attestoodle_training', array('id' => $info->trainingid));

    $text = new \stdClass();
    $text->user = $infouser->lastname .  ' ' . $infouser->firstname;
    $text->training = $infotraining->name;

    $rs = $DB->get_record('tool_attestoodle_launch_log', array('id' => $info->launchid));
    $dategener = new DateTime();
    $dategener->setTimestamp($rs->timegenerated);
    $text->training .= ' (' . $dategener->format($dformat) . ')';

    $confirm = get_string('confirmdeletecertif' , 'tool_history_attestoodle', $text);

    echo $OUTPUT->confirm($confirm, $deletebutton, $returnurl);
    echo $OUTPUT->footer();
    die;
}
echo $OUTPUT->header();

$req = 'select count(id) from {tool_attestoodle_certif_log} where learnerid = ?';
$matchcount = $DB->count_records_sql($req, array($learner->id));
if ($page > $matchcount / $perpage) {
    $page = $matchcount / $perpage;
}

$learner->nb = $matchcount;
$title = get_string('titleliststudent', 'tool_history_attestoodle', $learner);
echo $OUTPUT->heading($title);
// Per page.
echo choiceperpage($OUTPUT, $perpage);

// Table.
$baseurl = new moodle_url('/admin/tool/history_attestoodle/learner_certif.php',
           array('page' => $page, 'perpage' => $perpage,
                 'ppage' => $ppage, 'pperpage' => $pperpage,
                 'userid' => $learner->id));
$table = new flexible_table('admin_tool_certifhistory');

$tablecolumns = array('filename', 'name', 'timegenerated', 'timecredited', 'actions');
$tableheaders = array(get_string('file' , 'tool_history_attestoodle'),
                      'Formation',
                      'Généré le',
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

$req = "select c.id as id, c.filename as filename, trainingid as name, learnerid, timegenerated
          from {tool_attestoodle_certif_log} c
          join {tool_attestoodle_launch_log} l on l.id = c.launchid
          where learnerid = " . $learner->id . " " . $order;
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
    }

    $delete = new moodle_url('/admin/tool/history_attestoodle/learner_certif.php',
                          ['userid' => $result->learnerid,
                          'delete' => -1,
                          'page' => $page,   'perpage' => $perpage,
                          'ppage' => $ppage, 'pperpage' => $pperpage,
                          'cid' => $result->id,
                          'action' => 'delete']);
    $dellink = "<a href=" . $delete . "><i class='fa fa-trash'></i></a>&nbsp;&nbsp;";

    $training = $DB->get_record('tool_attestoodle_training', array('id' => $result->name));
    $name = get_string('toBeDeleted', 'tool_history_attestoodle');
    if (isset($training->id)) {
        $name = $training->name;
    }
    $dategener = new DateTime();
    $dategener->setTimestamp($result->timegenerated);
    $rows[] = array('filename' => $nomfichier,
            'name' => $name,
            'timegenerated' => $dategener->format($dformat),
            'timecredited' => $timecred,
            'actions' => $dellink
            );
}

foreach ($rows as $row) {
    $table->add_data(
            array(
                $row['filename'], $row['name'],
                $row['timegenerated'], $row['timecredited'],
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

$urlsearch = new \moodle_url('/admin/tool/history_attestoodle/learner_certif.php', $parameters);
$labelsearch = get_string('newsearch', 'tool_history_attestoodle');
$btnsearch = \html_writer::link($urlsearch, $labelsearch, $attributes);

echo "<br/><br/>" . $btncancel . "&nbsp;&nbsp;" . $btnsearch;

$urlchg = new moodle_url('/admin/tool/history_attestoodle/learner_certif.php',
           array('userid' => $learner->id,
                'ppage' => $ppage, 'pperpage' => $pperpage,
                'page' => 0));

echo '<script language="javascript">function changeperpage(choix) {
    var value = "'. htmlspecialchars_decode($urlchg->out()) .'&perpage=" + choix.value;
    window.location.href = value;
}</script>';

echo $OUTPUT->footer();
