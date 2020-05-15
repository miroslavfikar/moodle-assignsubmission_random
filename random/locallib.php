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
     * Insert random header if needed (replace the renderer.php hack)
     * 
     * @return string output generated 
     */
    public function view_header() {
    
        // $ĥeader->assign->id = $this->assignment->get_instance()->id;
        // has_capability('mod/assign:grade', $this->assignment->get_context())
        // $context->id => $this->assignment->get_context()->id();
        
        global $DB, $CFG, $USER;        
        
        $o = '';

        // Ak je povolene zadanie typu Random 
        // If the type Random is enabled
        $plugin_random = $DB->get_record('assign_plugin_config', array('assignment' => $this->assignment->get_instance()->id, 'plugin' => 'random'), 'value');
        
        // Kontrola priradenia/Priradenie suboru studentovi (ucitelovi sa subor nepriraduje)
        // Check/assign file to student (not to teacher)
        if($plugin_random->value && !has_capability('mod/assign:grade', $this->assignment->get_context())) {
 
            // nacitanie informacii o ulozenych suboroch so zadaniami
            // read information about stored files with assignments
            $fs = get_file_storage();
            $context = $this->assignment->get_context();
            $files = $fs->get_area_files($context->id, 'assignsubmission_random', 'inputfiles', false, '', false);
    
            // ak existuju subory so zadaniami
            // if there are assignment files
            if($files) {
                // zistenie uz priradenych suborov z vybraneho adresara
                // find files already assigned
                $file_array_db = array();
                $records = $DB->get_records('assignsubmission_random', array('assignment' => $this->assignment->get_instance()->id));     
                if ($records) {
                    foreach ($records as $records_value) {
                        if($records_value->file_assignment)  // este sa na to pozri a uprav to
                            $file_array_db[] = $records_value->file_assignment;
                    }
                }
    
                // user's group. Initialized here because we will maybee need it a different point.
                $user_groups  = [];

                // zistenie, ci je priradeny subor daneho zadania uzivatelovi 
                // find whether the current user has a file
                if ($this->assignment->get_instance()->teamsubmission){
                    // submission by groups

                    $grouping = $this->assignment->get_instance()->teamsubmissiongroupingid;
                    $user_groups = groups_get_all_groups($this->assignment->get_course()->id, $USER->id, $grouping);

                    // user must belong to a group, and only one group
                    if (count($user_groups) == 0 or count($user_groups) > 1){
                        $notification = new \core\output\notification(get_string('multipleteams_desc', 'assignsubmission_random'), 'error');
                        $notification->set_show_closebutton(false);
                        return $this->assignment->get_renderer()->render($notification);
                    }

                    $record = $DB->get_record('assignsubmission_random', array('assignment' => $this->assignment->get_instance()->id, 'groupid' => current($user_groups)->id));


                } else {
                    // submission by user
                    $record = $DB->get_record('assignsubmission_random', array('assignment' => $this->assignment->get_instance()->id, 'userid' => $USER->id));
                }
    
                if(!isset($record->file_assignment)) {
                    // ak nie je priradeny subor, vyhlada volny a priradi ho (vlozenim do DB)
                    // if not file is assigned, find a free one, assign it (by SQL insert)
                   	$file_array = array();
                    $fs = get_file_storage();
                    $files = $fs->get_area_files($context->id, 'assignsubmission_random', 'inputfiles', false, '', false);
                    if (!empty($files)) {
                        foreach ($files as $f) {
                            $file_array[] = $f->get_filename();
                        }    
                    }  

                  	// ziskanie rozdielov poli suborov v DB a v adresari - vysledok je pole suborov, ktore este neboli priradene
                  	// difference between file array and DB - files not yet assigned
                  	$file_array_diff = array_diff ($file_array, $file_array_db);
                  	
                  	$file_select = '';
                  	if(count($file_array_diff)) {
                        // ak nie je pole prazdne, potom vyber subor, ktory je prvym prvkom pola
                        // if array not empty, select its first element
                        $keys = array_keys($file_array_diff); // keys of array elements
                        $file_select = $file_array_diff[$keys[0]];
                  	} else {
                        // ak je pole prazdne, t.j. vsetky subory uz boli priradene, potom sa priraduju subory nahodne
                        // nahodny vyber prvku
                        // if is array empty (all files assigned), make random assigment
                        // random selection
                        $item_random = intval(rand(0, count($file_array)-1));
                        $file_select = $file_array[$item_random];
                    }

                  	if($file_select) {
                  	    // zistenie, ci je uz nejaky zaznam v tabulke mdl_assignsubmission_random
                          // find if there are records in table mdl_assignsubmission_random
                        if ($this->assignment->get_instance()->teamsubmission){ 
                            $record = $DB->get_record('assignsubmission_random', array('assignment' => $this->assignment->get_instance()->id, 'groupid' => current($user_groups)->id));
                        } else {
                            $record = $DB->get_record('assignsubmission_random', array('assignment' => $this->assignment->get_instance()->id, 'userid' => $USER->id));
                        }
                        
                        if(!$record) {
                            // ak neexistuje zaznam v tabulke mdl_assignsubmission_random => urob iba insert do mdl_assignsubmission_random
                            // if no record in mdl_assignsubmission_random => do only insert to mdl_assignsubmission_random
                  	        $data_insert = new stdClass();
                            $data_insert->userid          = $USER->id;
                            $data_insert->assignment      = $this->assignment->get_instance()->id;
                            $data_insert->file_assignment = $file_select;
                            $data_insert->date_save       = time();

                            if ($this->assignment->get_instance()->teamsubmission){ 
                                $data_insert->userid          = 0;
                                $data_insert->groupid = current($user_groups)->id;

                            } else {
                                $data_insert->userid          = $USER->id;
                                $data_inser->groupid          = 0;
                            }

                            $DB->insert_record('assignsubmission_random', $data_insert);
                        } 
                  	}
                }
            }   
        } 
        // $_GET['action'] == 'grading' - in older versions 
        // $_GET['action'] == 'grade' - in newer versions 
        // if one of these is chosen => set hide_list to 1 <= not to show list in grader report
        $hide_list = 0;       
        if(isset($_GET['action'])) {
            if($_GET['action'] == 'grading' || $_GET['action'] == 'grade')
                $hide_list = 1;
        }
        
        if($plugin_random->value && !$hide_list) { 
            $o .= "<div class='box generalbox boxaligncenter boxwidthnormal' id='random_assignment_list'>\n";

            if(has_capability('mod/assign:grade', $this->assignment->get_context())) {

                $fs = get_file_storage();

                $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_random', 'inputfiles', false, '', false);
                $url_list_in = array();
                if (!empty($files)) {
                    foreach ($files as $f) {
                        $url = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$this->assignment->get_context()->id.'/assignsubmission_random/inputfiles/'.$f->get_filename());
                        $url_list_in[] = '<li><a href="'.$url.'">'.$f->get_filename().'</a></li>';
                    }    
                } 
                
                $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_random', 'outputfiles', false, '', false);
                $url_list_out = array();
                if (!empty($files)) {
                    foreach ($files as $f) {
                        $url = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$this->assignment->get_context()->id.'/assignsubmission_random/outputfiles/'.$f->get_filename());
                        $url_list_out[] = '<li><a href="'.$url.'">'.$f->get_filename().'</a></li>';
                    }    
                } 

                if($url_list_in || $url_list_out) {
                    //$o .= "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js'></script>\n";
                    $o .= "<script type='text/javascript'>\n";
                    $o .= "function cca_show_hide() {\n";
                    $o .= "  if(document.getElementById('cca_show_hide').checked)\n";
                    $o .= "    document.getElementById('id_cca_show_hide').style.display = 'block';\n";
                    $o .= "  else\n";
                    $o .= "    document.getElementById('id_cca_show_hide').style.display = 'none';\n";
                    $o .= "}\n";
                    $o .= "</script>\n";
                    $o .= "<div><input type='checkbox' id='cca_show_hide' value='1' onclick='cca_show_hide()' /> ".get_string('show_hide', 'assignsubmission_random')."</div>\n";
                    $o .= "<div id='id_cca_show_hide' style='display: none'>\n";
                    $o .= "<table class='boxaligncenter'>\n";
                    $o .= "<tr>\n";
                }

                if($url_list_in) {
                    asort($url_list_in);
                    $o .= "<td>\n";
                    $o .= "<p>".get_string('assignments', 'assignsubmission_random').":</p>\n";
                    $o .= "<ol>";
                    $o .= implode("",$url_list_in);
                    $o .= "</ol>\n";
                    $o .= "</td>\n";
                }
                
                if($url_list_out) {
                    asort($url_list_out);
                    $o .= "<td>\n";
                    $o .= "<p>".get_string('solutions', 'assignsubmission_random').":</p>\n";
                    $o .= "<ol>";
                    $o .= implode("",$url_list_out);
                    $o .= "</ol>\n";
                    $o .= "</td>\n";
                }

                if($url_list_in || $url_list_out) {
                    $o .= "</tr>";
                    $o .= "</table>";
                    $o .= "</div>";
                }
            }
            else
            {    
                $record = $DB->get_record('assignsubmission_random', array('assignment' => $this->assignment->get_context()->id, 'userid' => $USER->id));
                if($record)
                    $filename = $record->file_assignment;
                else
                    $filename = '';
                if($filename) {
                    $url = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$this->assignment->get_context()->id.'/assignsubmission_random/inputfiles/'.$filename); 
                    $o .= get_string('getassignment','assignsubmission_random','<a href="'.$url.'" target="_blank">'.get_string('getassignmenttext','assignsubmission_random').'</a>');
                }
            }
            $o .= "\n</div>";
        }

        return $o;
    }

   /**
    * display AJAX based comment in the submission status table
    *
    * @param stdClass $submission
    * @param bool $showviewlink - If the comments are long this is set to true so they can be shown in a separate page
    * @return string
    */
   public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG, $DB, $USER;
        
        $showviewlink = false;
        $context = $this->assignment->get_context();
        $link = '';
        $url_assignment = '';
        $url_solution = '';

        if ($this->assignment->get_instance()->teamsubmission){ 
            $record = $DB->get_record('assignsubmission_random', array('assignment' => $this->assignment->get_instance()->id, 'groupid' => $submission->groupid));
        } else {
            $record = $DB->get_record('assignsubmission_random', array('assignment' => $submission->assignment, 'userid' => $submission->userid) );
        }
        
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
               
        // Nastavenie odovzd�vania zadan�: S�bory odovzdan�ch zadan�
        $DB->set_field('assign_plugin_config', 'value', 1, array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>'file', 'subtype'=>'assignsubmission', 'name'=>'enabled'));
        // Nastavenie odovzd�vania zadan�: Online text
        $DB->set_field('assign_plugin_config', 'value', 1, array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>'onlinetext', 'subtype'=>'assignsubmission', 'name'=>'enabled'));
        // Nastavenie odovzd�vania zadan�: Pozn�mky k hodnoteniam
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

        // Nastavenia koment�ra: Koment�re
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
