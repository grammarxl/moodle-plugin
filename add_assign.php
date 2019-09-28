<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');
require_once('add_assign_form.php');
$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');
$PAGE->set_context(context_course::instance($course->id));
require_login($course->id);
$PAGE->set_title("Enable GrammerXL grading");
$PAGE->set_heading("Enable GrammerXL grading");

$PAGE->set_url(new moodle_url('/local/grammarxl/add_assign.php',array('id'=>$course->id)));
$PAGE->set_pagelayout('standard');

$form = new add_assign_form(null,array('cmid'=>$id,'assignid'=>$cm->instance));

if($data = $form->get_data() ){
   enable_grammar_grading($data);
    redirect($CFG->wwwroot.'/mod/assign/view.php?id='.$id, get_string('success','local_grammarxl'), null, \core\output\notification::NOTIFY_SUCCESS);
   
}else{
 echo $OUTPUT->header();
  $form->display();  
}
echo $OUTPUT->footer();