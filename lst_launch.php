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
 * Lists all certificate generation launch.
 *
 * @package    tool_history_attestoodle
 * @copyright  2021 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/tablelib.php');

define('DEFAULT_PAGE_SIZE', 10);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$order = optional_param('tsort', 0, PARAM_INT);
$launchid = optional_param('launchid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$action    = optional_param('action', '', PARAM_ALPHA);

if ($delete != 0 && $delete == $launchid) {
    deletecertif($launchid);
    // Test si num page ok.
    if ($page > 0) {
        $req = 'select count(id) from {tool_attestoodle_launch_log}';
        $matchcount = $DB->count_records_sql($req);
        if (($matchcount / $perpage) - 1 <= $page) {
            $redirecturl = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php',
                                        array('page' => $page - 1, 'perpage' => $perpage));
            redirect($redirecturl);
            die;
        }
    }
}

if ($delete == -1) {
    // Le retour avec delete = launchid (supprimer une génération).
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleteallcertif' , 'tool_history_attestoodle'));
    $optionsyes = array('delete' => $launchid, 'launchid' => $launchid, 'sesskey' => sesskey());
    $returnurl = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php', array('page' => $page, 'perpage' => $perpage));
    $deleteurl = new moodle_url($returnurl, $optionsyes);

    $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

    $info = $DB->get_record('tool_attestoodle_launch_log', array('id' => $launchid));
    $confirm = get_string('confirmdeleteonecertif' , 'tool_history_attestoodle', $info);

    echo $OUTPUT->confirm($confirm, $deletebutton, $returnurl);
    echo $OUTPUT->footer();
    die;
}

// Navbar.
$PAGE->navbar->ignore_active();
$titlepage = get_string('historyTitle' , 'tool_history_attestoodle');

$navhome = get_string('myhome');
$urlhome = new moodle_url('/my', array());
$PAGE->navbar->add($navhome, $urlhome, array());

$navprofil = get_string('profile');
$urlprofil = new moodle_url('/user/profile.php', array('id' => $USER->id));
$PAGE->navbar->add($navprofil, $urlprofil, array());

$navlevel = get_string('history' , 'tool_history_attestoodle');
$url = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php', array());
$PAGE->navbar->add($navlevel, $url, array());

$PAGE->set_url($url);
$PAGE->set_title($titlepage);
$PAGE->set_heading($titlepage);

echo $OUTPUT->header();

$req = 'select count(id) from {tool_attestoodle_launch_log}';
$matchcount = $DB->count_records_sql($req);

echo $OUTPUT->heading(get_string('nblaunch', 'tool_history_attestoodle', $matchcount));
// Per page.
echo $OUTPUT->box_start('generalbox mdl-align');

echo '<form id="chxnbp">';
echo '<label for="idnbperpage">'. get_string('perpage') .' : </label>';
echo '<select name="nbpp" id="idnbperpage" onchange="changeperpage(this)">';

if ($perpage == 10) {
    echo '<option value="10" selected>10</option>';
} else {
    echo '<option value="10">10</option>';
}
if ($perpage == 50) {
    echo '<option value="50" selected>50</option>';
} else {
    echo '<option value="50">50</option>';
}
if ($perpage == 100) {
    echo '<option value="100" selected>100</option>';
} else {
    echo '<option value="100">100</option>';
}
if ($perpage == 500) {
    echo '<option value="500" selected>500</option>';
} else {
    echo '<option value="500">500</option>';
}
if ($perpage == 1000) {
    echo '<option value="1000" selected>1000</option>';
} else {
    echo '<option value="1000">1000</option>';
}
echo '</select>';
echo '</form>';
echo $OUTPUT->box_end();

// Table.
$baseurl = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php', array('page' => $page, 'perpage' => $perpage));

$table = new flexible_table('admin_tool_launchhistory');

$tablecolumns = array('timegenerated', 'training', 'begindate', 'enddate', 'comment', 'operatorid', 'nb', 'actions');
$tableheaders = array(get_string('timegenerated' , 'tool_history_attestoodle'),
                      get_string('training' , 'tool_history_attestoodle'),
                      get_string('begindate' , 'tool_history_attestoodle'),
                      get_string('enddate' , 'tool_history_attestoodle'),
                      get_string('comment' , 'tool_history_attestoodle'),
                      get_string('operator' , 'tool_history_attestoodle'),
                      get_string('nbcertificat' , 'tool_history_attestoodle'),
                      get_string('actions' , 'tool_history_attestoodle'));


$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl->out());
$table->sortable(true, 'timegenerated', SORT_DESC);
$table->no_sorting('training');
$table->no_sorting('actions');
$table->no_sorting('nb');

