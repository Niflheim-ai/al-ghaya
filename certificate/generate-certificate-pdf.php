<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../php/dbConnection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    die('Unauthorized');
}

$student_id = (int)$_SESSION['userID'];
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

if (!$program_id) {
    http_response_code(400);
    die('Invalid program ID');
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['imageData'])) {
    http_response_code(400);
    die('No image data received');
}

$imageData = $data['imageData'];
$filename = $data['filename'] ?? 'certificate.pdf';

// Get certificate info for metadata
$stmt = $conn->prepare("
    SELECT 
        CONCAT(u.fname, ' ', u.lname) as student_name,
        p.title as program_title
    FROM student_program_certificates spc
    JOIN user u ON spc.student_id = u.userID
    JOIN programs p ON spc.program_id = p.programID
    WHERE spc.student_id = ? AND spc.program_id = ?
");
$stmt->bind_param("ii", $student_id, $program_id);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cert) {
    http_response_code(404);
    die('Certificate not found');
}

// Create HTML with embedded image
$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        img {
            width: 100%;
            height: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <img src="{$imageData}" alt="Certificate">
</body>
</html>
HTML;

// Configure DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Helvetica');

// Initialize DomPDF
$dompdf = new Dompdf($options);

// Load HTML with embedded image
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render PDF
$dompdf->render();

// Set PDF metadata
$dompdf->add_info('Title', 'Certificate of Completion - ' . $cert['program_title']);
$dompdf->add_info('Author', 'Al-Ghaya LMS');
$dompdf->add_info('Subject', 'Certificate for ' . $cert['student_name']);

// Output PDF for download
$dompdf->stream($filename, array('Attachment' => true));