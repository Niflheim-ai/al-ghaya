<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/dbConnection.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../pages/login.php');
    exit();
}

$student_id = (int)$_SESSION['userID'];
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

if (!$program_id) {
    die('Invalid program ID');
}

// Get certificate info from database
$stmt = $conn->prepare("
    SELECT 
        CONCAT(u.fname, ' ', u.lname) as student_name,
        u.email as student_email,
        p.title as program_title,
        p.category as program_category,
        spc.issue_date,
        spc.student_id,
        spc.program_id
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
    die('Certificate not found. Please complete the program exam first.');
}

// Get final exam score (optional)
$scoreStmt = $conn->prepare("
    SELECT score, max_score 
    FROM student_quiz_attempts 
    WHERE student_id = ? 
    ORDER BY attempt_date DESC 
    LIMIT 1
");
$scoreStmt->bind_param("i", $student_id);
$scoreStmt->execute();
$scoreResult = $scoreStmt->get_result()->fetch_assoc();
$scoreStmt->close();

$score = $scoreResult ? round(($scoreResult['score'] / $scoreResult['max_score']) * 100, 2) : 0;

// Generate certificate code
$cert_code = 'AL-' . str_pad($cert['student_id'], 4, '0', STR_PAD_LEFT) . '-' . str_pad($cert['program_id'], 4, '0', STR_PAD_LEFT);

// Set variables for template
$name = $cert['student_name'];
$course = $cert['program_title'];
$date = date("F j, Y", strtotime($cert['issue_date']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?= htmlspecialchars($course) ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body class="bg-gray-100">
    <section class="w-full min-h-screen flex flex-col justify-center items-center py-10">
        <div id="certificate" class="w-[842px] h-[595px] bg-[url('CertificateBG.svg')] flex flex-col justify-between items-center p-[50px] bg-white shadow-2xl">
            <!-- Upper Part -->
            <div class="flex flex-col justify-center items-center">
                <h1 class="font-semibold text-[24px] pb-[25px]">Certificate of Completion</h1>
                <div class="max-w-[600px] size-auto flex flex-col items-center gap-[34px] justify-center text-center">
                    <p>This is to certify that</p>
                    <h2 class="font-semibold text-[40px]"><?= htmlspecialchars($name) ?></h2>
                    <p>has successfully completed <span class="font-semibold"><?= htmlspecialchars($course) ?></span> with a grade of <span class="font-semibold"><?= $score ?>%</span></p>
                </div>
            </div>
            <!-- Lower Part -->
            <div class="w-full h-fit flex flex-col justify-center items-center gap-[25px]">
                <div class="w-full h-fit flex justify-around items-center relative">
                    <img src="./Logo.svg" alt="Logo" class="w-[137.2px] h-[67.25px] absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div class="size-auto text-center flex flex-col">
                        <p class="font-semibold">Omar Eguia</p>
                        <p>Head of Al-Ghaya</p>
                    </div>
                    <div class="size-auto text-center flex flex-col">
                        <p>Certificate Code:</p>
                        <p class="font-semibold"><?= $cert_code ?></p>
                    </div>
                </div>
                <p>Date: <span><?= $date ?></span></p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="mt-10 flex gap-4">
            <button id="downloadButton" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-lg transition-all">
                Download Certificate
            </button>
            <a href="../pages/student/student-program-view.php?program_id=<?= $program_id ?>" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold shadow-lg transition-all">
                Back to Program
            </a>
        </div>
    </section>

    <script>
        document.getElementById('downloadButton').addEventListener('click', function() {
            const element = document.getElementById('certificate');

            // Use html2canvas to capture the element
            html2canvas(element, {
                scale: 2 // Higher quality
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 1);

                // Access jsPDF from the global window.jspdf
                const {
                    jsPDF
                } = window.jspdf;

                // Create jsPDF with landscape A4
                const pdf = new jsPDF({
                    unit: 'in',
                    format: 'a4',
                    orientation: 'landscape'
                });

                // Get page dimensions in inches
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();

                // Assume 96 DPI for converting pixel dimensions to inches
                const imgWidth = canvas.width / 96;
                const imgHeight = canvas.height / 96;

                // Calculate centered position
                const x = (pageWidth - imgWidth) / 2;
                const y = (pageHeight - imgHeight) / 2;

                // Add the image to the PDF at the centered position
                pdf.addImage(imgData, 'JPEG', x, y, imgWidth, imgHeight);

                // Save the PDF with dynamic filename
                pdf.save('certificate_<?= str_replace(' ', '_', $name) ?>_<?= $cert_code ?>.pdf');
            });
        });
    </script>
</body>

</html>
