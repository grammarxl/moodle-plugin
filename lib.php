<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot."/mod/assign/locallib.php");
function grade() {
    global $DB;
    $site = get_site();
    $start_time = time();
    $config = get_config('local_grammarxl');
    $last_grade_time =  isset($config->last_grade_success_time)?$config->last_grade_success_time:0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config->hostname);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $sql = "SELECT * FROM {assign_submission} WHERE status=:status AND timemodified> :timemodified ";
    $assign_submissions = $DB->get_records_sql($sql, array('status' => 'submitted', 'timemodified' => $last_grade_time));
    if(empty($assign_submissions)){
        echo "No new assignment submitted forgrading \n";
    }
    foreach ($assign_submissions as $assign_submission) {
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
            if (curl_error($ch)) {
                echo "Error in curl request";
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
                save_response($course, $assign_submission, $response,$grammarxl_grade);
            }else{
                echo "Error in grade processing $error";
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

function save_response($course, $assign_submission, $response,$grammarxl_response) {
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

