<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . "/mod/assign/locallib.php");

function grade() {
    global $DB;
    $site = get_site();
    $start_time = time();
    $grammarxl_assign = $DB->get_records('grammarxl_assign',array('status'=>1),null,'assignment');
    $config = get_config('local_grammarxl');
    $last_grade_time = 0;// isset($config->last_grade_success_time) ? $config->last_grade_success_time : 0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config->hostname);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $sql = "SELECT * FROM {assign_submission} WHERE status=:status AND timemodified> :timemodified ";
    $assign_submissions = $DB->get_records_sql($sql, array('status' => 'submitted', 'timemodified' => $last_grade_time));
    if (empty($assign_submissions)) {
        echo "No new assignment submitted forgrading \n";
    }
    foreach ($assign_submissions as $assign_submission) {
        if(!array_key_exists($assign_submission->assignment, $grammarxl_assign)){
            mtrace("Grading not enable in assignment $assign_submission->assignment");
            continue;  
        }
        echo "Grading assignment subission id:$assign_submission->id \n";
        $assign = $DB->get_record('assign', array('id' => $assign_submission->assignment), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($assign, 'assign');
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        $files = $fs->get_area_files($context->id, 'assignsubmission_file', 'submission_files', $assign_submission->id);
        foreach ($files as $file) {
            if ($file->get_filesize() <= 0) {
                continue;
            }
            $params = get_curl_params($assign_submission, $course, $config, $site);

            $file_path = get_file_real_path($file->get_contenthash());
            if ($file_path === null OR empty($file_path)) {
                echo "Empty file for assignment submissionid:$assign_submission->id";
                continue;
            }
            $params['uploadedFile'] = new CurlFile($file_path, $file->get_mimetype(), $file->get_filename());
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $response = curl_exec($ch);
            $error = curl_errno($ch);
            if ( $error ) {
                echo "Error in curl request:  $error ";
                continue;
            }
            $grammarxl_grade = json_decode($response);
            if (isset($grammarxl_grade) && isset($grammarxl_grade->scoreHistoryModel) && isset($grammarxl_grade->scoreHistoryModel->totalScore)) {
                $assign_grades = new assign($context, $cm, $course);
                $grade_data = new stdClass();
                $grade_data->attemptnumber = -1;
                $grade_data->grade = $grammarxl_grade->scoreHistoryModel->totalScore;
                $grade_data->assignfeedbackcomments_editor = array('text' => '', 'format' => '', 'itemid' => '');
                $assign_grades->save_grade($assign_submission->userid, $grade_data);
                save_response($course, $assign_submission, $response, $grammarxl_grade);
            } else {
                echo "Error in grade processing $response";
            }
            echo "Assignment submission id:$assign_submission->id compltetes here\n";
        }
    }
    curl_close($ch);
    set_config('last_grade_success_time', $start_time, 'local_grammarxl');
}

function get_curl_params($assign_submission, $course, $config, $site) {
    global $CFG;
    $params = array();
    $params['Ocp-Apim-Subscription-Key'] = $config->apikey;
    $params['Ocp-Apim-Trace'] = 'true';
    $params['UserId'] = $assign_submission->userid;
    $params['FirstName'] = 'aaa';
    $params['LastName'] = 'bb';
    $params['CourseId'] = $course->id;
    $params['CourseName'] = $course->fullname;
    $params['AssignmentId'] = $assign_submission->assignment;
    $params['AssignmentName'] = 'Assign';
    $params['AccountId'] = '';
    $params['AccountName'] = $CFG->wwwroot;
    $params['SiteName'] = $site->fullname;
    $params['SiteId'] = $site->shortname;
    $params['ClientName'] = $site->fullname;
    $params['Accept'] = 'application/json';
    return $params;
}

function get_file_real_path($filepathhash) {
    global $CFG;
    $file_real_path = null;
    if (!empty($filepathhash)) {
        $base_path = $CFG->dataroot . DIRECTORY_SEPARATOR . 'filedir';
        $file_path = substr($filepathhash, 0, 2) . DIRECTORY_SEPARATOR . substr($filepathhash, 2, 2);
        $file_real_path = $base_path . DIRECTORY_SEPARATOR . $file_path . DIRECTORY_SEPARATOR . $filepathhash;
        if (!file_exists($file_real_path)) {
            return null;
        }
    }
    return $file_real_path;
}

function save_response($course, $assign_submission, $response, $grammarxl_response) {
    global $DB;
    $grammarxl_grades = new stdClass();
    $grammarxl_grades->grade_response = $response;
    $grammarxl_grades->user = $assign_submission->userid;
    $grammarxl_grades->course = $course->id;
    $grammarxl_grades->assignment = $assign_submission->assignment;
    $grammarxl_grades->assignment_id = $grammarxl_response->assignmentId;
    $grammarxl_grades->assign_submission = $assign_submission->id;
    $grammarxl_grades->grade = $grammarxl_response->scoreHistoryModel->totalScore;
    $grammarxl_grades->pdf_report = $grammarxl_response->pdfReport;
    $grammarxl_grades->report_url = $grammarxl_response->reportUrl;
    $grammarxl_grades->timecreated = time();
    $grammarxl_grades->timemodified = time();
    $DB->insert_record('grammarxl_grades', $grammarxl_grades);
}

function local_grammarxl_grade_assign() {
    grade();
}

function local_grammarxl_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }
    $current_page_url = new moodle_url('/mod/assign/view.php', array());
    if (!$PAGE->url->compare($current_page_url, URL_MATCH_BASE)) {
        return;
    }
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }
    // print_object($settingsnav->find('modulesettings',navigation_node::TYPE_SETTING));
    if ($settingnode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING)) {
        $add_assign = get_string('add_assign', 'local_grammarxl');
        $url = new moodle_url('/local/grammarxl/add_assign.php', array('id' => $PAGE->cm->id));
        $foonode = navigation_node::create(
                        $add_assign,
                        $url,
                        navigation_node::NODETYPE_LEAF,
                        'local_grammarxl',
                        'local_grammarxl',
                        new pix_icon('i/navigationitem', $add_assign)
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $foonode->make_active();
        }
        $settingnode->add_node($foonode);
    }
}

