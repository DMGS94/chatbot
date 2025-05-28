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
 * Chat API endpoint for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define('AJAX_SCRIPT', true);

 require_once('../../config.php');
 require_once($CFG->dirroot.'/blocks/chatbot/classes/flowise.php');
 
 // Adicionar log de depuração
 error_log('chat_api.php: Requisição recebida');
 
 // Require login
 require_login();
 
 // Set up page
 $PAGE->set_url('/blocks/chatbot/chat_api.php');
 $PAGE->set_context(context_system::instance());
 
 // Check capability
 if (!has_capability('block/chatbot:usechatbot', $PAGE->context)) {
     error_log('chat_api.php: Erro de permissão');
     $response = array(
         'status' => 'error',
         'message' => get_string('nopermission', 'error')
     );
     echo json_encode($response);
     die;
 }
 
 // Get parameters
 $message = required_param('message', PARAM_TEXT);
 $courseid = required_param('courseid', PARAM_INT);
 $sessionid = optional_param('sessionid', '', PARAM_TEXT);
 
 error_log('chat_api.php: Parâmetros - message: ' . $message . ', courseid: ' . $courseid);
 
 // Modificar a chamada para a API Flowise para usar diretamente os valores da API
 // em vez de obter das configurações
 $chatendpoint = "http://localhost:3000/api/v1/prediction/ad7941c1-8011-4a7c-b314-2e2c0181076f";
 $apikey = "rUSCBhWX2L2xlamT7siiLrS4v2QsGiW51qIBBlGrS_U";
 
 // Prepare the POST data
 $postdata = array(
     'question' => $message,
     'sessionId' => $sessionid
 ) ;
 
 // Initialize cURL session
 $ch = curl_init();
 
 // Set cURL options
 curl_setopt($ch, CURLOPT_URL, $chatendpoint);
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_HTTPHEADER, array(
     'Authorization: Bearer ' . $apikey,
     'Content-Type: application/json',
     'Accept: application/json'
 ));
 
 // Execute cURL session
 $response = curl_exec($ch);
 $error = curl_error($ch);
 $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ;
 
 // Log de depuração
 error_log('chat_api.php: Resposta HTTP: ' . $httpcode) ;
 if (!empty($error)) {
     error_log('chat_api.php: Erro cURL: ' . $error);
 }
 error_log('chat_api.php: Resposta: ' . $response);
 
 // Close cURL session
 curl_close($ch);
 
 // Check if the request was successful
 if ($httpcode >= 200 && $httpcode < 300 && $response)  {
     $responseData = json_decode($response, true);
     
     // Registrar a interação no banco de dados (se as tabelas existirem)
     try {
         $interactionid = $DB->insert_record('block_chatbot_interactions', array(
             'userid' => $USER->id,
             'courseid' => $courseid,
             'question' => $message,
             'answer' => $responseData['text'],
             'timecreated' => time()
         ));
         
         // Success
         echo json_encode(array(
             'status' => 'success',
             'message' => $responseData['text'],
             'sessionid' => isset($responseData['sessionId']) ? $responseData['sessionId'] : $sessionid,
             'interactionid' => $interactionid
         ));
     } catch (Exception $e) {
         // Erro ao registrar no banco de dados, mas ainda retorna a resposta
         error_log('chat_api.php: Erro ao registrar interação: ' . $e->getMessage());
         echo json_encode(array(
             'status' => 'success',
             'message' => $responseData['text'],
             'sessionid' => isset($responseData['sessionId']) ? $responseData['sessionId'] : $sessionid,
             'interactionid' => 0
         ));
     }
 } else {
     // Error
     error_log('chat_api.php: Erro na resposta da API');
     echo json_encode(array(
         'status' => 'error',
         'message' => get_string('api_error', 'block_chatbot')
     ));
 }