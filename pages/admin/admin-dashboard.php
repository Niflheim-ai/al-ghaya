<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/config.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

// Time range filter
$range = $_GET['range'] ?? '7d';
$now = new DateTime('now');
$from = clone $now;
if ($range === '30d') { $from->modify('-30 days'); } else { $from->modify('-7 days'); }
$fromStr = $from->format('Y-m-d 00:00:00');

// Metrics
function scalar($conn,$sql,$types='',$params=[]) { $stmt=$conn->prepare($sql); if($types){$stmt->bind_param($types,...$params);} $stmt->execute(); $res=$stmt->get_result(); $row=$res->fetch_assoc(); return array_values($row)[0] ?? 0; }

$metrics = [
  'students'  => scalar($conn, "SELECT COUNT(*) c FROM user WHERE role='student' AND isActive=1"),
  'teachers'  => scalar($conn, "SELECT COUNT(*) c FROM user WHERE role='teacher' AND isActive=1"),
  'programs'  => scalar($conn, "SELECT COUNT(*) c FROM programs"),
  'published' => scalar($conn, "SELECT COUNT(*) c FROM programs WHERE status='published'"),
  'pending'   => scalar($conn, "SELECT COUNT(*) c FROM programs WHERE status IN ('draft','pending_review')"),
  'signups7'  => scalar($conn, "SELECT COUNT(*) c FROM user WHERE dateCreated >= ?", 's', [$fromStr])
];

// Recent activity (last 10)
$activity = [];
$stmt = $conn->prepare("(
  SELECT 'student_signup' as type, userID as id, CONCAT(COALESCE(fname,''),' ',COALESCE(lname,'')) as name, email, dateCreated as ts FROM user WHERE role='student'
) UNION ALL (
  SELECT 'teacher_created', userID, CONCAT(COALESCE(fname,''),' ',COALESCE(lname,'')), email, dateCreated FROM user WHERE role='teacher'
) UNION ALL (
  SELECT 'program_event', programID, title, NULL, dateUpdated FROM programs
) ORDER BY ts DESC LIMIT 10");
$stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){$activity[]=$row;}
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container"><div class="page-content">
  <section class="content-section">
    <h1 class="section-title">Admin Dashboard</h1>
    <div class="w-full flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <label class="text-sm">Range:</label>
        <select id="rangeSelect" class="border rounded px-2 py-1">
          <option value="7d" <?= $range==='7d'?'selected':'' ?>>Last 7 days</option>
          <option value="30d" <?= $range==='30d'?'selected':'' ?>>Last 30 days</option>
        </select>
      </div>
      <button onclick="downloadAnalytics()" class="group btn-secondary"><i class="ph ph-download text-[20px] group-hover:hidden"></i><i class="ph-duotone ph-download text-[20px] hidden group-hover:block"></i><p class="font-medium">Download Summary</p></button>
    </div>

    <div class="w-full grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-users text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['students'] ?></span></div><p>Active Students</p></div>
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-chalkboard-simple text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['teachers'] ?></span></div><p>Active Teachers</p></div>
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-books text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['programs'] ?></span></div><p>Total Programs</p></div>
      <div class="p-4 rounded bg-company_green text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-seal-check text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['published'] ?></span></div><p>Published</p></div>
      <div class="p-4 rounded bg-company_orange text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-traffic-cone text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['pending'] ?></span></div><p>Draft/Pending</p></div>
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-trend-up text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['signups7'] ?></span></div><p>New Signups</p></div>
    </div>

    <div class="section-card">
      <div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold">Recent Activity</h2></div>
      <div class="w-full overflow-x-auto">
        <table class="w-full bg-white border border-gray-200 rounded-lg">
          <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Type</th><th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Name/Title</th><th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Email</th><th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Timestamp</th></tr></thead>
          <tbody>
          <?php if (empty($activity)) : ?>
            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No recent activity</td></tr>
          <?php else: foreach ($activity as $row): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($row['type']) ?></td>
              <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($row['name']) ?></td>
              <td class="px-4 py-2 text-sm text-gray-500"><?= htmlspecialchars($row['email'] ?? '') ?></td>
              <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars((new DateTime($row['ts']))->format('Y-m-d H:i')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div></div>
<script>
document.getElementById('rangeSelect').addEventListener('change', function(){ const v=this.value; const url=new URL(window.location.href); url.searchParams.set('range', v); window.location.href=url.toString(); });

function downloadAnalytics(){
  const data = {
    students: <?= (int)$metrics['students'] ?>,
    teachers: <?= (int)$metrics['teachers'] ?>,
    programs: <?= (int)$metrics['programs'] ?>,
    published: <?= (int)$metrics['published'] ?>,
    pending: <?= (int)$metrics['pending'] ?>,
    signups: <?= (int)$metrics['signups7'] ?>,
    range: '<?= htmlspecialchars($range) ?>'
  };
  const headers=['Metric','Value'];
  const rows=[['Students',data.students],['Teachers',data.teachers],['Programs',data.programs],['Published',data.published],['Draft/Pending',data.pending],['New Signups ('+data.range+')',data.signups]];
  const csv=[headers.join(','), ...rows.map(r=>r.join(','))].join('\n');
  const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download=`analytics_${new Date().toISOString().split('T')[0]}.csv`; document.body.appendChild(link); link.click(); document.body.removeChild(link);
}
</script>
</body>
</html>