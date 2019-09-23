<?php
/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_grammarxl\task;

/**
 * An example of a scheduled task.
 */
class grade_assign extends \core\task\scheduled_task {
   
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('grade_assign', 'local_grammarxl');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/grammarxl/lib.php');
        $configs = get_config('local_grammarxl');
        if ($configs->grade_processing == 'TASK') {
            local_grammarxl_grade_assign();
        } else {
            mtrace('Task based assign grade processing is not enabled');
        }
    }

}
