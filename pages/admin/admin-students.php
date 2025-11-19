<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/config.php';

// Check admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$current_page = "admin-students";
$page_title = "Student Management";

// AJAX endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'get_students') {
        $search = trim($_POST['search'] ?? '');
        $sortBy = $_POST['sortBy'] ?? 'recent';
        $filterDate = $_POST['filterDate'] ?? '';
        $showArchived = intval($_POST['showArchived'] ?? 0);

        $sql = "SELECT u.userID, u.fname, u.lname, u.email, u.dateCreated, u.lastLogin, u.isActive,
                       (SELECT COUNT(*) FROM student_program_enrollments e WHERE e.student_id = u.userID) AS enrolled_count
                FROM user u WHERE u.role = 'student'";
        $params = []; $types = '';
        $sql .= $showArchived ? " AND u.isActive = 0" : " AND u.isActive = 1";
        if (!empty($search)) {
            $sql .= " AND (u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ? OR CONCAT(u.fname,' ',u.lname) LIKE ?)";
            $s = "%{$search}%"; $params = array_merge($params, [$s,$s,$s,$s]); $types .= 'ssss';
        }
        if (!empty($filterDate)) { $sql .= " AND DATE(u.dateCreated) = ?"; $params[] = $filterDate; $types .= 's'; }
        switch ($sortBy) {
            case 'alphabetical_asc': $sql .= " ORDER BY u.fname ASC, u.lname ASC"; break;
            case 'alphabetical_desc': $sql .= " ORDER BY u.fname DESC, u.lname DESC"; break;
            case 'oldest': $sql .= " ORDER BY u.dateCreated ASC"; break;
            default: $sql .= " ORDER BY u.dateCreated DESC"; break;
        }
        $stmt = $conn->prepare($sql); if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute(); $res = $stmt->get_result(); $rows = []; while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        echo json_encode(['success'=>true,'students'=>$rows]); exit;
    }

    if ($_POST['action'] === 'archive_student') {
        $userID = intval($_POST['userID'] ?? 0); $archive = intval($_POST['archive'] ?? 1);
        if (!$userID) { echo json_encode(['success'=>false,'message'=>'Invalid student ID']); exit; }
        $newStatus = $archive ? 0 : 1;
        $stmt = $conn->prepare("UPDATE user SET isActive = ? WHERE userID = ? AND role = 'student'");
        $stmt->bind_param('ii', $newStatus, $userID); $stmt->execute();
        echo json_encode(['success'=> $stmt->affected_rows>0, 'message'=> $stmt->affected_rows>0 ? 'Student updated' : 'No changes']); exit;
    }
}