$table->set_attribute('class', 'generaltable');

$table->column_style('actions', 'width', '7%');
$table->column_style('nb', 'width', '5%');
$table->column_style('training', 'width', '20%');

$table->setup();

$table->pagesize($perpage, $matchcount);
$order = " order by " . $table->get_sql_sort();

if ($action == 'purger') {
    if (handle_actions($launchid, $page, $perpage, $order)) {
        $table->currpage = 0;
    }
}

$req = 'select * from {tool_attestoodle_launch_log} ' . $order;
$rs = $DB->get_recordset_sql($req, array(), $table->get_page_start(), $table->get_page_size());

$rows = array();

$hasselect = false;
$haserror = false;
foreach ($rs as $result) {
    $reqnb = 'select count(id) from {tool_attestoodle_certif_log} where launchid =' . $result->id;
    $countnb = $DB->count_records_sql($reqnb);

    $rqtraining = 'select distinct(trainingid) as trainingid from {tool_attestoodle_certif_log} where launchid =?';
    $record = $DB->get_recordset_sql($rqtraining, array($result->id));
    $trainingid = -1;
    foreach ($record as $inf) {
        $trainingid = $inf->trainingid;
    }
    $trainingname = get_string('toBeDeleted' , 'tool_history_attestoodle');
    $delete = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php',
                          ['delete' => $result->id,
                          'launchid' => $result->id,
                          'page' => $page,
                          'perpage' => $perpage]);

    $viewlink = '';
    if ($trainingid != -1) {
        $delete = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php',
                          ['delete' => -1,
                          'launchid' => $result->id,
                          'page' => $page,
                          'perpage' => $perpage]);
        $training = $DB->get_record('tool_attestoodle_training', array('id' => $trainingid), "name");
        $trainingname = $training->name;
        $view = new moodle_url('/admin/tool/history_attestoodle/lst_certif.php',
                          ['launchid' => $result->id,
                          'ppage' => $page,
                          'pperpage' => $perpage,
                          'launchlabel' => $trainingname . ' ' . $result->begindate . ' ' . $result->enddate]);
        $viewlink = "<a href=" . $view . "><i class='fa fa-eye'></i></a>&nbsp;&nbsp;";
    } else {
        $haserror = true;
    }

    $dellink = "<a href=" . $delete . "><i class='fa fa-trash'></i></a>&nbsp;&nbsp;";

    $record = $DB->get_record('user', array('id' => $result->operatorid));
    $nameusr = 'inconnu';
    if (isset($record)) {
        $nameusr = $record->firstname . '.' . $record->lastname;
    }

    $next = new \DateTime();
    $next->setTimestamp($result->timegenerated);
    $timegen = $next->format(get_string('dateformat', 'tool_attestoodle'));

    $rows[] = array('timegenerated' => $timegen,
            'training' => $trainingname,
            'begindate' => $result->begindate,
            'enddate' => $result->enddate,
            'comment' => $result->comment,
            'operatorid' => $nameusr,
            'actions' => $dellink . $viewlink,
            'nb' => $countnb
            );
}

foreach ($rows as $row) {
    $table->add_data(
            array(
                $row['timegenerated'], $row['training'], $row['begindate'], $row['enddate'],
                $row['comment'], $row['operatorid'], $row['nb'], $row['actions']));
}

$table->print_html();

echo "<br>";

$parameters['page'] = $page;
$parameters['action'] = 'reinit';
$parameters['perpage'] = $perpage;

// Button valid.
$attributes = array('class' => 'btn btn-default attestoodle-button');

$parameters['action'] = 'purger';
$url = new \moodle_url('/admin/tool/history_attestoodle/lst_launch.php', $parameters);
$label = get_string('pageClean', 'tool_history_attestoodle');
$btnok = \html_writer::link($url, $label, $attributes);
// Button cancel.
$parameters['action'] = 'cancel';
$label = get_string('cancel');
$btncancel = \html_writer::link($urlprofil, $label, $attributes);

echo "<br/><br/>";
if ($haserror) {
    echo $btnok. "&nbsp;&nbsp;";
}
echo $btncancel;

$urlchg = new moodle_url('/admin/tool/history_attestoodle/lst_launch.php',
           array('launchid' => $launchid,
                'order' => $order,
                'page' => $page));

echo '<script language="javascript">function changeperpage(choix) {
    var value = "'. $urlchg->out() .'&perpage=" + choix.value;
    window.location.href = value;
}</script>';

echo $OUTPUT->footer();

function deletecertif($launchid) {
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

function handle_actions($launchid, $page, $perpage, $order) {
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
    if ($cpt == $perpage) { // Si on supprime toute la page.
        return true;
    } // Sinon return false.
    return false;
}