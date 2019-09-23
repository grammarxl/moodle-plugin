<?php

/**
 * @package   local_grammarxl
 * @copyright 2019 abhishekumarai1@gmai.com  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ( $hassiteconfig ){
 
	$settings = new admin_settingpage( 'local_grammarxl', 'GrammerXL Settings' );
 
	// Create 
	$ADMIN->add( 'localplugins', $settings );
 
    $hostname = new lang_string('hostname', 'local_grammarxl');
    $hostname_description = new lang_string('hostname_desc', 'local_grammarxl');
    $settings->add(new admin_setting_configtext('local_grammarxl/hostname',
                                                    $hostname,
                                                    $hostname_description,
                                                    'http://tmtdev.azurewebsites.net/api/Assignment',
                                                    PARAM_URL));
    $apikey_name = new lang_string('apikey_name', 'local_grammarxl');
    $apikey_description = new lang_string('apikey_desc', 'local_grammarxl');
    $settings->add(new admin_setting_configtext('local_grammarxl/apikey',
                                                    $apikey_name,
                                                    $apikey_description,
                                                    'f59ba710055f435b88abe0b15dfc55e2',
                                                    PARAM_TEXT));
	
        
    $grade_processing_name = new lang_string('grade_processing_name', 'local_grammarxl');
    $grade_processing_desc = new lang_string('grade_processing_desc', 'local_grammarxl');
    $choice = array('TASK'=>'TASK','NONE'=>'NONE'); 
    $settings->add(new admin_setting_configselect('local_grammarxl/grade_processing',
                                                    $grade_processing_name,
                                                    $grade_processing_desc,
                                                    'TASK',$choice));
    
                                    
      
}