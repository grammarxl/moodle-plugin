<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = array(
 
    array(
        'eventname'   => 'mod_assign\event\submission_created',
        'callback'    => 'local_grammarxl_observer::user_assignment_submitted',
    )   
 
);