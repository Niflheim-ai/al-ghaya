<?php
require '../php/dbConnection.php';

$code = isset($_GET['code']) ? $_GET['code'] : '';
$info = false;
$error = '';

if ($code && preg_match('/^AL-\d{4}-\d{4}$/', $code)) {
    list(,$sid,$pid) = explode('-', $code);
    $student_id = (int)ltrim($sid, '0');
    $program_id = (int)ltrim($pid, '0');
    $stmt = $conn->prepare("
        SELECT 
            CONCAT(u.fname, ' ', u.lname) as student_name,
            u.email as student_email,
            p.title as program_title,
            p.category as program_category,
            spc.issue_date
        FROM student_program_certificates spc
        JOIN user u ON spc.student_id = u.userID
        JOIN programs p ON spc.program_id = p.programID
        WHERE spc.student_id = ? AND spc.program_id = ?
    ");
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$info) $error = "No such certificate was found for the code entered.";
} else {
    $error = "Invalid or missing certificate code.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Validation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f6fa; color: #222; }
        .container { max-width: 550px; margin: 60px auto; background: #fff; border-radius: 14px; box-shadow: 0 4px 44px 0 rgba(30,50,70,0.11); padding:38px 30px; }
        h1 { font-size: 2rem; font-weight:700; margin-bottom: 18px;}
        .cert-good { color: #059669;}
        .cert-bad { color: #ef4444;}
        .details { margin-top:24px;font-size:1.05rem;}
        label { font-weight: 600; color: #2563eb; margin-right: 5px;}
        .input-bar { width: 85%; margin-bottom:16px; }
        .barcode-box { margin-top: 32px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Certificate Validation</h1>
    <?php if ($info): ?>
        <div class="cert-good"><strong>This certificate is VALID.</strong></div>
        <div class="details">
            <p><label>Student:</label> <?= htmlspecialchars($info['student_name']) ?></p>
            <p><label>Email:</label> <?= htmlspecialchars($info['student_email']) ?></p>
            <p><label>Program:</label> <?= htmlspecialchars($info['program_title']) ?></p>
            <p><label>Category:</label> <?= htmlspecialchars($info['program_category']) ?></p>
            <p><label>Date Issued:</label> <?= date('F j, Y', strtotime($info['issue_date'])) ?></p>
            <p><label>Certificate Code:</label> <?= htmlspecialchars($code) ?></p>
        </div>
    <?php else: ?>
        <div class="cert-bad">
            <strong>Certificate not found or invalid.</strong>
        </div>
        <form method="get">
            <input type="text" name="code" class="input-bar" placeholder="Enter certificate code (ex: AL-0001-0021)" required>
            <button type="submit" class="btn btn-primary">Validate</button>
        </form>
        <?php if ($error): ?>
            <div class="cert-bad" style="margin-top:14px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>