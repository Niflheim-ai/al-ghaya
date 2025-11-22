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

// Get certificate settings
$certSettings = $conn->query("SELECT * FROM certificate_settings WHERE id = 1")->fetch_assoc();

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

// Define the validation URL for QR code
$validationURL = "validate-certificate.php?code=" . urlencode($cert_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=900, initial-scale=1.0">
    <title>Certificate - <?= htmlspecialchars($course) ?></title>
    <link rel="shortcut icon" href="../images/al-ghaya_logoForPrint.svg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: <?= $certSettings['font_family'] ?>, sans-serif; color: <?= $certSettings['primary_color'] ?>; background-color: #f3f4f6; }
        .container { width: 100%; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding:40px 20px;}
        #certificate {
            width: 842px; height: 595px;
            background-image: url('<?= $certSettings['background_image'] ?>');
            background-size: cover; background-position: center; background-color: #fff;
            border-radius: 18px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.09);
            position: relative; padding: 50px; display: flex; flex-direction: column; justify-content: space-between; align-items: center;
        }
        .upper-section { display: flex; flex-direction: column; align-items: center; text-align:center;}
        .student-name { font-size: 40px; font-weight: 600; color: <?= $certSettings['primary_color'] ?>; }
        h1 { font-size: 24px; font-weight: 600; padding-bottom: 25px; color: <?= $certSettings['primary_color'] ?>;}
        .lower-section { width: 100%; display: flex; flex-direction: column; align-items: center; gap: 25px; }
        .signature-section { width: 100%; display: flex; justify-content: space-around; align-items: center; position: relative; }
        .logo { width: 137.2px; height: 67.25px; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);}
        .button-container { margin-top: 40px; display: flex; gap: 16px;}
        .btn { padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s;}
        .btn-primary { background-color: #2563eb; color: #fff; }
        .btn-primary:hover:enabled{ background-color: #1d4ed8;}
        .btn-secondary { background-color: #4b5563; color: #fff;}
        .btn-secondary:hover { background-color: #374151;}
        .btn:disabled { opacity: 0.67; cursor: not-allowed;}
        /* QR styles */
        #certificate-qr { position:absolute; right:40px; bottom:50px; width:90px; height:90px; z-index:10; background:#fff8; border-radius:10px; padding:4px; display:flex;align-items:center;justify-content:center;}
        .cert-qr-label { font-size:11px; color:#10375B; position:absolute; right:44px; bottom:0px; width:85px; text-align:center; letter-spacing:0.18em; background:#fff7; line-height:1.3;}
    </style>
</head>
<body>
<div class="container">
    <div id="certificate">
        <div class="upper-section">
            <h1><?= htmlspecialchars($certSettings['header_title']) ?></h1>
            <div class="content" style="max-width:600px;display:flex;flex-direction:column;align-items:center;gap:34px;">
                <p><?= htmlspecialchars($certSettings['intro_text']) ?></p>
                <h2 class="student-name"><?= htmlspecialchars($name) ?></h2>
                <p>
                    <?= htmlspecialchars($certSettings['completion_text']) ?>
                    <span style="font-weight:600"><?= htmlspecialchars($course) ?></span>
                    <?php if ($certSettings['show_grade']): ?>
                        <?= htmlspecialchars($certSettings['grade_text']) ?>
                        <span style="font-weight:600"><?= $score ?>%</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="lower-section">
            <div class="signature-section">
                <img src="<?= htmlspecialchars($certSettings['logo_image']) ?>" alt="Logo" class="logo" crossorigin="anonymous">
                <div class="signature-block" style="text-align:center;">
                    <p style="font-weight:600;"><?= htmlspecialchars($certSettings['signature_name']) ?></p>
                    <p><?= htmlspecialchars($certSettings['signature_title']) ?></p>
                </div>
                <?php if ($certSettings['show_certificate_code']): ?>
                <div class="signature-block" style="text-align:center;">
                    <p><?= htmlspecialchars($certSettings['certificate_code_label']) ?></p>
                    <p style="font-weight:600;"><?= $cert_code ?></p>
                </div>
                <?php endif; ?>
            </div>
            <p><?= htmlspecialchars($certSettings['date_label']) ?> <span><?= $date ?></span></p>
        </div>
        <!-- QR code block -->
        <div id="certificate-qr"></div>
        <div class="cert-qr-label">
          Verify: <br><?= htmlspecialchars($cert_code) ?>
        </div>
    </div>
    <div class="button-container">
        <button id="downloadButton" class="btn btn-primary">Download Certificate</button>
        <a href="../pages/student/student-program-view.php?program_id=<?= $program_id ?>" class="btn btn-secondary">Back to Program</a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  // Generate QR for validation
  document.getElementById('certificate-qr').innerHTML = '';
  new QRCode(document.getElementById("certificate-qr"), {
    text: "<?= htmlspecialchars($validationURL) ?>",
    width: 80,
    height: 80,
    colorDark: "#10375B",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
  });
});
document.getElementById('downloadButton').addEventListener('click', async function() {
    const button = this;
    const originalText = button.textContent;
    button.disabled = true; button.textContent = 'Generating Image...';
    try {
        const element = document.getElementById('certificate');
        await new Promise(resolve => setTimeout(resolve, 600));
        const canvas = await html2canvas(element, {
            scale: 3, useCORS: true, allowTaint: false, backgroundColor: '#fff', logging: false, imageTimeout: 0
        });
        canvas.toBlob(function(blob) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'certificate_<?= str_replace(" ", "_", $name) ?>_<?= $cert_code ?>.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            button.textContent = '✓ Downloaded!';
            setTimeout(() => { button.textContent = originalText; }, 2000);
        }, 'image/png', 1.0);
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to download certificate. Error: ' + error.message);
    } finally {
        button.disabled = false;
    }
});
</script>
</body>
</html>