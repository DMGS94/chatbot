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
 * Test connection to Flowise API
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php') ;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/chatbot/classes/flowise.php');

// Require admin login
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/blocks/chatbot/test_connection.php');
$PAGE->set_title(get_string('test_connection', 'block_chatbot'));
$PAGE->set_heading(get_string('test_connection', 'block_chatbot'));
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('test_connection', 'block_chatbot'));

// Test connection to Flowise API
$result = block_chatbot_flowise::test_connection();

if ($result) {
    echo $OUTPUT->notification(get_string('connection_successful', 'block_chatbot'), 'success');
    echo html_writer::tag('p', get_string('connection_successful_details', 'block_chatbot'));
} else {
    echo $OUTPUT->notification(get_string('connection_failed', 'block_chatbot'), 'error');
    echo html_writer::tag('p', get_string('connection_failed_details', 'block_chatbot'));
    
    // Check configuration
    if (!block_chatbot_flowise::is_configured()) {
        echo $OUTPUT->notification(get_string('config_error', 'block_chatbot'), 'error');
    }
    
    // Adicione código de depuração aqui
    $apiurl = get_config('block_chatbot', 'flowise_api_url');
    $apikey = get_config('block_chatbot', 'flowise_api_key');

    echo html_writer::tag('h3', 'Informações de Depuração:');
    echo html_writer::tag('p', 'API URL configurada: ' . $apiurl);
    echo html_writer::tag('p', 'API Key configurada: ' . (empty($apikey) ? 'Não definida' : 'Definida (oculta por segurança)'));

    // Teste manual com cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $apikey,
        'Accept: application/json'
    ));
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ;
    curl_close($ch);

    echo html_writer::tag('p', 'Código de resposta HTTP: ' . $httpcode) ;
    if (!empty($error)) {
        echo html_writer::tag('p', 'Erro cURL: ' . $error);
    }
    echo html_writer::tag('p', 'Resposta: ' . htmlspecialchars($response));
}

// Back to settings button
$settingsurl = new moodle_url('/admin/settings.php', array('section' => 'blocksettingchatbot'));
echo html_writer::tag('div', 
    $OUTPUT->single_button($settingsurl, get_string('back_to_settings', 'block_chatbot')),
    array('class' => 'mt-3')
);

echo $OUTPUT->footer();
