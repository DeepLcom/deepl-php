<?php
/**
 * This PHP script is used to translate HTML and text files using DeepL's API.
 *
 * It requires the following environment variables to be set:
 * - DEEPL_API_KEY: The DeepL API key.
 *
 * It accepts the following parameters via POST request:
 * - upload-type: Indicates whether to upload a file or enter a URL. Possible values: "upload-file", "enter-url".
 * - source-language: The source language code.
 * - target-language: The target language code.
 * - url-input: The URL of the file to translate if upload-type is "enter-url".
 *
 * It returns a JSON response with the following properties:
 * - status: Indicates whether the operation was successful or not. Possible values: "success", "error".
 * - filename: The name of the file that was translated (if applicable).
 * - msg: An error message (if applicable).
 * - response: The DeepL API response ([documentHandle, documentStatus] if applicable).
 */

require_once '../../vendor/autoload.php';

$output = [
    'status'    => 'error',
    'filename'  => '',
    'msg'       => '',
    'response'  => null
];

try {
    // Load the DeepL API key from the .env file
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();
    $apiKey = $_ENV['DEEPL_API_KEY'];

    if (!$apiKey) {
        throw new \Exception('No API key specified. You must create a ".env" file in the root of the project with DEEPL_API_KEY="YOUR_DEEPL_API_KEY"');
    }

    // Check that the request is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Invalid request method.');
    }

    // Check that the required parameters are present
    if (!isset($_POST['upload-type'])) {
        throw new \Exception('No upload type specified.');
    }
    if (!isset($_POST['source-language']) || !isset($_POST['target-language']) || !preg_match('/^[a-zA-Z-]{2,5}$/', $_POST['source-language']) || !preg_match('/^[a-zA-Z-]{2,5}$/', $_POST['target-language'])) {
        throw new \Exception('Invalid source or target language specified.');
    }

    // Define the directory where uploaded files will be stored
    $srcDir = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '../uploads/';

    // Handle the uploaded file
    if ($_POST['upload-type'] === 'upload-file') {
        if (!isset($_FILES['uploaded-file'])) {
            throw new \Exception('No uploaded file specified.');
        }

        $file = $_FILES['uploaded-file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error uploading file: ' . $file['error']);
        }

        $fileName = $file['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!in_array($fileExtension, ['html', 'txt'])) {
            throw new \Exception('Only .html or .txt files are allowed. Please upload a valid file.');
        }

        $fileMimeType = mime_content_type($file['tmp_name']);

        if ($fileMimeType !== 'text/plain' && $fileMimeType !== 'text/html') {
            throw new \Exception('Invalid file type. Only html or text files are allowed.');
        }

        $srcFile = $srcDir . basename($fileName);

        if (!move_uploaded_file($file['tmp_name'], $srcFile)) {
            throw new \Exception('Error moving uploaded file to target directory.');
        }
        $output['filename'] = basename($fileName);
    } elseif ($_POST['upload-type'] === 'enter-url') {
        $url = filter_var($_POST['url-input'], FILTER_SANITIZE_URL);

        // Remove 'http://' or 'https://' from $_POST['url-input']
        $fileName = preg_replace('/^https?:\/\//', '', $_POST['url-input']);

        // Remove all the slashes
        $fileName = str_replace('/', '', $fileName);

        // If $fileName doesn't end with a '.html' extension, add it
        if (!preg_match('/\.html$/', $fileName)) {
            $fileName.= '.html';
        }

        $inputFile = file_get_contents($_POST['url-input']);
        file_put_contents($srcDir. $fileName, $inputFile);

        $srcFile = $srcDir. $fileName;

        $output['filename'] = $fileName;
    } else {
        throw new \Exception('Invalid upload type.');
    }

    // Initialize the DeepL translator
    try {
        $deepl_translator = new \DeepL\Translator($apiKey);

        // Translate the uploaded file
        $options = [
            'formality' => 'prefer_more',
            'tag_handling' => 'html',
            'ignore_tags' => ['script', 'noscript', 'style', 'pre', 'code']
        ];

        $document_handle = $deepl_translator->uploadDocument(
            $srcFile,
            $_POST['source-language'],
            $_POST['target-language'],
            $options
        );

        $document_status = $deepl_translator->getDocumentStatus($document_handle);

        $output['status'] = 'success';
        $output['response'] = [
            'documentHandle' => $document_handle,
            'documentStatus' => $document_status
        ];
    } catch (\DeepL\DocumentTranslationException $error) {
        // If the error occurs after the document was already uploaded,
        // documentHandle will contain the document ID and key
        throw new \Exception('Error occurred while translating document: ' . ($error->getMessage() ?? 'unknown error'));
    }
} catch (\Exception $e) {
    $output['status'] = 'error';
    $output['msg'] = $e->getMessage();
}

echo json_encode($output);