function local_grammarxl_extend_navigation(global_navigation $navigation) {
    global $CFG,$DB,$USER,$PAGE;

    $url = new moodle_url('/mod/assign/view.php');
    if (!$PAGE->url->compare($url, URL_MATCH_BASE)) {
       return; 
    }
   
   if( !$DB->record_exists('grammarxl_grades',array('assignment'=> $PAGE->cm->instance,'user'=>$USER->id))){
     return;  
   }
   
    if ($home = $navigation->find($PAGE->cm->id, global_navigation::TYPE_ACTIVITY)) {
        $strfoo = get_string('grade', 'local_grammarxl');
        $url = new moodle_url('/local/grammarxl/view.php', array('id' => $PAGE->cm->id));
        $foonode = navigation_node::create(
                        $strfoo,
                        $url,
                        navigation_node::NODETYPE_LEAF,
                        'local_grammarxl',
                        'local_grammarxl',
                        new pix_icon('i/grades', $strfoo)
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $foonode->make_active();
        }
        $home->add_node($foonode);
    }
}

function enable_grammar_grading($data) {
    global $DB;

    $grammarxl_assignment = $DB->get_record('grammarxl_assign', array('assignment' => $data->assignid));

    if ($grammarxl_assignment) {
        if (isset($data->enable)) {
            $grammarxl_assignment->status = $data->enable;
        }else{
           $grammarxl_assignment->status =0;  
        }
        $DB->update_record('grammarxl_assign', $grammarxl_assignment);
    } else {
        $grammarxl_assignment = new stdClass();
        $grammarxl_assignment->assignment = $data->assignid;
        $grammarxl_assignment->timecreated = time();
        $grammarxl_assignment->timemodified = time();
         if (isset($data->enable)) {
            $grammarxl_assignment->status = $data->enable;
        }else{
           $grammarxl_assignment->status =0;  
        }
         $DB->insert_record('grammarxl_assign',$grammarxl_assignment);
    }
}


function grammarxl_grading_available($userid,$assignid){
       
}