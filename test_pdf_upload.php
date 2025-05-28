<?php
// Salve como test_pdf_upload.php na raiz do diretório do plugin

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Require admin login
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/blocks/chatbot/test_pdf_upload.php');
$PAGE->set_title('Teste de Upload de PDF para Flowise');
$PAGE->set_heading('Teste de Upload de PDF para Flowise');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['pdfFile'])) {
    $file = $_FILES['pdfFile'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Verificar se é um PDF
        if ($file['type'] !== 'application/pdf') {
            $message = '<div class="alert alert-danger">Apenas arquivos PDF são suportados.</div>';
        } else {
            // Create temporary file
            $tempdir = make_temp_directory('block_chatbot');
            $tempfilepath = $tempdir . '/' . $file['name'];
            
            if (move_uploaded_file($file['tmp_name'], $tempfilepath)) {
                // Obter valores do formulário
                $usage = required_param('usage', PARAM_TEXT);
                $legacyBuild = optional_param('legacyBuild', 0, PARAM_BOOL);
                $additionalMetadata = optional_param('additionalMetadata', '', PARAM_TEXT);
                
                // Endpoint correto para o PDF File Loader
                $uploadendpoint = "http://localhost:3000/api/v1/nodes/pdfFile";
                
                // Inicializar cURL
                $ch = curl_init() ;
                
                // Preparar o ficheiro para upload
                $cfile = new \CURLFile($tempfilepath, $file['type'], $file['name']);
                
                // Metadados do Moodle
                $metadata = array(
                    'source' => 'moodle_test',
                    'courseid' => $COURSE->id,
                    'coursename' => $COURSE->fullname,
                    'userid' => $USER->id,
                    'username' => fullname($USER),
                    'uploadtime' => time(),
                    'documenttype' => 'test_upload'
                );
                
                // Adicionar metadados adicionais se fornecidos
                if (!empty($additionalMetadata)) {
                    try {
                        $extraMeta = json_decode($additionalMetadata, true);
                        if (is_array($extraMeta)) {
                            $metadata = array_merge($metadata, $extraMeta);
                        }
                    } catch (Exception $e) {
                        // Ignorar erro de JSON inválido
                    }
                }
                
                // Preparar os dados POST
                $postdata = array(
                    'file' => $cfile,
                    'usage' => $usage,
                    'legacyBuild' => $legacyBuild ? 'true' : 'false',
                    'metadata' => json_encode($metadata)
                );
                
                // Configurar opções cURL
                curl_setopt($ch, CURLOPT_URL, $uploadendpoint);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer rUSCBhWX2L2xlamT7siiLrS4v2QsGiW51qIBBlGrS_U',
                    'Accept: application/json'
                ));
                
                // Executar sessão cURL
                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ;
                $error = curl_error($ch);
                
                // Fechar sessão cURL
                curl_close($ch);
                
                // Clean up
                unlink($tempfilepath);
                
                if ($httpcode >= 200 && $httpcode < 300 && $response)  {
                    $message = '<div class="alert alert-success">PDF enviado com sucesso para o Flowise!</div>';
                    $message .= '<div class="alert alert-info"><strong>Resposta:</strong> <pre>' . htmlspecialchars($response) . '</pre></div>';
                } else {
                    $message = '<div class="alert alert-danger">Falha ao enviar o PDF para o Flowise.</div>';
                    $message .= '<div class="alert alert-info"><strong>Código HTTP:</strong> ' . $httpcode . '</div>';
                    if (!empty($error) ) {
                        $message .= '<div class="alert alert-info"><strong>Erro cURL:</strong> ' . $error . '</div>';
                    }
                    $message .= '<div class="alert alert-info"><strong>Resposta:</strong> <pre>' . htmlspecialchars($response) . '</pre></div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Erro ao mover o ficheiro carregado.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger">Erro no upload do ficheiro: ' . $file['error'] . '</div>';
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Teste de Upload de PDF para Flowise');

// Display message if any
echo $message;

// Display form
?>
<div class="card">
    <div class="card-body">
        <p>Esta página permite testar o upload de documentos PDF para o Flowise usando o endpoint correto.</p>
        <p><strong>Endpoint:</strong> http://localhost:3000/api/v1/nodes/pdfFile</p>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label for="pdfFile">Ficheiro PDF:</label>
                <input type="file" name="pdfFile" id="pdfFile" class="form-control" accept=".pdf" required>
                <small class="form-text text-muted">Escolha um ficheiro para upload</small>
            </div>
            
            <div class="form-group mb-3">
                <label for="usage">Uso:</label>
                <select name="usage" id="usage" class="form-control">
                    <option value="perPage" selected>Um documento por página</option>
                    <option value="perFile">Um documento por ficheiro</option>
                </select>
            </div>
            
            <div class="form-group mb-3">
                <div class="form-check">
                    <input type="checkbox" name="legacyBuild" id="legacyBuild" class="form-check-input" value="1">
                    <label class="form-check-label" for="legacyBuild">Usar Legacy Build</label>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="additionalMetadata">Metadados Adicionais (formato JSON) :</label>
                <textarea name="additionalMetadata" id="additionalMetadata" class="form-control" rows="3" placeholder='{"key1": "value1", "key2": "value2"}'></textarea>
                <small class="form-text text-muted">Metadados adicionais a serem adicionados aos documentos extraídos</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Enviar para Flowise</button>
        </form>
    </div>
</div>

<?php
echo $OUTPUT->footer();
