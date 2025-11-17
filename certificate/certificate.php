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

// Get final exam score
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
$cert_code = 'AL-' . str_pad($cert['student_id'], 4, '0', STR_PAD_LEFT) . '-' . str_pad($cert['program_id'], 4, '0', STR_PAD_LEFT);
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
    
    <!-- Import Google Fonts for better rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="shortcut icon" href="../images/al-ghaya_logoForPrint.svg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Winky Rough", sans-serif;
            color: #10375b;
            font-size: 15px;
            margin: 0;
            background-color: #f3f4f6;
        }
        
        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        #certificate {
            width: 842px;
            height: 595px;
            background-image: url('CertificateBG.svg');
            background-size: cover;
            background-position: center;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 50px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .upper-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 600;
            padding-bottom: 25px;
            color: #10375b;
        }
        
        .content {
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 34px;
        }
        
        .content p {
            font-size: 16px;
            color: #10375b;
            line-height: 1.5;
        }
        
        .student-name {
            font-size: 40px;
            font-weight: 600;
            color: #10375b;
        }
        
        .bold {
            font-weight: 600;
        }
        
        .lower-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
        }
        
        .signature-section {
            width: 100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            position: relative;
        }
        
        .logo {
            width: 137.2px;
            height: 67.25px;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        
        .signature-block {
            text-align: center;
        }
        
        .signature-block p {
            font-size: 14px;
            color: #10375b;
            margin: 2px 0;
        }
        
        .lower-section > p {
            font-size: 14px;
            color: #10375b;
        }
        
        .button-container {
            margin-top: 40px;
            display: flex;
            gap: 16px;
        }
        
        .btn {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #1d4ed8;
        }
        
        .btn-secondary {
            background-color: #4b5563;
            color: #ffffff;
        }
        
        .btn-secondary:hover {
            background-color: #374151;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="container">
        <div id="certificate">
            <div class="upper-section">
                <h1>Certificate of Completion</h1>
                <div class="content">
                    <p>This is to certify that</p>
                    <h2 class="student-name"><?= htmlspecialchars($name) ?></h2>
                    <p>has successfully completed <span class="bold"><?= htmlspecialchars($course) ?></span> with a grade of <span class="bold"><?= $score ?>%</span></p>
                </div>
            </div>
            
            <div class="lower-section">
                <div class="signature-section">
                    <img src="./Logo.svg" alt="Logo" class="logo" crossorigin="anonymous">
                    <div class="signature-block">
                        <p class="bold">Omar Eguia</p>
                        <p>Head of Al-Ghaya</p>
                    </div>
                    <div class="signature-block">
                        <p>Certificate Code:</p>
                        <p class="bold"><?= $cert_code ?></p>
                    </div>
                </div>
                <p>Date: <span><?= $date ?></span></p>
            </div>
        </div>
        
        <div class="button-container">
            <button id="downloadButton" class="btn btn-primary">
                Download Certificate
            </button>
            <a href="../pages/student/student-program-view.php?program_id=<?= $program_id ?>" class="btn btn-secondary">
                Back to Program
            </a>
        </div>
    </div>

    <script>
        document.getElementById('downloadButton').addEventListener('click', async function() {
            const button = this;
            const originalText = button.textContent;
            
            // Disable button
            button.disabled = true;
            button.textContent = 'Generating Image...';
            
            try {
                const element = document.getElementById('certificate');
                
                // Wait a bit for any rendering to complete
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Use html2canvas to capture the certificate
                const canvas = await html2canvas(element, {
                    scale: 3, // Higher quality
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 0,
                    onclone: function(clonedDoc) {
                        // Ensure fonts are loaded in cloned document
                        const clonedElement = clonedDoc.getElementById('certificate');
                        if (clonedElement) {
                            clonedElement.style.fontFamily = '"Winky Rough", sans-serif';
                            clonedElement.style.color = '#10375b';
                        }
                    }
                });
                
                // Convert canvas to blob and download
                canvas.toBlob(function(blob) {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'certificate_<?= str_replace(" ", "_", $name) ?>_<?= $cert_code ?>.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    
                    // Success feedback
                    button.textContent = '✓ Downloaded!';
                    setTimeout(() => {
                        button.textContent = originalText;
                    }, 2000);
                }, 'image/png', 1.0);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to download certificate. Error: ' + error.message);
            } finally {
                button.disabled = false;
            }
        });
    </script>

    <!-- Load html2canvas from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</body>

</html>