// Stats
$stats = ['active'=>0,'archived'=>0,'enrollments'=>0];
$q1 = $conn->query("SELECT COUNT(*) c FROM user WHERE role='student' AND isActive=1"); if ($q1) $stats['active'] = $q1->fetch_assoc()['c'];
$q2 = $conn->query("SELECT COUNT(*) c FROM user WHERE role='student' AND isActive=0"); if ($q2) $stats['archived'] = $q2->fetch_assoc()['c'];
$q3 = $conn->query("SELECT COUNT(*) c FROM student_program_enrollments"); if ($q3) $stats['enrollments'] = $q3->fetch_assoc()['c'];
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container">
  <div class="page-content">
    <section class="content-section">
      <h1 class="section-title">Students Management</h1>
      <div class="w-full flex gap-[10px] mb-6">
        <div class="size-fit p-[25px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
          <div class="text-tertiary flex items-center gap-[10px]"><i class="ph-duotone ph-users text-[40px]"></i><p class="sub-header"><?= sprintf('%02d',$stats['active']) ?></p></div>
          <p>Active Students</p>
        </div>
        <div class="size-fit p-[25px] rounded-[10px] text-company_white bg-company_red flex flex-col items-center justify-center">
          <div class="flex items-center gap-[10px]"><i class="ph-duotone ph-archive text-[40px]"></i><p class="sub-header"><?= sprintf('%02d',$stats['archived']) ?></p></div>
          <p>Archived Students</p>
        </div>
        <div class="size-fit p-[25px] rounded-[10px] text-company_white bg-company_green flex flex-col items-center justify-center">
          <div class="flex items-center gap-[10px]"><i class="ph-duotone ph-graduation-cap text-[40px]"></i><p class="sub-header"><?= sprintf('%02d',$stats['enrollments']) ?></p></div>
          <p>Total Enrollments</p>
        </div>
      </div>

      <div class="section-card flex-col">
        <div class="w-full flex items-center justify-between mb-6">
          <div class="flex flex-col items-start gap-[20px]">
            <div class="flex gap-[10px] items-center"><i class="ph ph-arrows-down-up text-[24px]"></i><p class="body-text2-semibold">Sort & Filter</p></div>
            <div class="flex gap-[10px] items-center flex-wrap">
              <label class="inline-flex items-center"><input type="radio" name="sort-students" value="recent" class="form-radio h-4 w-4 text-primary" checked><span class="ml-2">Recent</span></label>
              <label class="inline-flex items-center"><input type="radio" name="sort-students" value="alphabetical_asc" class="form-radio h-4 w-4 text-primary"><span class="ml-2">A-Z</span></label>
              <label class="inline-flex items-center"><input type="radio" name="sort-students" value="alphabetical_desc" class="form-radio h-4 w-4 text-primary"><span class="ml-2">Z-A</span></label>
              <label class="inline-flex items-center"><input type="radio" name="sort-students" value="oldest" class="form-radio h-4 w-4 text-primary"><span class="ml-2">Oldest</span></label>
              <label class="inline-flex items-center ml-4"><input type="checkbox" id="showArchived" class="form-checkbox h-4 w-4 text-primary"><span class="ml-2 text-red-600">Show Archived</span></label>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2">
            <button type="button" onclick="downloadStudentRecords()" class="group btn-secondary"><i class="ph ph-download text-[20px] group-hover:hidden"></i><i class="ph-duotone ph-download text-[20px] hidden group-hover:block"></i><p class="font-medium">Download</p></button>
          </div>
        </div>

        <div class="w-full flex items-center gap-[10px] mb-6">
          <i class="ph ph-magnifying-glass text-[30px]"></i>
          <input type="text" id="searchStudents" placeholder="Search students by name or email..." class="w-[500px] h-[40px] border border-company_black rounded-[10px] p-[12px]">
        </div>

        <div class="w-full overflow-x-auto">
          <table class="w-full bg-white border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollments</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Join Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody id="studentsTableBody" class="bg-white divide-y divide-gray-200"></tbody>
          </table>
          <div id="noStudentsMessage" class="w-full h-[200px] flex items-center justify-center text-gray-500 hidden"><div class="text-center"><i class="ph ph-user-x text-[48px] mb-2"></i><p>No students found</p></div></div>
          <div id="loadingStudents" class="w-full h-[200px] flex items-center justify-center"><div class="text-center text-gray-500"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div><p>Loading students...</p></div></div>
        </div>
      </div>
    </section>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentStudents = [];

document.addEventListener('DOMContentLoaded', () => {
  loadStudents();
  document.getElementById('searchStudents').addEventListener('input', debounce(loadStudents,300));
  document.querySelectorAll('input[name="sort-students"]').forEach(r=>r.addEventListener('change', loadStudents));
  document.getElementById('showArchived').addEventListener('change', loadStudents);
  document.getElementById('filterDate').addEventListener('change', loadStudents);
});

function debounce(fn, wait){let t;return (...args)=>{clearTimeout(t);t=setTimeout(()=>fn(...args),wait)}}

function loadStudents(){
  const search=document.getElementById('searchStudents').value;
  const sortBy=document.querySelector('input[name="sort-students"]:checked').value;
  const showArchived=document.getElementById('showArchived').checked?1:0;
  const filterDate=document.getElementById('filterDate').value;
  document.getElementById('loadingStudents').style.display='flex';
  document.getElementById('noStudentsMessage').style.display='none';
  const fd=new FormData(); fd.append('action','get_students'); fd.append('search',search); fd.append('sortBy',sortBy); fd.append('showArchived',showArchived); fd.append('filterDate',filterDate);
  fetch('admin-students.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
    document.getElementById('loadingStudents').style.display='none';
    if(data.success){currentStudents=data.students; displayStudents(data.students);} else {document.getElementById('noStudentsMessage').style.display='flex';}
  }).catch(err=>{console.error(err);document.getElementById('loadingStudents').style.display='none';document.getElementById('noStudentsMessage').style.display='flex';});
}

