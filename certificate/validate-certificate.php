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
} else if ($code !== '') {
    $error = "Invalid or missing certificate code.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Validation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CDN – for a production site, use your own build -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      body {
        background-image: url("backgrounds/CertificateBG.svg");
        background-repeat: no-repeat;
        background-size: cover;
        min-height: 100vh;
      }
    </style>

    <link rel="shortcut icon" href="../images/al-ghaya_logoForPrint.svg" type="image/x-icon">
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="w-full min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full bg-white/90 rounded-xl shadow-2xl mx-4 my-8 flex flex-col justify-center items-center p-6 sm:p-10">
      <h1 class="text-2xl sm:text-3xl font-bold text-blue-900 mb-3 text-center">Certificate Validation</h1>
      <?php if ($info): ?>
        <div class="w-full mb-6">
          <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-2 mb-4 justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-green-700 font-semibold">This certificate is VALID.</span>
          </div>
          <div class="text-sm sm:text-base space-y-3">
            <p><span class="font-semibold text-blue-700">Student:</span> <?= htmlspecialchars($info['student_name']) ?></p>
            <p><span class="font-semibold text-blue-700">Email:</span> <?= htmlspecialchars($info['student_email']) ?></p>
            <p><span class="font-semibold text-blue-700">Program:</span> <?= htmlspecialchars($info['program_title']) ?></p>
            <p><span class="font-semibold text-blue-700">Category:</span> <?= htmlspecialchars($info['program_category']) ?></p>
            <p><span class="font-semibold text-blue-700">Date Issued:</span> <?= date('F j, Y', strtotime($info['issue_date'])) ?></p>
            <p><span class="font-semibold text-blue-700">Certificate Code:</span> <span class="tracking-wider"><?= htmlspecialchars($code) ?></span></p>
          </div>
        </div>
      <?php else: ?>
        <div class="w-full mb-6">
          <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 flex items-center gap-2 mb-4 justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span class="text-red-700 font-semibold">Certificate not found or invalid.</span>
          </div>
        </div>
        <form method="get" class="w-full flex flex-col items-center gap-4">
          <input type="text" name="code" class="input-bar w-full py-3 px-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition text-base tracking-widest"
            placeholder="Enter certificate code (ex: AL-0001-0021)" required>
          <button type="submit" class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 font-semibold transition">
            Validate
          </button>
        </form>
        <?php if ($error): ?>
          <div class="cert-bad mt-4 text-red-600 font-medium text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>