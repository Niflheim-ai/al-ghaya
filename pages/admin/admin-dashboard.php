<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/config.php';
require_once '../../php/program-core.php';

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
  'pending'   => scalar($conn, "SELECT COUNT(*) c FROM programs WHERE status='pending_review'"),
  'drafts'    => scalar($conn, "SELECT COUNT(*) c FROM programs WHERE status='draft' OR status IS NULL"),
  'signups7'  => scalar($conn, "SELECT COUNT(*) c FROM user WHERE dateCreated >= ?", 's', [$fromStr])
];

// Pending programs for admin review
$pendingPrograms = [];
$stmt = $conn->prepare("
  SELECT p.programID, p.title, p.description, p.price, p.category, p.dateUpdated,
         CONCAT(COALESCE(u.fname,''), ' ', COALESCE(u.lname,'')) as teacher_name,
         u.email as teacher_email
  FROM programs p 
  JOIN teacher t ON p.teacherID = t.teacherID 
  JOIN user u ON t.userID = u.userID 
  WHERE p.status = 'pending_review' 
  ORDER BY p.dateUpdated DESC 
  LIMIT 20
");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $pendingPrograms[] = $row;
    }
    $stmt->close();
}

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

    <div class="w-full grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-3 mb-6">
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-users text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['students'] ?></span></div><p>Active Students</p></div>
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-chalkboard-simple text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['teachers'] ?></span></div><p>Active Teachers</p></div>
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-books text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['programs'] ?></span></div><p>Total Programs</p></div>
      <div class="p-4 rounded bg-company_green text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-seal-check text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['published'] ?></span></div><p>Published</p></div>
      <div class="p-4 rounded bg-company_orange text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-clock text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['pending'] ?></span></div><p>Pending Review</p></div>
      <div class="p-4 rounded bg-gray-400 text-white text-center"><div class="flex items-center justify-center gap-2"><i class="ph-duotone ph-note text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['drafts'] ?></span></div><p>Drafts</p></div>
      <div class="p-4 rounded bg-company_white text-center"><div class="text-tertiary flex items-center justify-center gap-2"><i class="ph-duotone ph-trend-up text-[28px]"></i><span class="text-2xl font-semibold"><?= $metrics['signups7'] ?></span></div><p>New Signups</p></div>
    </div>

    <!-- Pending Programs Review Section -->
    <?php if (!empty($pendingPrograms)): ?>
    <div class="section-card mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold flex items-center gap-2">
          <i class="ph ph-clock text-company_orange"></i>
          Programs Pending Review
          <span class="text-sm bg-orange-100 text-orange-800 px-2 py-1 rounded-full"><?= count($pendingPrograms) ?></span>
        </h2>
        <button onclick="bulkApproveAll()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm">
          <i class="ph ph-check-circle mr-1"></i>Approve All
        </button>
      </div>
      <div class="space-y-3 max-h-96 overflow-y-auto">
        <?php foreach ($pendingPrograms as $program): ?>
          <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow" id="program-<?= $program['programID'] ?>">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($program['title']) ?></h3>
                  <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">₱<?= number_format($program['price'], 2) ?></span>
                  <span class="text-sm bg-purple-100 text-purple-800 px-2 py-1 rounded-full"><?= htmlspecialchars($program['category']) ?></span>
                </div>
                <p class="text-sm text-gray-600 mb-2 line-clamp-2"><?= htmlspecialchars($program['description']) ?></p>
                <div class="text-xs text-gray-500">
                  <span class="font-medium">Teacher:</span> <?= htmlspecialchars($program['teacher_name']) ?> (<?= htmlspecialchars($program['teacher_email']) ?>)
                  <span class="ml-4">Submitted:</span> <?= (new DateTime($program['dateUpdated']))->format('M j, Y g:i A') ?>
                </div>
              </div>
              <div class="flex items-center gap-2 ml-4">
                <button onclick="viewProgram(<?= $program['programID'] ?>)" class="text-blue-600 hover:text-blue-800 px-3 py-1 text-sm border border-blue-200 rounded hover:bg-blue-50">
                  <i class="ph ph-eye mr-1"></i>View
                </button>
                <button onclick="approveProgram(<?= $program['programID'] ?>)" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 text-sm rounded">
                  <i class="ph ph-check mr-1"></i>Approve
                </button>
                <button onclick="rejectProgram(<?= $program['programID'] ?>)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 text-sm rounded">
                  <i class="ph ph-x mr-1"></i>Reject
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('rangeSelect').addEventListener('change', function(){ const v=this.value; const url=new URL(window.location.href); url.searchParams.set('range', v); window.location.href=url.toString(); });

