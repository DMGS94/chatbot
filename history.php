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
 * Chat history page for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/chatbot/classes/gamification.php');

// Require login
require_login();

// Set up page
$PAGE->set_url('/blocks/chatbot/history.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('interaction_history', 'block_chatbot'));
$PAGE->set_heading(get_string('interaction_history', 'block_chatbot'));
$PAGE->set_pagelayout('standard');

// Check capability
if (!has_capability('block/chatbot:usechatbot', $PAGE->context)) {
    throw new required_capability_exception($PAGE->context, 'block/chatbot:usechatbot', 'nopermission', '');
}

// Get user ID (default to current user, allow teachers to view others)
$userid = optional_param('userid', $USER->id, PARAM_INT);
if ($userid != $USER->id && !has_capability('block/chatbot:viewinteractions', $PAGE->context)) {
    $userid = $USER->id;
}

// Get user record
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

// Get user's interactions
$interactions = $DB->get_records('block_chatbot_interactions', 
    array('userid' => $userid), 
    'timecreated DESC'
);

// Get user's badges
$badges = $DB->get_records('block_chatbot_badges', 
    array('userid' => $userid), 
    'timecreated ASC'
);

// Get progress to next badge
$progress = block_chatbot_gamification::get_progress_to_next_badge($userid);

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('interaction_history', 'block_chatbot'));

// User info
echo html_writer::start_div('user-info');
echo html_writer::tag('h3', fullname($user));
echo html_writer::tag('p', get_string('interactions', 'block_chatbot') . ': ' . count($interactions));
echo html_writer::end_div();

// Badges section
echo html_writer::start_div('badges-section');
echo html_writer::tag('h3', get_string('your_badges', 'block_chatbot'));

if (empty($badges)) {
    echo html_writer::tag('p', get_string('no_badges', 'block_chatbot'));
} else {
    echo html_writer::start_div('badge-list');
    foreach ($badges as $badge) {
        echo html_writer::start_div('badge-item');
        echo html_writer::tag('span', $badge->name, array('class' => 'badge badge-success'));
        echo html_writer::tag('p', $badge->description);
        echo html_writer::tag('p', get_string('awarded', 'block_chatbot') . ': ' . userdate($badge->timecreated));
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Progress to next badge
if ($progress['has_next_badge']) {
    echo html_writer::start_div('progress-section');
    echo html_writer::tag('h3', get_string('progress_to_next_badge', 'block_chatbot'));
    echo html_writer::tag('p', get_string('next_badge', 'block_chatbot') . ': ' . $progress['next_badge_name']);
    echo html_writer::tag('p', $progress['current_count'] . ' / ' . $progress['next_badge_count'] . ' ' . get_string('interactions', 'block_chatbot'));
    
    echo html_writer::start_div('progress-container');
    echo html_writer::start_tag('div', array('class' => 'progress'));
    echo html_writer::tag('div', $progress['progress_percent'] . '%', 
        array(
            'class' => 'progress-bar',
            'role' => 'progressbar',
            'style' => 'width: ' . $progress['progress_percent'] . '%',
            'aria-valuenow' => $progress['progress_percent'],
            'aria-valuemin' => '0',
            'aria-valuemax' => '100'
        )
    );
    echo html_writer::end_tag('div');
    echo html_writer::end_div();
    
    echo html_writer::end_div();
}

// Interactions history
echo html_writer::start_div('interactions-section');
echo html_writer::tag('h3', get_string('chat_history', 'block_chatbot'));

if (empty($interactions)) {
    echo html_writer::tag('p', get_string('no_history', 'block_chatbot'));
} else {
    echo html_writer::start_tag('table', array('class' => 'table table-striped'));
    
    // Table header
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('date', 'block_chatbot'));
    echo html_writer::tag('th', get_string('question', 'block_chatbot'));
    echo html_writer::tag('th', get_string('answer', 'block_chatbot'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    // Table body
    echo html_writer::start_tag('tbody');
    foreach ($interactions as $interaction) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', userdate($interaction->timecreated));
        echo html_writer::tag('td', $interaction->question);
        echo html_writer::tag('td', $interaction->answer);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    
    echo html_writer::end_tag('table');
}
echo html_writer::end_div();

// Back button
echo html_writer::start_div('back-button');
echo $OUTPUT->single_button(new moodle_url('/my/'), get_string('back_to_dashboard', 'block_chatbot'));
echo html_writer::end_div();

echo $OUTPUT->footer();
