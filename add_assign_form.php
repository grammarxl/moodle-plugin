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
        $all_assignment = $this->get_course_assignments($this->_customdata['courseid']);

        // Choice of format, with help.
        $mform->addElement('header', 'enable_grading', get_string('enable_grading', 'local_grammarxl'));

        $select = $mform->addElement('select', 'colors', get_string('assignment','local_grammarxl'),  $all_assignment, $all_assignment);
        $select->setMultiple(true);
        $select->setSelected(array(7));
        // Submit buttons.
        $this->add_action_buttons(true, get_string('save', 'local_grammarxl'));
    }

    private function get_course_assignments($courseid) {
        global $DB;
        $all_assignment = array();
        $assignments = $DB->get_records('assign', array('course' => $courseid), null, 'id,name');
        foreach ($assignments as $assignment) {
            $all_assignment[$assignment->id] = $assignment->name;
        }
        return $all_assignment;
    }

}