function downloadAnalytics(){
  const data = {
    students: <?= (int)$metrics['students'] ?>,
    teachers: <?= (int)$metrics['teachers'] ?>,
    programs: <?= (int)$metrics['programs'] ?>,
    published: <?= (int)$metrics['published'] ?>,
    pending: <?= (int)$metrics['pending'] ?>,
    drafts: <?= (int)$metrics['drafts'] ?>,
    signups: <?= (int)$metrics['signups7'] ?>,
    range: '<?= htmlspecialchars($range) ?>'
  };
  const headers=['Metric','Value'];
  const rows=[['Students',data.students],['Teachers',data.teachers],['Programs',data.programs],['Published',data.published],['Pending Review',data.pending],['Drafts',data.drafts],['New Signups ('+data.range+')',data.signups]];
  const csv=[headers.join(','), ...rows.map(r=>r.join(','))].join('\n');
  const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download=`analytics_${new Date().toISOString().split('T')[0]}.csv`; document.body.appendChild(link); link.click(); document.body.removeChild(link);
}

function viewProgram(programId) {
    // Open program details in new tab or modal
    window.open(`../teacher/teacher-programs.php?action=view&program_id=${programId}`, '_blank');
}

function approveProgram(programId) {
    Swal.fire({
        title: 'Approve Program?',
        text: 'This will publish the program and make it available to students.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Approving...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            const fd = new FormData();
            fd.append('action', 'approve_program');
            fd.append('programID', programId);
            
            fetch('../../php/program-core.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Approved!',
                        text: 'Program has been published successfully.',
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        document.getElementById(`program-${programId}`).remove();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to approve program.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'Network error. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#3b82f6'
                });
            });
        }
    });
}

function rejectProgram(programId) {
    Swal.fire({
        title: 'Reject Program?',
        text: 'This will send the program back to draft status.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, reject it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Rejecting...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            const fd = new FormData();
            fd.append('action', 'reject_program');
            fd.append('programID', programId);
            
            fetch('../../php/program-core.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Rejected',
                        text: 'Program has been sent back to draft status.',
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        document.getElementById(`program-${programId}`).remove();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to reject program.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'Network error. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#3b82f6'
                });
            });
        }
    });
}

function bulkApproveAll() {
    const pendingCount = <?= count($pendingPrograms) ?>;
    if (pendingCount === 0) {
        Swal.fire({
            title: 'No Programs',
            text: 'No pending programs to approve.',
            icon: 'info'
        });
        return;
    }
    
    Swal.fire({
        title: `Approve All ${pendingCount} Programs?`,
        text: 'This will publish all pending programs and make them available to students.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, approve all!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Approving all programs...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            const programIds = <?= json_encode(array_column($pendingPrograms, 'programID')) ?>;
            const fd = new FormData();
            fd.append('action', 'bulk_approve_programs');
            fd.append('program_ids', JSON.stringify(programIds));
            
            fetch('../../php/program-core.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'All Approved!',
                        text: `${data.approved || pendingCount} programs have been published successfully.`,
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to approve all programs.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'Network error. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#3b82f6'
                });
            });
        }
    });
}
</script>
</body>
</html>