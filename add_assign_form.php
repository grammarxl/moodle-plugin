<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class add_assign_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        $grammarxl_assignment = $this->get_assignment($this->_customdata['cmid']);

        // Choice of format, with help.
        $mform->addElement('header', 'enable_grading', get_string('enable_grading', 'local_grammarxl'));
        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'assignid', $this->_customdata['assignid']);
        $mform->setType('assignid', PARAM_INT);
        
        $mform->addElement('checkbox', 'enable', get_string('enable_grading', 'local_grammarxl'));
        $mform->setDefault('enable', $grammarxl_assignment['status']);
        $this->add_action_buttons(true, get_string('save', 'local_grammarxl'));
    }

    private function get_assignment($cmid) {
        global $DB;
        list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'assign');
        $context = context_module::instance($cmid);

        $grammar_assign = $DB->get_record('grammarxl_assign', array('assignment' =>  $cm->instance));
        $status = 0;
        if ($grammar_assign && $grammar_assign->status) {
            $status = $grammar_assign->status;
        }
        return  array('assignid' =>  $cm->instance, 'status' => $status);

        
    }

}
