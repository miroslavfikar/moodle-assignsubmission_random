<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for random assignment plugin
 * 
 * This class provides all the functionality for the new assign module.  
 *
 * @package   assignsubmission_random
 * @copyright 2012 KIRP FCHPT STU in Bratislava {@link http://kirp.chtf.stuba.sk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include submissionplugin.php */
require_once($CFG->dirroot.'/mod/assign/submissionplugin.php');

defined('MOODLE_INTERNAL') || die();
/**
 * File areas for file submission assignment
 */
define('ASSIGNSUBMISSION_RANDOM_MAXFILES', 400);
define('ASSIGNSUBMISSION_RANDOM_FILEAREA_IN', 'inputfiles');
define('ASSIGNSUBMISSION_RANDOM_FILEAREA_OUT', 'outputfiles');

/**
 * library class for random assignment submission plugin extending submission plugin base class
 * 
 * @package   assignsubmission_random
 * @copyright 2012 KIRP FCHPT STU in Bratislava {@link http://kirp.chtf.stuba.sk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_random extends assign_submission_plugin {

   /**
    * get the name of the random assignment plugin
    * @return string
    */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_random');
    }  
    
    /**
     * Get the default setting for random assignment plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $fileoptions = array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 400);
       
        $mform->addElement('filemanager', 'inputfiles', get_string('inputfiles','assignsubmission_random'), null, $fileoptions ); 
        $mform->addHelpButton('inputfiles', 'inputfiles','assignsubmission_random');
         
        $mform->addElement('filemanager', 'outputfiles', get_string('outputfiles','assignsubmission_random'), null, $fileoptions ); 
        $mform->addHelpButton('outputfiles', 'outputfiles','assignsubmission_random');             
    }
    
    /**
     * Set up the draft file areas before displaying the settings form
     * @param array $default_values the values to be passed in to the form
     */
    public function data_preprocessing(&$default_values) { 

        $context = $this->assignment->get_context();
    
        $draftitemid1 = file_get_submitted_draft_itemid('inputfiles');
        if ($context) {
            file_prepare_draft_area($draftitemid1, $context->id, 'assignsubmission_random', 'inputfiles', 0, array('subdirs'=>0, 'maxfiles'=>400));
        }
        $default_values['inputfiles'] = $draftitemid1;
    
        $draftitemid2 = file_get_submitted_draft_itemid('outputfiles');
        if ($context) {
            file_prepare_draft_area($draftitemid2, $context->id, 'assignsubmission_random', 'outputfiles', 0, array('subdirs'=>0, 'maxfiles'=>400));
        }
        $default_values['outputfiles'] = $draftitemid2;
    
    }
    
    /**
     * Save the settings for random assignment plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $context = $this->assignment->get_context();   
        $draftitemid1 = $data->inputfiles;
        $draftitemid2 = $data->outputfiles;
        
        if ($draftitemid1) {
            file_save_draft_area_files($draftitemid1, $context->id, 'assignsubmission_random', 'inputfiles', 0, array('subdirs'=>false, 'maxfiles'=>400));
        }
        if ($draftitemid2) {
            file_save_draft_area_files($draftitemid2, $context->id, 'assignsubmission_random', 'outputfiles', 0, array('subdirs'=>false, 'maxfiles'=>400));
        }
       
        return true;
    } 
    
   /**
    * display AJAX based comment in the submission status table
    *
    * @param stdClass $submission
    * @param bool $showviewlink - If the comments are long this is set to true so they can be shown in a separate page
    * @return string
    */
   public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG, $DB;        
        
        $showviewlink = false;
        $context = $this->assignment->get_context();
        $link = '';
        $url_assignment = '';
        $url_solution = '';

        $record = $DB->get_record('assignsubmission_random', array('assignment' => $submission->assignment, 'userid' => $submission->userid) );
        if($record)  {
            if($record->file_assignment) {
                $url_assignment = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/assignsubmission_random/inputfiles/'.$record->file_assignment);
                $url_solution = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/assignsubmission_random/outputfiles/'.$record->file_assignment);
            }
            if(has_capability('mod/assign:grade', $context) && $record->file_assignment) {
                $link .= '<ul>';
                if($url_assignment) {
                    $link .= '<li>'.get_string('responsefilesassignment','assignsubmission_random').'<a href="'.$url_assignment.'" target="_blank">'.$record->file_assignment.'</a></li>';
                    // Ak existuje aj subor s riesenim - zobraz ho 
                    $fs = get_file_storage();                    
                    $fullpath = '/'.$context->id.'/assignsubmission_random/outputfiles/0/'.$record->file_assignment;
                    if($file = $fs->get_file_by_hash(sha1($fullpath))) {
                        $link .= '<li>'.get_string('responsefilessolution','assignsubmission_random').'<a href="'.$url_solution.'" target="_blank">'.$record->file_assignment.'</a></li>';  
                    }
                }                             
                $link .= '</ul>';                                  
            }
            else {
                if($url_assignment)
                    $link .= '<a href="'.$url_assignment.'">'.$record->file_assignment.'</a>';  
            }
        }
        return $link;
    } 
    
    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_random', array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }
   
    // **************************** cca start ******************************
    // Upgrade 
    // **************************** cca start ******************************   
    
    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'random') {
            return true;
        }
        return false;
    }
    
    /**
     * Upgrade the settings from the old assignment 
     * to the new plugin based one
     * 
     * @param context $oldcontext - the old assignment context
     * @param stdClass $oldassignment - the old assignment data record
     * @param string log record log events here
     * @return bool Was it a success? (false will trigger rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        global $DB;
        
        // Skopirovanie suborov so zadaniami a rieseniami do novej area files
        // Zadania
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id, 'mod_assignment', 'inputfiles', 0, $this->assignment->get_context()->id, 'assignsubmission_random', 'inputfiles', 0);
        // Riesenia
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id, 'mod_assignment', 'outputfiles', 0, $this->assignment->get_context()->id, 'assignsubmission_random', 'outputfiles', 0);     
        
        // ************************** upload start *****************************
        // Odosielanie je na zaklade assignmenttype = upload, je potrebne aj to upradeovat 
                              
        $file_maxfilesubmissions = new stdClass();
        $file_maxfilesubmissions->assignment = $this->assignment->get_instance()->id;
        $file_maxfilesubmissions->plugin = 'file';
        $file_maxfilesubmissions->subtype = 'assignsubmission';
        $file_maxfilesubmissions->name = 'maxfilesubmissions';
        $file_maxfilesubmissions->value = $oldassignment->var1;
        $DB->insert_record('assign_plugin_config', $file_maxfilesubmissions);               

        $file_maxsubmissionsizebytes = new stdClass();
        $file_maxsubmissionsizebytes->assignment = $this->assignment->get_instance()->id;
        $file_maxsubmissionsizebytes->plugin = 'file';
        $file_maxsubmissionsizebytes->subtype = 'assignsubmission';
        $file_maxsubmissionsizebytes->name = 'maxsubmissionsizebytes';
        $file_maxsubmissionsizebytes->value = $oldassignment->maxbytes;
        $DB->insert_record('assign_plugin_config', $file_maxsubmissionsizebytes);  
               
        // Nastavenie odovzdávania zadaní: Súbory odovzdanıch zadaní
        $DB->set_field('assign_plugin_config', 'value', 1, array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>'file', 'subtype'=>'assignsubmission', 'name'=>'enabled'));
        // Nastavenie odovzdávania zadaní: Online text
        $DB->set_field('assign_plugin_config', 'value', 1, array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>'onlinetext', 'subtype'=>'assignsubmission', 'name'=>'enabled'));
        // Nastavenie odovzdávania zadaní: Poznámky k hodnoteniam
        $DB->set_field('assign_plugin_config', 'value', 1, array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>'comments', 'subtype'=>'assignsubmission', 'name'=>'enabled'));
       
        // ************************** upload stop *****************************

        return true;
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @global moodle_database $DB
     * @param context $oldcontext The context of the old assignment
     * @param stdClass $oldassignment The data record for the old oldassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, & $log) {
        global $DB;   

        // Vytvorenie triedy, do ktorej naplnim udaje zo starej tabulky mdl_assignment_submissions - pre random
        $assignrandom = new stdClass();
        // Udaje pre mdl_assignsubmission_random    
        $assignrandom->assignment = $this->assignment->get_instance()->id;
        $assignrandom->userid = $oldsubmission->userid;
        $assignrandom->file_assignment = $oldsubmission->data1;
        $assignrandom->date_save = time();        
      
        // Ulozenie udajov o priradenom zadani studentovi do novej tabulky mdl_assignsubmission_random
        if (!$DB->insert_record('assignsubmission_random', $assignrandom) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }
      
        // Vytvorenie triedy, do ktorej naplnim udaje zo starej tabulky mdl_assignment_submissions - pre upload     
        $mdl_assignsubmission_file = new stdClass();
        // Udaje pre mdl_assignsubmission_file    
        $mdl_assignsubmission_file->assignment = $this->assignment->get_instance()->id;
        $mdl_assignsubmission_file->submission = $submission->id;
        //$mdl_assignsubmission_file->numfiles = $oldsubmission->numfiles;
        // Zistenie poctu odovzdanych suborov (v predchadzajucej verzii nebol k dispozicii v tabulke)
        $fs = get_file_storage(); 
        $files = $fs->get_area_files($oldcontext->id, 'mod_assignment', 'submission');
        //$mdl_assignsubmission_file->numfiles = count($files);

        // Ulozenie udajov o priradenom zadani studentovi do novej tabulky mdl_assignsubmission_file
        if (!$DB->insert_record('assignsubmission_file', $mdl_assignsubmission_file) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }
       
        // Skopirovanie suborov s odovzdanymi rieseniami do novej area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id, 'mod_assignment', 'submission', $oldsubmission->id, $this->assignment->get_context()->id, 'assignsubmission_file', 'submission_files', $submission->id);        

        // Nastavenia komentára: Komentáre
        $DB->set_field('assign_plugin_config', 'value', 1, array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>'comments', 'subtype'=>'assignfeedback', 'name'=>'enabled'));

        return true;
    }
    
    // **************************** cca stop *******************************
    // Upgrade 
    // **************************** cca stop *******************************     
    
    // **************************** cca start ******************************
    // Nepouzite
    // **************************** cca start ******************************                                    

    /**
     * Load the submission object for a particular user, optionally creating it if required
     * I don't want to have to do this, but it's private on the assign() class, so can't be used!
     *
     * @param int $userid The id of the user whose submission we want or 0 in which case USER->id is used
     * @param bool $create optional Defaults to false. If set to true a new submission object will be created in the database
     * @return stdClass The submission
     */
    public function get_user_submission_record($userid, $create) {
        global $DB, $USER;
        
        if (!$userid) {
            $userid = $USER->id;
        }
        // if the userid is not null then use userid
        $submission = $DB->get_record('assign_submission', array('assignment'=>$this->assignment->get_instance()->id, 'userid'=>$userid));

        if ($submission) {
            return $submission;
        }
        if ($create) {
            $submission = new stdClass();
            $submission->assignment   = $this->assignment->get_instance()->id;
            $submission->userid       = $userid;
            $submission->timecreated = time();
            $submission->timemodified = $submission->timecreated;

            if ($this->assignment->get_instance()->submissiondrafts) {
                $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
            } else {
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            }
            $sid = $DB->insert_record('assign_submission', $submission);
            $submission->id = $sid;
            return $submission;
        }
        return false;
    }
      
    /**
     * Always return false because only active if exist submission
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {      
        return false;
    }

    /**
     * The submission view xxx plugin has no submission component per se so should not be counted
     * when determining whether to show the edit submission link.
     * @return boolean
     */
    public function allow_submissions() {
        return false;
    }

    /**
     * Check if the submission plugin is open for submissions by a user
     * @param int $userid user ID
     * @return bool|string 'true' if OK to proceed with submission, otherwise a
     *                        a message to display to the user
     */
    public function is_open($userid=0) {
        return true;
    }

    /**
     */    
    private function is_graded($userid) {
        global $DB;

        $instance = $this->assignment->get_instance();
        $grade = $DB->get_record('assign_grades', array('assignment'=>$instance->id, 'userid'=>$userid));
        if ($grade) {
            return ($grade->grade !== NULL && $grade->grade >= 0);
        }
        return false;

    }

    /**
     */
    public function can_view_random($userid=0) {
        global $DB, $USER; 

        if(!$userid) {
            $userid = $USER->id;
        }
        $instance = $this->assignment->get_instance();
        $is_open = false;
        if ($this->assignment->is_any_submission_plugin_enabled()) {
            if($submission = $DB->get_record('assign_submission', array('assignment'=>$instance->id, 'userid'=>$userid))) {
                $mode = $this->get_config('viewrandomlimit');
                switch ($mode) {
                    case 'grade' :  $is_open = !$this->is_graded($userid);
                                    break;
                    case 'final':  $is_open = $this->assignment->submissions_open();
                                    break;
                    case 'time'  :  $is_open = ($time <= $instance->duedate);
                                    break;
                    case 'submit'  :  $is_open = false;
                                    break;
                }
            }
        }
        return !$is_open;
    }
    // **************************** cca stop *******************************
    // Nepouzite 
    // **************************** cca stop *******************************     
}
