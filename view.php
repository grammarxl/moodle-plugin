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
$sql = "SELECT * FROM {grammarxl_grades} WHERE assignment=:assignment  AND  user=:user order by  id desc limit 0,1 ";
$grammarxl_grade =  $DB->get_record_sql($sql,array('assignment'=>$cm->instance,'user'=>$USER->id));
if(!$grammarxl_grade){
    print_error("Grade report not found");
}
  
require_course_login($course->id);
 if(!has_capability('mod/assign:viewgrades', $cm->context) && $USER->id != $grammarxl_grade->user && !is_siteadmin() ) {
    print_error("You don't have permission to see the report");  
   }
  
$context = context_course::instance($course->id);
$PAGE->set_context($context);

$PAGE->set_title("GrammarXL grades");
$PAGE->set_pagelayout('standard');
$PAGE->set_heading("GrammarXL grades");



echo $OUTPUT->header();

echo "<iframe src=$grammarxl_grade->report_url width='100%' height='900px'>";

echo $OUTPUT->footer();




