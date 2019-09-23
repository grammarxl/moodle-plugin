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

$PAGE->set_url('/local/grammer/view.php',array('id'=>$id));
$grammarxl_grade =  $DB->get_record('grammarxl_grades',array('id'=>$id),'*');
if(!$grammarxl_grade){
    print_error("Grade report not found");
}
 $assign = $DB->get_record('assign', array('id' => $grammarxl_grade->assignment), '*', MUST_EXIST);
 list($course, $cm) = get_course_and_cm_from_instance($assign, 'assign');
  
require_course_login($grammarxl_grade->course);
 if(!has_capability('mod/assign:viewgrades', $cm->context) && $USER->id != $grammarxl_grade->user ) {
    print_error("You don't have permission to see the report");  
   }
  
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$PAGE->set_title("GrammerXL grades ");

$PAGE->set_heading("$assign->name grades");


echo $OUTPUT->header();

echo "<iframe src='https://tmtwebapp.azurewebsites.net/AssignmentReport/df959fe2-14ca-4657-9725-d16c39e98ebf?embedded=true' width='100%' height='500px'>";
echo $OUTPUT->footer();




