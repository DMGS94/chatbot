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
 * Language strings for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Gamified Chatbot';
$string['chatbot:addinstance'] = 'Add a new Gamified Chatbot block';
$string['chatbot:myaddinstance'] = 'Add a new Gamified Chatbot block to Dashboard';
$string['chatbot:viewinteractions'] = 'View student interactions with chatbot';
$string['chatbot:managebadges'] = 'Manage chatbot badges';
$string['chatbot:usechatbot'] = 'Use the chatbot';

// Block interface strings
$string['login_required'] = 'Please log in to use the chatbot';
$string['your_stats'] = 'Your Statistics';
$string['interactions'] = 'Interactions';
$string['your_badges'] = 'Your Badges';
$string['chat_with_assistant'] = 'Chat with your Learning Assistant';
$string['type_message'] = 'Type your message here...';
$string['send'] = 'Send';
$string['chat_history'] = 'Chat History';
$string['view_history'] = 'View your chat history';

// Settings page
$string['settings'] = 'Gamified Chatbot Settings';
$string['flowise_settings'] = 'Flowise Integration Settings';
$string['flowise_api_url'] = 'Flowise API URL';
$string['flowise_api_url_desc'] = 'The URL of your Flowise API endpoint';
$string['flowise_chatbot_url'] = 'Flowise Chatbot URL';
$string['flowise_chatbot_url_desc'] = 'The URL of your Flowise chatbot interface (for iframe embedding)';
$string['flowise_api_key'] = 'Flowise API Key';
$string['flowise_api_key_desc'] = 'Your Flowise API key for authentication';

// Gamification strings
$string['badge_novice'] = 'Novice Learner';
$string['badge_novice_desc'] = 'Awarded after 10 interactions with the chatbot';
$string['badge_explorer'] = 'Knowledge Explorer';
$string['badge_explorer_desc'] = 'Awarded after 50 interactions with the chatbot';
$string['badge_master'] = 'Learning Master';
$string['badge_master_desc'] = 'Awarded after 100 interactions with the chatbot';

// History page
$string['interaction_history'] = 'Chatbot Interaction History';
$string['date'] = 'Date';
$string['question'] = 'Your Question';
$string['answer'] = 'Chatbot Response';
$string['no_history'] = 'No chat history found';

// Error messages
$string['api_error'] = 'Error connecting to Flowise API';
$string['config_error'] = 'Chatbot is not properly configured. Please contact your administrator.';
$string['test_connection'] = 'Test Flowise Connection';
$string['connection_successful'] = 'Connection to Flowise successful!';
$string['connection_successful_details'] = 'Your Moodle instance can successfully communicate with the Flowise API.';
$string['connection_failed'] = 'Connection to Flowise failed';
$string['connection_failed_details'] = 'Unable to connect to the Flowise API. Please check your settings and ensure the Flowise server is running.';
$string['back_to_settings'] = 'Back to Settings';