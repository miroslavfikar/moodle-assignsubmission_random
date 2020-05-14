<?PHP
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
 * This file contains the definition for the library class for the random 
 *  assignment plugin
 * 
 * This class provides all the functionality for the new assign module.  
 *
 * @package   assignsubmission_random
 * @copyright 2012 KIRP FCHPT STU in Bratislava {@link http://kirp.chtf.stuba.sk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Serves assignment submissions and other files.
 *
 * @global stdClass USER
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function assignsubmission_random_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $USER, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }  

    if ($filearea == 'inputfiles') {
        // Inputfiles
        if (!has_capability('mod/assign:grade', $context)) {
            require_capability('mod/assign:submit', $context);
        }

        $itemid = 0;
        $filename = array_pop($args);

    } elseif ($filearea == 'outputfiles') {
        // Outputfiles
        if (!has_capability('mod/assign:grade', $context)) {
            require_capability('mod/assign:submit', $context);
        }

        $itemid = 0;
        $filename = array_pop($args);

    } else {
         return false;
    }

    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'assignsubmission_random', $filearea, $itemid, $filepath, $filename);
    if ($file) {
        send_stored_file($file, 86400, 0, $forcedownload);
    }

    return false;
}