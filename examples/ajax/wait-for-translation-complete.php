<?php
require_once '../../vendor/autoload.php';

$has_error = false;

$output = [
    'status'          => 'error',
    'msg'             => '',
    'documentStatus'  => null,
    'downloadUrl'     => null
];

try {
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();
    $apiKey = $_ENV['DEEPL_API_KEY'];
    if (!$apiKey) {
        throw new \Exception('No API key specified. You must create a ".env" file in the root of the project with DEEPL_API_KEY="YOUR_DEEPL_API_KEY"');
    }

    if (!isset($_POST['document-handle'])) {
        throw new \Exception('No document handle specified.');
    }

    $dh = json_decode($_POST['document-handle'], true);
    if (!$dh) {
        throw new \Exception('Invalid document handle');
    }

    $document_handle = new \DeepL\DocumentHandle($dh['documentId'], $dh['documentKey']);

    try {
        $output['status'] = 'success';

        $deepl_translator = new \DeepL\Translator($apiKey);

        $document_status = $deepl_translator->waitUntilDocumentTranslationComplete($document_handle);

        $output['documentStatus'] = $document_status;

        if ($document_status->status == 'done') {
            $ext = pathinfo($_POST['filename'], PATHINFO_EXTENSION);
            $name = pathinfo($_POST['filename'], PATHINFO_FILENAME);

            $newFilename = $name. '-' . $_POST['target-language'] . '-translated.' . $ext;

            if (file_exists('../uploads/' . $newFilename)) {
                unlink('../uploads/'. $newFilename);
            }

            $deepl_translator->downloadDocument($document_handle, '../uploads/' . $newFilename);
            $output['downloadUrl'] = 'uploads/'. $newFilename;
        }
    } catch (\Exception $e) {
        $output['status'] = 'error';
        $output['msg'] = $e->getMessage();
    }
} catch (\Exception $e) {
    $output['status'] = 'error';
    $output['msg'] = $e->getMessage();
}

echo json_encode($output);
