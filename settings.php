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
 * Settings for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Header for Flowise settings
    $settings->add(new admin_setting_heading(
        'block_chatbot_flowise_settings',
        get_string('flowise_settings', 'block_chatbot'),
        ''
    ));
    
    // Flowise API URL
    $settings->add(new admin_setting_configtext(
        'block_chatbot/flowise_api_url',
        get_string('flowise_api_url', 'block_chatbot'),
        get_string('flowise_api_url_desc', 'block_chatbot'),
        '',
        PARAM_URL
    ));
    
    // Flowise Chatbot URL (for iframe)
    $settings->add(new admin_setting_configtext(
        'block_chatbot/flowise_chatbot_url',
        get_string('flowise_chatbot_url', 'block_chatbot'),
        get_string('flowise_chatbot_url_desc', 'block_chatbot'),
        '',
        PARAM_URL
    ));
    
    // Flowise API Key
    $settings->add(new admin_setting_configpasswordunmask(
        'block_chatbot/flowise_api_key',
        get_string('flowise_api_key', 'block_chatbot'),
        get_string('flowise_api_key_desc', 'block_chatbot'),
        ''
    ));
    
    // Test connection button
    $testurl = new moodle_url('/blocks/chatbot/test_connection.php');
    $settings->add(new admin_setting_description(
        'block_chatbot/test_connection',
        '',
        html_writer::link($testurl, get_string('test_connection', 'block_chatbot'), 
            ['class' => 'btn btn-secondary', 'target' => '_blank'])
    ));

    // Registar a página de teste de conexão
    $ADMIN->add('blocksettings', new admin_externalpage(
        'blockchatbotsettings',
        get_string('test_connection', 'block_chatbot'),
        new moodle_url('/blocks/chatbot/test_connection.php')
));
}