function displayStudents(rows){
  const tbody=document.getElementById('studentsTableBody'); const noMsg=document.getElementById('noStudentsMessage');
  if(rows.length===0){tbody.innerHTML=''; noMsg.style.display='flex'; return;} noMsg.style.display='none';
  tbody.innerHTML=rows.map(s=>{
    const fullName=`${s.fname||''} ${s.lname||''}`.trim()||'Unnamed';
    const joinDate=new Date(s.dateCreated).toLocaleDateString();
    const lastLogin=s.lastLogin?new Date(s.lastLogin).toLocaleDateString():'Never';
    const isActive=parseInt(s.isActive);
    const badge=isActive?'<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>':'<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Archived</span>';
    const actionBtn=isActive?`<button onclick="archiveStudent(${s.userID}, '${fullName.replace(/'/g,"\'")}',1)" class="text-red-600 hover:text-red-800" title="Archive"><i class="ph ph-archive text-[18px]"></i></button>`:`<button onclick="archiveStudent(${s.userID}, '${fullName.replace(/'/g,"\'")}',0)" class="text-green-600 hover:text-green-800" title="Restore"><i class="ph ph-arrow-counter-clockwise text-[18px]"></i></button>`;
    const initials=`${(s.fname||'').charAt(0)}${(s.lname||'').charAt(0)}`;
    return `<tr class="hover:bg-gray-50">
      <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center font-medium">${initials}</div><div class="ml-4"><div class="text-sm font-medium text-gray-900">${fullName}</div></div></div></td>
      <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">${s.email}</div></td>
      <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">${s.enrolled_count||0}</div><div class="text-xs text-gray-500">${(s.enrolled_count||0)==1?'enrollment':'enrollments'}</div></td>
      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${joinDate}</td>
      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${lastLogin}</td>
      <td class="px-6 py-4 whitespace-nowrap">${badge}</td>
      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><div class="flex items-center space-x-3">${actionBtn}</div></td>
    </tr>`;
  }).join('');
}

function archiveStudent(userID, name, archive){
  const text=archive?'archive':'restore'; const color=archive?'#ef4444':'#10b981';
  Swal.fire({title:`${archive?'Archive':'Restore'} Student?`, text:`Are you sure you want to ${text} ${name}?`, icon:'warning', showCancelButton:true, confirmButtonColor:color, cancelButtonColor:'#6b7280', confirmButtonText:`Yes, ${text}`}).then(res=>{
    if(res.isConfirmed){ const fd=new FormData(); fd.append('action','archive_student'); fd.append('userID',userID); fd.append('archive',archive);
      fetch('admin-students.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){Swal.fire({icon:'success',title:'Success',text:data.message,timer:2000,showConfirmButton:false}); loadStudents();}
        else{Swal.fire({icon:'error',title:'Error',text:data.message});}
      }).catch(()=>Swal.fire({icon:'error',title:'Network Error',text:'Please try again.'})); }
  });
}

function downloadStudentRecords(){
  const rows=currentStudents; if(rows.length===0){Swal.fire({icon:'warning',title:'No Data',text:'No student records to download.'}); return;}
  const headers=['Name','Email','Enrollments','Join Date','Last Login','Status'];
  const csv=[headers.join(','), ...rows.map(s=>{
    const fullName=`"${(`${s.fname||''} ${s.lname||''}`).trim()}"`; const email=`"${s.email}"`; const enroll=s.enrolled_count||0; const join=new Date(s.dateCreated).toLocaleDateString(); const last=s.lastLogin?new Date(s.lastLogin).toLocaleDateString():'Never'; const status=s.isActive?'Active':'Archived'; return [fullName,email,enroll,join,last,status].join(',');
  })].join('\n');
  const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}); const link=document.createElement('a'); const url=URL.createObjectURL(blob); link.href=url; link.download=`student_records_${new Date().toISOString().split('T')[0]}.csv`; document.body.appendChild(link); link.click(); document.body.removeChild(link);
  Swal.fire({icon:'success',title:'Downloaded!',text:'Student records have been downloaded.',timer:2000,showConfirmButton:false});
}
</script>
<?php include '../../components/footer.php'; ?>
</body>
</html>