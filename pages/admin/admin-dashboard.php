<?php
  session_start();
  include('../../php/dbConnection.php');
  require_once '../../php/config.php';
  require_once '../../php/program-core.php';

  if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

  $current_page = "admin-dashboard";
  $page_title = "Admin Dashboard";

  // Range
  $range = $_GET['range'] ?? '7d';
  $now = new DateTime('now');
  $from = clone $now;
  if ($range === '30d') $from->modify('-30 days'); else $from->modify('-7 days');
  $fromStr = $from->format('Y-m-d 00:00:00');

  // Metrics
  function scalar($conn,$sql,$types='',$params=[]) { $stmt=$conn->prepare($sql); if($types){$stmt->bind_param($types,...$params);} $stmt->execute(); $res=$stmt->get_result(); $row=$res->fetch_assoc(); return array_values($row)[0] ?? 0;}
  $metrics = [
    'students'=>scalar($conn, "SELECT COUNT(*) FROM user WHERE role='student' AND isActive=1"),
    'teachers'=>scalar($conn, "SELECT COUNT(*) FROM user WHERE role='teacher' AND isActive=1"),
    'programs'=>scalar($conn, "SELECT COUNT(*) FROM programs"),
    'published'=>scalar($conn, "SELECT COUNT(*) FROM programs WHERE status='published'"),
    'pending'=>scalar($conn, "SELECT COUNT(*) FROM programs WHERE status='pending_review'"),
    'drafts'=>scalar($conn, "SELECT COUNT(*) FROM programs WHERE status='draft' OR status IS NULL"),
    'signups'=>scalar($conn, "SELECT COUNT(*) FROM user WHERE role='student' AND dateCreated >= ?", 's', [$fromStr]),
    'revenue'=>scalar($conn,"SELECT IFNULL(SUM(amount),0) FROM payment_transactions WHERE status='paid' AND dateCreated >= ?",'s',[$fromStr]),
    'enrollments'=>scalar($conn, "SELECT COUNT(*) FROM student_program_enrollments WHERE enrollment_date >= ?", 's', [$fromStr])
  ];

  // Chart: Signups per day
  $signupsData = [];
  $sQuery = $conn->prepare("SELECT DATE(dateCreated) as d, COUNT(*) as cnt FROM user WHERE role='student' AND dateCreated >= ? GROUP BY d ORDER BY d");
  $sQuery->bind_param("s", $fromStr); $sQuery->execute(); $r=$sQuery->get_result();
  while($row=$r->fetch_assoc()) $signupsData[$row['d']]=(int)$row['cnt'];
  $sQuery->close();

  // Chart: Enrollments per day
  $enrollData = [];
  $eQuery = $conn->prepare("SELECT DATE(enrollment_date) as d, COUNT(*) as cnt FROM student_program_enrollments WHERE enrollment_date >= ? GROUP BY d ORDER BY d");
  $eQuery->bind_param("s",$fromStr); $eQuery->execute(); $r=$eQuery->get_result();
  while($row=$r->fetch_assoc()) $enrollData[$row['d']] = (int)$row['cnt'];
  $eQuery->close();

  // Chart: Revenue per day
  $revenueData = [];
  $rQuery = $conn->prepare("SELECT DATE(dateCreated) as d, SUM(amount) as sum FROM payment_transactions WHERE status='paid' AND dateCreated >= ? GROUP BY d ORDER BY d");
  $rQuery->bind_param("s",$fromStr); $rQuery->execute(); $r=$rQuery->get_result();
  while($row=$r->fetch_assoc()) $revenueData[$row['d']] = floatval($row['sum']);
  $rQuery->close();

  // Recent enrollments table
  $enrollList = [];
  $result = $conn->query("SELECT e.enrollment_id, e.student_id, u.fname, u.lname, e.program_id, p.title as program_title, e.enrollment_date, t.status as payment_status FROM student_program_enrollments e LEFT JOIN user u ON e.student_id=u.userID LEFT JOIN programs p ON e.program_id=p.programID LEFT JOIN payment_transactions t ON t.program_id = e.program_id AND t.student_id = e.student_id WHERE e.enrollment_date >= '$fromStr' ORDER BY e.enrollment_date DESC LIMIT 100");
  while($row=$result->fetch_assoc()) $enrollList[] = $row;

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

  // Recent activity (last 10, filtered by range)
  $activity = [];
  $stmt = $conn->prepare("(
    SELECT 'student_signup' as type, userID as id, CONCAT(COALESCE(fname,''),' ',COALESCE(lname,'')) as name, email, dateCreated as ts
    FROM user
    WHERE role='student' AND dateCreated >= ?
  ) UNION ALL (
    SELECT 'teacher_created', userID, CONCAT(COALESCE(fname,''),' ',COALESCE(lname,'')), email, dateCreated
    FROM user
    WHERE role='teacher' AND dateCreated >= ?
  ) UNION ALL (
    SELECT 'program_event', p.programID, p.title, u.email, p.dateUpdated
    FROM programs p
    LEFT JOIN user u ON p.teacherID = u.userID
    WHERE p.dateUpdated >= ?
  )
  ORDER BY ts DESC LIMIT 30");
  $stmt->bind_param("sss", $fromStr, $fromStr, $fromStr);
  $stmt->execute();
  $res = $stmt->get_result(); while ($row = $res->fetch_assoc()) {$activity[]=$row;}
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/v/dt/dt-2.0.5/datatables.min.css"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.0.5/datatables.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css"/>
<div class="page-container"><div class="page-content">
  <section class="content-section">
    <h1 class="section-title">Admin Dashboard</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Total Revenue -->
      <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white flex flex-col">
        <div class="text-green-100 text-sm font-medium mb-1">Total Revenue</div>
        <div class="text-3xl font-bold">₱<?= number_format($metrics['revenue'],2) ?></div>
        <div class="text-green-100 text-xs mt-auto">in period</div>
      </div>
      <!-- Enrollments -->
      <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white flex flex-col">
        <div class="text-blue-100 text-sm font-medium mb-1">Enrollments</div>
        <div class="text-3xl font-bold"><?= $metrics['enrollments'] ?></div>
        <div class="text-blue-100 text-xs mt-auto">in period</div>
      </div>
      <!-- Students -->
      <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white flex flex-col">
        <div class="text-purple-100 text-sm font-medium mb-1">Students</div>
        <div class="text-3xl font-bold"><?= $metrics['students'] ?></div>
        <div class="text-purple-100 text-xs mt-auto">registered</div>
      </div>
      <!-- Programs -->
      <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white flex flex-col">
        <div class="text-orange-100 text-sm font-medium mb-1">Total Programs</div>
        <div class="text-3xl font-bold"><?= $metrics['programs'] ?></div>
        <div class="text-orange-100 text-xs mt-auto"><?= $metrics['published'] ?> published</div>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
      <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-gray-200">
        <i class="ph-duotone ph-file-dashed text-gray-500 text-3xl mb-2"></i>
        <p class="text-2xl font-bold text-gray-900"><?= $metrics['drafts'] ?></p>
        <p class="text-sm text-gray-600">Draft</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-yellow-200">
        <i class="ph-duotone ph-clock text-yellow-600 text-3xl mb-2"></i>
        <p class="text-2xl font-bold text-yellow-700"><?= $metrics['pending'] ?></p>
        <p class="text-sm text-gray-600">Pending</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-green-200">
        <i class="ph-duotone ph-seal-check text-green-600 text-3xl mb-2"></i>
        <p class="text-2xl font-bold text-green-700"><?= $metrics['published'] ?></p>
        <p class="text-sm text-gray-600">Published</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-red-200">
        <i class="ph-duotone ph-prohibit text-red-600 text-3xl mb-2"></i>
        <p class="text-2xl font-bold text-red-700"><?= $metrics['rejected'] ?? 0 ?></p>
        <p class="text-sm text-gray-600">Rejected</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-gray-200">
        <i class="ph-duotone ph-archive text-gray-500 text-3xl mb-2"></i>
        <p class="text-2xl font-bold text-gray-700"><?= $metrics['archived'] ?? 0 ?></p>
        <p class="text-sm text-gray-600">Archived</p>
      </div>
    </div>

    <div class="section-card mb-8 bg-white rounded-xl shadow-md p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold flex items-center gap-2">
          <i class="ph ph-clock text-yellow-500"></i>
          Programs Pending Review
          <span class="text-sm bg-orange-100 text-orange-800 px-2 py-1 rounded-full mr-2"><?= count($pendingPrograms) ?></span>
        </h2>
        <button 
          onclick="bulkApproveAll()" 
          class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
          <?= empty($pendingPrograms) ? 'disabled' : '' ?>>
          <i class="ph ph-check-circle mr-1"></i>
          Approve All
        </button>
      </div>

      <div class="space-y-3 max-h-96 overflow-y-auto">
        <?php if (empty($pendingPrograms)): ?>
          <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <i class="ph ph-folder-open text-5xl mb-3"></i>
            <p class="text-lg font-semibold mb-1">No pending programs for review</p>
            <p class="text-sm">All published programs are up to date.</p>
          </div>
        <?php else: ?>
          <?php foreach ($pendingPrograms as $program): ?>
            <div class="bg-gradient-to-br from-yellow-50 to-gray-50 border border-yellow-200 rounded-lg p-4 hover:shadow transition-all duration-150" id="program-<?= $program['programID'] ?>">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="font-bold text-gray-900"><?= htmlspecialchars($program['title']) ?></h3>
                    <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">₱<?= number_format($program['price'], 2) ?></span>
                    <span class="text-sm bg-purple-100 text-purple-800 px-2 py-1 rounded-full"><?= htmlspecialchars($program['category']) ?></span>
                  </div>
                  <p class="text-sm text-gray-600 mb-2 line-clamp-2"><?= htmlspecialchars($program['description']) ?></p>
                  <div class="text-xs text-gray-500 flex flex-wrap gap-2">
                    <span class="font-medium">Teacher:</span>
                    <span class="text-gray-900"><?= htmlspecialchars($program['teacher_name']) ?></span>
                    <span class="text-gray-500">(<?= htmlspecialchars($program['teacher_email']) ?>)</span>
                    <span class="ml-4">Submitted:</span>
                    <span><?= (new DateTime($program['dateUpdated']))->format('M j, Y g:i A') ?></span>
                  </div>
                </div>
                <div class="flex flex-col gap-2 ml-4 items-end">
                  <button onclick="openReviewModal(<?= $program['programID'] ?>)" class="text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-200 px-3 py-1 text-sm rounded transition">
                    <i class="ph ph-eye mr-1"></i>View
                  </button>
                  <button onclick="approveProgram(<?= $program['programID'] ?>)" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 text-sm rounded transition">
                    <i class="ph ph-check mr-1"></i>Approve
                  </button>
                  <button onclick="rejectProgram(<?= $program['programID'] ?>)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 text-sm rounded transition">
                    <i class="ph ph-x mr-1"></i>Reject
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>


    <!-- Top row: Range selector and export -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <label>Range:
          <select id="rangeSelect" class="border rounded px-2 py-1">
            <option value="7d" <?= $range==='7d'?'selected':'' ?>>Last 7 days</option>
            <option value="30d" <?= $range==='30d'?'selected':'' ?>>Last 30 days</option>
          </select>
        </label>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid md:grid-cols-3 gap-6 mb-12">
      <div class="bg-white rounded-lg p-4 shadow">
        <div class="font-semibold mb-2">New Student Signups</div>
        <canvas id="signupsChart" height="120"></canvas>
      </div>
      <div class="bg-white rounded-lg p-4 shadow">
        <div class="font-semibold mb-2">Enrollments</div>
        <canvas id="enrollChart" height="120"></canvas>
      </div>
      <div class="bg-white rounded-lg p-4 shadow">
        <div class="font-semibold mb-2">Revenue (₱)</div>
        <canvas id="revenueChart" height="120"></canvas>
      </div>
    </div>

    <!-- Tables -->
    <div class="bg-white rounded-lg p-6 mb-8 shadow">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Recent Student Enrollments</h2>
        <button onclick="tableToCSV('enrollmentsTable','enrollments.csv')" class="border px-3 py-1 rounded bg-blue-100 text-blue-800 text-sm hover:bg-blue-200">Export CSV</button>
      </div>
      <div class="overflow-x-auto">
        <table id="enrollmentsTable" class="display w-full mb-4 text-center">
          <thead>
            <tr>
              <th>Enrollment ID</th>
              <th>Student</th>
              <th>Program</th>
              <th>Payment Status</th>
              <th>Date Enrolled</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($enrollList as $row): ?>
            <tr>
              <td><?= $row['enrollment_id'] ?></td>
              <td><?= htmlspecialchars(trim($row['fname'].' '.$row['lname'])) ?></td>
              <td><?= htmlspecialchars($row['program_title']) ?></td>
              <td><?= htmlspecialchars(ucfirst($row['payment_status'])) ?></td>
              <td><?= date('M d, Y H:i', strtotime($row['enrollment_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white rounded-lg p-6 mb-8 shadow">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Recent Activity</h2>
        <button onclick="tableToCSV('activityTable','activity.csv')" class="border px-3 py-1 rounded bg-blue-100 text-blue-800 text-sm hover:bg-blue-200">Export CSV</button>
      </div>
      <div class="overflow-x-auto">
        <table id="activityTable" class="display w-full mb-4">
          <thead>
            <tr>
              <th>Type</th>
              <th>Name/Title</th>
              <th>Email</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($activity as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['type']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= date('M d, Y H:i', strtotime($row['ts'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div></div>

  <!-- CSV Export Helper -->
  <script>
    function tableToCSV(tableId, filename) {
      let csv = [];
      const rows = document.querySelectorAll(`#${tableId} tr`);
      for (const row of rows) {
        const cols = Array.from(row.querySelectorAll('th,td')).map(td=>`"${td.textContent.replace(/"/g,'""')}"`).join(",");
        csv.push(cols);
      }
      const blob = new Blob([csv.join('\n')],{type:'text/csv'});
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      link.click();
    }
    document.getElementById('rangeSelect').addEventListener('change', function(){
      const url=new URL(window.location.href);
      url.searchParams.set('range', this.value);
      window.location.href = url.toString();
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      new DataTable('#activityTable',    {paging:true, searching:true, ordering:true});
      $('#enrollmentsTable').DataTable({
        paging:true,
        searching:true,
        ordering:true,
        columnDefs: [
          { 
            targets: '_all',
            className: 'dt-center'
          }
        ]
      });


      // Chart.js Data
      new Chart(document.getElementById('signupsChart'), {
        type: 'line',
        data: {labels: <?= json_encode(array_keys($signupsData)) ?>, datasets:[{label:'Signups',data:<?= json_encode(array_values($signupsData)) ?>,borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,0.1)',fill:true}]},
        options:{responsive: true, scales:{y:{beginAtZero:true}}}
      });
      new Chart(document.getElementById('enrollChart'), {
        type: 'line',
        data: {labels: <?= json_encode(array_keys($enrollData)) ?>, datasets:[{label:'Enrollments',data:<?= json_encode(array_values($enrollData)) ?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.1)',fill:true}]},
        options:{responsive: true, scales:{y:{beginAtZero:true}}}
      });
      new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {labels: <?= json_encode(array_keys($revenueData)) ?>, datasets:[{label:'Revenue',data:<?= json_encode(array_values($revenueData)) ?>,backgroundColor:'#A58618'}]},
        options:{responsive: true, scales:{y:{beginAtZero:true}}}
      });
    });
  </script>

<!-- Program Review Modal -->
<div id="programReviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-hidden shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold" id="modalProgramTitle">Program Review</h2>
        <p class="text-blue-100 text-sm mt-1" id="modalProgramTeacher"></p>
      </div>
      <button onclick="closeReviewModal()" class="text-white hover:text-gray-200 text-3xl leading-none">&times;</button>
    </div>

    <!-- Modal Content -->
    <div class="overflow-y-auto max-h-[calc(90vh-200px)] p-6" id="modalContent">
      <div class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Loading program details...</p>
      </div>
    </div>

    <!-- Modal Footer with Actions -->
    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
      <button onclick="closeReviewModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
        Close
      </button>
      <div class="flex gap-3">
        <button onclick="rejectProgramFromModal()" id="modalRejectBtn" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg">
          <i class="ph ph-x mr-2"></i>Reject Program
        </button>
        <button onclick="approveProgramFromModal()" id="modalApproveBtn" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg">
          <i class="ph ph-check mr-2"></i>Approve Program
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let currentProgramId = null;

// Open review modal and load program details
function openReviewModal(programId) {
  currentProgramId = programId;
  document.getElementById('programReviewModal').classList.remove('hidden');
  loadProgramDetails(programId);
}

// Close modal
function closeReviewModal() {
  document.getElementById('programReviewModal').classList.add('hidden');
  currentProgramId = null;
}

function loadProgramDetails(programId) {
  const modalContent = document.getElementById('modalContent');
  
  console.log('Loading program ID:', programId);
  
  fetch(`../../php/admin-get-program-details.php?program_id=${programId}`)
    .then(response => response.json())
    .then(data => {
      console.log('Full response:', data); // ✅ Debug
      if (data.success) {
        console.log('Program chapters:', data.program.chapters); // ✅ Debug
        if (data.program.chapters && data.program.chapters.length > 0) {
          console.log('First chapter:', data.program.chapters[0]); // ✅ Debug
          console.log('Has quiz?', data.program.chapters[0].has_quiz); // ✅ Debug
          console.log('Question?', data.program.chapters[0].question); // ✅ Debug
        }
        renderProgramDetails(data.program);
      } else {
        modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Error loading program: ${data.message}</div>`;
      }
    })
    .catch(error => {
      modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load program details.</div>`;
      console.error('Error:', error);
    });
}

// Render program details in modal
function renderProgramDetails(program) {
  const modalContent = document.getElementById('modalContent');
  document.getElementById('modalProgramTitle').textContent = program.title;
  document.getElementById('modalProgramTeacher').textContent = `Teacher: ${program.teacher_name} (${program.teacher_email})`;
  
  let chaptersHtml = '';
  if (program.chapters && program.chapters.length > 0) {
    program.chapters.forEach((chapter, idx) => {
      // Stories section
      let storiesHtml = '';
      if (chapter.stories && chapter.stories.length > 0) {
        chapter.stories.forEach((story, sIdx) => {
          // Interactive sections for this story
          let interactiveSectionsHtml = '';
          if (story.interactive_sections && story.interactive_sections.length > 0) {
            interactiveSectionsHtml = `
              <div class="mt-4 space-y-3">
                <p class="text-xs font-semibold text-purple-800 mb-2">
                  <i class="ph ph-magic-wand text-purple-600 mr-1"></i>
                  Interactive Sections (${story.interactive_sections.length})
                </p>
                ${story.interactive_sections.map((section, secIdx) => {
                  // Render questions for this section
                  let questionsHtml = '';
                  if (section.questions && section.questions.length > 0) {
                    questionsHtml = section.questions.map((question, qIdx) => {
                      // Render options
                      let optionsHtml = question.options.map(opt => {
                        const isCorrect = opt.is_correct == 1;
                        return `
                          <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                            ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-1"></i>' : ''}
                            ${escapeHtml(opt.option_text)}
                            ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct)</span>' : ''}
                          </div>
                        `;
                      }).join('');
                      
                      return `
                        <div class="mb-3">
                          <p class="font-medium text-gray-800 mb-2">${qIdx + 1}. ${escapeHtml(question.question_text)}</p>
                          <p class="text-xs text-gray-500 mb-2">Type: ${question.question_type}</p>
                          <div class="space-y-1 ml-3">
                            ${optionsHtml}
                          </div>
                        </div>
                      `;
                    }).join('');
                  }
                  
                  return `
                    <div class="p-3 bg-purple-50 border-l-4 border-purple-400 rounded">
                      <p class="text-xs font-semibold text-purple-900 mb-3">Section ${secIdx + 1}</p>
                      ${questionsHtml || '<p class="text-xs text-gray-500 italic">No questions in this section</p>'}
                    </div>
                  `;
                }).join('')}
              </div>
            `;
          }
          
          storiesHtml += `
            <div class="ml-6 mb-4 p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
              <h5 class="font-bold text-blue-900 mb-2 flex items-center gap-2">
                <i class="ph ph-book-open text-blue-600"></i>
                Story ${sIdx + 1}: ${escapeHtml(story.title)}
              </h5>
              
              <!-- Arabic Synopsis -->
              <div class="mb-3 p-3 bg-amber-50 border-l-4 border-amber-400 rounded">
                <p class="text-xs font-semibold text-amber-800 mb-1">Arabic Synopsis:</p>
                <p class="text-sm text-gray-800 arabic leading-relaxed">${escapeHtml(story.synopsis_arabic)}</p>
              </div>
              
              <!-- English Synopsis -->
              <div class="mb-3 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                <p class="text-xs font-semibold text-blue-800 mb-1">English Synopsis:</p>
                <p class="text-sm text-gray-800 leading-relaxed">${escapeHtml(story.synopsis_english)}</p>
              </div>
              
              <!-- Video Player -->
              ${story.video_url_embed ? `
                <div class="mt-3">
                  <p class="text-xs font-semibold text-gray-700 mb-2">Video Content:</p>
                  <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                    <iframe 
                      src="${escapeHtml(story.video_url_embed)}" 
                      class="absolute top-0 left-0 w-full h-full rounded-lg"
                      frameborder="0" 
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                      allowfullscreen>
                    </iframe>
                  </div>
                </div>
              ` : (story.video_url ? `<p class="text-xs text-gray-500 italic">Video URL: ${escapeHtml(story.video_url)}</p>` : '<p class="text-xs text-gray-500 italic">No video for this story</p>')}
              
              <!-- Interactive Sections -->
              ${interactiveSectionsHtml}
            </div>
          `;
        });
      } else {
        storiesHtml = '<p class="ml-6 text-sm text-gray-500 italic">No stories in this chapter</p>';
      }
      
      // Chapter Interactive Question (if exists)
      let chapterQuestionHtml = '';
      if (chapter.question && chapter.question_type) {
        let optionsHtml = '';
        if (chapter.question_type === 'multiple_choice' && chapter.answer_options_parsed) {
          optionsHtml = chapter.answer_options_parsed.map(option => {
            const isCorrect = option === chapter.correct_answer;
            return `
              <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                ${escapeHtml(option)}
                ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
              </div>
            `;
          }).join('');
        } else if (chapter.question_type === 'true_false') {
          optionsHtml = `
            <div class="p-2 rounded border ${chapter.correct_answer === 'True' ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
              ${chapter.correct_answer === 'True' ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
              True
              ${chapter.correct_answer === 'True' ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
            </div>
            <div class="p-2 rounded border ${chapter.correct_answer === 'False' ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
              ${chapter.correct_answer === 'False' ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
              False
              ${chapter.correct_answer === 'False' ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
            </div>
          `;
        } else {
          optionsHtml = `<div class="p-2 bg-green-100 border border-green-500 rounded"><strong>Correct Answer:</strong> ${escapeHtml(chapter.correct_answer)}</div>`;
        }
        
        chapterQuestionHtml = `
          <div class="ml-6 mt-4 p-4 bg-purple-50 border-2 border-purple-400 rounded-lg">
            <h6 class="font-semibold text-purple-900 mb-2 flex items-center gap-2">
              <i class="ph ph-question text-purple-600"></i>
              Chapter Interactive Question (${chapter.points_reward} points)
            </h6>
            <p class="text-sm font-medium text-gray-800 mb-3">${escapeHtml(chapter.question)}</p>
            <div class="space-y-2">
              ${optionsHtml}
            </div>
          </div>
        `;
      }
      
      // Chapter Media
      let chapterMediaHtml = '';
      if (chapter.video_url_embed || chapter.audio_url) {
        chapterMediaHtml = '<div class="ml-6 mt-3 space-y-3">';
        if (chapter.video_url_embed) {
          chapterMediaHtml += `
            <div>
              <p class="text-xs font-semibold text-gray-700 mb-2">Chapter Video:</p>
              <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                <iframe 
                  src="${escapeHtml(chapter.video_url_embed)}" 
                  class="absolute top-0 left-0 w-full h-full rounded-lg"
                  frameborder="0" 
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                  allowfullscreen>
                </iframe>
              </div>
            </div>
          `;
        }
        if (chapter.audio_url) {
          chapterMediaHtml += `
            <div>
              <p class="text-xs font-semibold text-gray-700 mb-2">Chapter Audio:</p>
              <audio controls class="w-full">
                <source src="${escapeHtml(chapter.audio_url)}" type="audio/mpeg">
                Your browser does not support the audio element.
              </audio>
            </div>
          `;
        }
        chapterMediaHtml += '</div>';
      }
      
      // Chapter Quiz
      let quizHtml = '';
      if (chapter.has_quiz && chapter.quiz_questions && chapter.quiz_questions.length > 0) {
        let questionsHtml = chapter.quiz_questions.map((q, qIdx) => {
          let optionsListHtml = q.options.map(opt => {
            const isCorrect = opt.is_correct == 1;
            return `
              <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                ${escapeHtml(opt.option_text)}
                ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct)</span>' : ''}
              </div>
            `;
          }).join('');
          
          return `
            <div class="mb-3 p-3 bg-white border border-gray-300 rounded">
              <p class="font-medium text-gray-800 mb-2">${qIdx + 1}. ${escapeHtml(q.question_text)}</p>
              <div class="space-y-1 ml-4">
                ${optionsListHtml}
              </div>
            </div>
          `;
        }).join('');
        
        quizHtml = `
          <div class="ml-6 mt-4 p-4 bg-green-50 border-2 border-green-400 rounded-lg">
            <h6 class="font-semibold text-green-900 mb-3 flex items-center gap-2">
              <i class="ph ph-exam text-green-600"></i>
              Chapter Quiz (${chapter.quiz_questions.length} questions)
            </h6>
            ${questionsHtml}
          </div>
        `;
      }
      
      chaptersHtml += `
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xl font-bold text-gray-900">
              <i class="ph ph-book-bookmark text-blue-600 mr-2"></i>
              Chapter ${chapter.chapter_order}: ${escapeHtml(chapter.title)}
            </h4>
            <div class="flex gap-2 text-xs">
              <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded font-semibold">${chapter.story_count} stories</span>
              ${chapter.has_quiz ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">Has Quiz</span>` : ''}
            </div>
          </div>
          ${chapter.content ? `<div class="mb-4 p-3 bg-white border-l-4 border-blue-500 rounded"><p class="text-sm text-gray-700">${escapeHtml(chapter.content)}</p></div>` : ''}
          ${chapterMediaHtml}
          ${storiesHtml}
          ${chapterQuestionHtml}
          ${quizHtml}
        </div>
      `;
    });
  } else {
    chaptersHtml = '<p class="text-gray-500 italic">No chapters found in this program</p>';
  }
  
  modalContent.innerHTML = `
    <div class="space-y-6">
      <!-- Program Overview -->
      <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-300 rounded-lg p-5 shadow-sm">
        <h3 class="text-xl font-bold text-blue-900 mb-4">📋 Program Overview</h3>
        
        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
          <div class="bg-white p-2 rounded"><strong>Difficulty:</strong> <span class="capitalize">${escapeHtml(program.category)}</span></div>
          <div class="bg-white p-2 rounded"><strong>Price:</strong> ₱${parseFloat(program.price).toFixed(2)}</div>
        </div>
        <div class="bg-white p-3 rounded">
          <strong class="text-gray-900">Description:</strong>
          <p class="text-gray-700 mt-1">${escapeHtml(program.description)}</p>
        </div>
        ${program.prerequisites ? `
          <div class="bg-white p-3 rounded mt-3">
            <strong class="text-gray-900">Prerequisites:</strong>
            <p class="text-gray-700 mt-1">${escapeHtml(program.prerequisites)}</p>
          </div>
        ` : ''} 
        ${program.learning_objectives ? `
          <div class="bg-white p-3 rounded mt-3">
            <strong class="text-gray-900">Learning Objectives:</strong>
            <p class="text-gray-700 mt-1">${escapeHtml(program.learning_objectives)}</p>
          </div>
        ` : ''}
      </div>

      <!-- Overview Video -->
        ${program.overview_video_url_embed ? `
          <div class="mb-4">
            <p class="text-sm font-semibold text-blue-900 mb-2">
              <i class="ph ph-play-circle text-blue-600 mr-1"></i>
              Program Introduction Video
            </p>
            <div class="relative bg-white rounded-lg overflow-hidden" style="padding-bottom: 56.25%; height: 0;">
              <iframe 
                src="${escapeHtml(program.overview_video_url_embed)}" 
                class="absolute top-0 left-0 w-full h-full"
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                allowfullscreen>
              </iframe>
            </div>
          </div>
        ` : ''}

      <!-- Chapters and Content -->
      <div>
        <h3 class="text-xl font-bold text-gray-900 mb-4">📚 Program Content (${program.chapters ? program.chapters.length : 0} Chapters)</h3>
        ${chaptersHtml}
      </div>
    </div>
  `;
}

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text || '';
  return div.innerHTML;
}

// Approve from modal
function approveProgramFromModal() {
  if (currentProgramId) {
    approveProgram(currentProgramId);
    closeReviewModal();
  }
}

// Reject from modal
function rejectProgramFromModal() {
  if (currentProgramId) {
    rejectProgram(currentProgramId);
    closeReviewModal();
  }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeReviewModal();
  }
});
</script>

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