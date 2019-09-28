<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('lib.php');
global $DB,$USER;
$id = required_param('id', PARAM_INT);       
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');
$PAGE->set_url('/local/grammer/view.php',array('id'=>$id));
$grammarxl_grade =  $DB->get_record('grammarxl_grades',array('assignment'=>$cm->instance,'user'=>$USER->id), '*', IGNORE_MULTIPLE);
if(!$grammarxl_grade){
    print_error("Grade report not found");
}
  
require_course_login($course->id);
 if(!has_capability('mod/assign:viewgrades', $cm->context) && $USER->id != $grammarxl_grade->user && !is_siteadmin() ) {
    print_error("You don't have permission to see the report");  
   }
  
$context = context_course::instance($course->id);
$PAGE->set_context($context);

$PAGE->set_title("GrammerXL grades");
$PAGE->set_pagelayout('standard');
$PAGE->set_heading("GrammerXL grades");


echo $OUTPUT->header();

echo "<iframe src='https://tmtwebapp.azurewebsites.net/AssignmentReport/df959fe2-14ca-4657-9725-d16c39e98ebf?embedded=true' width='100%' height='900px'>";

echo $OUTPUT->footer();




