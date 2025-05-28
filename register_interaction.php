<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option)  any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Register chatbot interaction
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php') ;
require_once($CFG->dirroot.'/blocks/chatbot/classes/gamification.php');

// Require login
require_login();

// Set up response
header('Content-Type: application/json');

// Get parameters
$question = required_param('question', PARAM_TEXT);
$answer = required_param('answer', PARAM_TEXT);
$courseid = optional_param('courseid', $COURSE->id, PARAM_INT);

// Try to create tables if they don't exist
try {
    // Check if table exists
    $dbman = $DB->get_manager();
    $table = new xmldb_table('block_chatbot_interactions');
    
    if (!$dbman->table_exists($table)) {
        // Create interactions table
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('answer', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        $dbman->create_table($table);
    }
    
    // Check if badges table exists
    $table = new xmldb_table('block_chatbot_badges');
    
    if (!$dbman->table_exists($table)) {
        // Create badges table
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('badgetype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        $dbman->create_table($table);
    }
} catch (Exception $e) {
    // Ignore errors, we'll try to use the tables anyway
}

// Record interaction
try {
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->courseid = $courseid;
    $record->question = $question;
    $record->answer = $answer;
    $record->timecreated = time();
    
    $DB->insert_record('block_chatbot_interactions', $record);
    
    // Get interaction count
    $count = $DB->count_records('block_chatbot_interactions', array('userid' => $USER->id));
    
    // Check for badges
    $badge_earned = false;
    $badge_name = '';
    
    // Badge levels
    $badge_levels = array(
        10 => 'Novice Learner',
        50 => 'Knowledge Explorer',
        100 => 'Learning Master'
    );
    
    // Check if user earned a new badge
    foreach ($badge_levels as $level => $name) {
        if ($count == $level) {
            // Check if user already has this badge
            $existing = $DB->get_record('block_chatbot_badges', array(
                'userid' => $USER->id,
                'badgetype' => 'level_' . $level
            ));
            
            if (!$existing) {
                // Award new badge
                $badge = new stdClass();
                $badge->userid = $USER->id;
                $badge->badgetype = 'level_' . $level;
                $badge->name = $name;
                $badge->description = "Awarded after $level interactions with the chatbot";
                $badge->timecreated = time();
                
                $DB->insert_record('block_chatbot_badges', $badge);
                
                $badge_earned = true;
                $badge_name = $name;
                break;
            }
        }
    }
    
    echo json_encode(array(
        'status' => 'success',
        'interactions' => $count,
        'badge_earned' => $badge_earned,
        'badge_name' => $badge_name
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
