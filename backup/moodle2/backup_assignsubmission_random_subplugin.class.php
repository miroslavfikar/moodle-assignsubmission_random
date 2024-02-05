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
 * This file contains the class for backup of this submission plugin
 * 
 * @package assignsubmission_random
 * @copyright 2012 Paul Nicholls
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup submission files
 *
 * This just adds its filearea to the annotations and records the number of files
 *
 * @package assignsubmission_random
 * @copyright 2012 Paul Nicholls
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_assignsubmission_random_subplugin extends backup_subplugin {

    /**
     * 
     * Returns the subplugin information to attach to submission element
     * @return backup_subplugin_element
     */
    protected function define_submission_subplugin_structure() {

        // create XML elements
        $subplugin = $this->get_subplugin_element(); // virtual optigroup element
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginelement = new backup_nested_element('submission_random', null, array('assignment','userid','file_assignment', 'date_save'));

        // connect XML elements into the tree
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginelement);

        // $assignid = $this->task->get_activityid();
        // set source to populate the data
        // $subpluginelement->set_source_table('assignsubmission_random', array(
        //     'assignment' => backup::VAR_ACTIVITYID));
        $sql = "SELECT ar.*
        FROM {assignsubmission_random} ar
        JOIN {assign_submission} asub ON (ar.userid = asub.userid AND ar.assignment = asub.assignment)
        WHERE ar.assignment = ? AND asub.id = ?";
        $subpluginelement->set_source_sql($sql, array(backup::VAR_ACTIVITYID,backup::VAR_PARENTID));
        
        // We only need to backup the files in the final pdf area, and the readonly page images - the others can be regenerated.
        $subpluginelement->annotate_files('assignsubmission_random', 'outputfiles', null);
        $subpluginelement->annotate_files('assignsubmission_random', 'inputfiles', null);
        
//        var_dump($subplugin);
//        var_dump($subpluginelement);        
        return $subplugin;
    }
    
}
