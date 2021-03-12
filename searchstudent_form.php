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
 * This is the form for select student.
 *
 * @copyright  2021 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    tool_history_attestoodle
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Form to select student by id username or email.
 *
 * @copyright  2021 Pole de Ressource Numerique de l'Universite du Mans
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class searchstudent_form extends moodleform {

    /**
     * Method automagically called when the form is instanciated. It defines
     * all the elements (inputs, titles, buttons, ...) in the form.
     */
    protected function definition() {
        $mform    = $this->_form;

        $mform->addElement('text', 'user', get_string('usernameorid', 'webservice'));
        $mform->setType('user', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'mail', get_string('email'));
        $mform->setType('mail', PARAM_RAW_TRIMMED);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHANUMEXT);
        $this->add_action_buttons(true);
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * note: if data->user not numeric convert data->user  to id user find.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        global $DB;
        $data = parent::get_data();

        if (!empty($data) && !is_numeric($data->user)) {
            $user = $DB->get_record('user', array('username' => $data->user), 'id');
            $data->user = $user->id;
        }
        if (!empty($data) && empty($data->user) && !empty($data->mail)) {
            $user = $DB->get_record('user', array('email' => $data->mail), 'id');
            $data->user = $user->id;
        }
        return $data;
    }

    /**
     * Checks whether a student matches the login entered or userid or the email address entered.
     * The designated student must also have at least one certificate.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        if (empty($data['user'])) {
            if (empty($data['mail'])) {
                $errors['user'] = get_string('errornocriteria' , 'tool_history_attestoodle');
                return $errors;
            }
            $users = $DB->get_records('user', array('email' => $data['mail']), '', 'id');
            if (count($users) != 1) {
                $errors['mail'] = get_string('errornotmail' , 'tool_history_attestoodle');
                return $errors;
            }
        }

        if (is_numeric($data['user'])) {
            $searchtype = 'id';
        } else {
            $searchtype = 'username';
            // Check the username is valid.
            if (clean_param($data['user'], PARAM_USERNAME) != $data['user']) {
                $errors['user'] = get_string('invalidusername');
            }
        }

        if (!isset($errors['user']) && !empty($data['user'])) {
            $users = $DB->get_records('user', array($searchtype => $data['user']), '', 'id');

            // Check that the user exists in the database.
            if (count($users) == 0) {
                $errors['user'] = get_string('usernameoridnousererror', 'webservice');
            } else if (count($users) > 1) { // Can only be a username search as id are unique.
                $errors['user'] = get_string('usernameoridoccurenceerror', 'webservice');
            }
        }

        if (!isset($errors['user']) && !isset($errors['mail'])) {
            foreach ($users as $learner) {
                $id = $learner->id;
            }

            $certifs = $DB->get_records('tool_attestoodle_certif_log', array('learnerid' => $id), '', 'id');
            if (count($certifs) < 1) {
                $errors['user'] = get_string('errornocertificat' , 'tool_history_attestoodle', $id);
            }
        }
        return $errors;
    }
}
