<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/config.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$current_page = "admin-analytics";
$page_title = "Admin Analytics";

// Metrics by status
$statuses = ['draft','pending_review','published','rejected','archived'];
$counts = [];
foreach ($statuses as $s) {
  $stmt=$conn->prepare("SELECT COUNT(*) c FROM programs WHERE status = ?");
  $stmt->bind_param('s',$s); $stmt->execute(); $res=$stmt->get_result(); $counts[$s] = (int)($res->fetch_assoc()['c'] ?? 0);
}
$totalPrograms = array_sum($counts);

// Top teachers by published programs
$top = [];
$stmt = $conn->prepare("SELECT u.userID, u.fname, u.lname, COUNT(*) as cnt
                        FROM programs p
                        INNER JOIN teacher t ON p.teacherID = t.teacherID
                        INNER JOIN user u ON t.userID = u.userID
                        WHERE p.status='published'
                        GROUP BY u.userID, u.fname, u.lname
                        ORDER BY cnt DESC
                        LIMIT 5");
$stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){$top[]=$row;}

?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container"><div class="page-content">
<section class="content-section">
  <h1 class="section-title">Analytics</h1>
  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
    <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-books text-[28px]"></i><span class="text-2xl font-semibold"><?= $totalPrograms ?></span></div><p>Total Programs</p></div>
    <div class="p-4 rounded bg-company_orange text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-traffic-cone text-[28px]"></i><span class="text-2xl font-semibold"><?= $counts['draft'] + $counts['pending_review'] ?></span></div><p>Draft/Pending</p></div>
    <div class="p-4 rounded bg-company_green text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-seal-check text-[28px]"></i><span class="text-2xl font-semibold"><?= $counts['published'] ?></span></div><p>Published</p></div>
    <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-prohibit text-[28px]"></i><span class="text-2xl font-semibold"><?= $counts['rejected'] ?></span></div><p>Rejected</p></div>
    <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-archive text-[28px]"></i><span class="text-2xl font-semibold"><?= $counts['archived'] ?></span></div><p>Archived</p></div>
  </div>

  <div class="section-card">
    <div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold">Top Teachers by Published Programs</h2>
      <button onclick="downloadProgramsSummary()" class="group btn-secondary"><i class="ph ph-download text-[20px] group-hover:hidden"></i><i class="ph-duotone ph-download text-[20px] hidden group-hover:block"></i><p class="font-medium">Download Summary</p></button>
    </div>
    <div class="w-full overflow-x-auto">
      <table class="w-full bg-white border border-gray-200 rounded-lg">
        <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Teacher</th><th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Published Programs</th></tr></thead>
        <tbody>
          <?php if (empty($top)) : ?>
            <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">No data</td></tr>
          <?php else: foreach ($top as $t): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars(trim(($t['fname']??'').' '.($t['lname']??'')) ?: 'Unnamed') ?></td>
              <td class="px-4 py-2 text-sm text-gray-700"><?= (int)$t['cnt'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
</div></div>
<script>
function downloadProgramsSummary(){
  const rows=[
    ['Metric','Value'],
    ['Total Programs', <?= (int)$totalPrograms ?>],
    ['Draft', <?= (int)$counts['draft'] ?>],
    ['Pending Review', <?= (int)$counts['pending_review'] ?>],
    ['Published', <?= (int)$counts['published'] ?>],
    ['Rejected', <?= (int)$counts['rejected'] ?>],
    ['Archived', <?= (int)$counts['archived'] ?>]
  ];
  const csv=rows.map(r=>r.join(',')).join('\n');
  const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download=`programs_summary_${new Date().toISOString().split('T')[0]}.csv`; document.body.appendChild(link); link.click(); document.body.removeChild(link);
}
</script>
</body>
</html>