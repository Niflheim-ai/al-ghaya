<?php
    require '../../php/dbConnection.php';
    require '../../php/functions.php';
    $current_page = "admin-dashboard";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_email'])) {
        $teacherEmail = $_POST['teacher_email'];
        $fname = $_POST['fname'] ?? null;
        $lname = $_POST['lname'] ?? null;
        $adminID = 1; // example admin ID

        $result = createTeacherAccount($teacherEmail, $fname, $lname, $adminID);

        if ($result === true) {
            echo json_encode(["status" => "success", "message" => "Teacher account created and email sent successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => $result]);
        }
        exit; // stop normal page rendering
    }

    // Fetch all teachers
    $resultTable = $conn->query("SELECT teacherID, email, username, fname, lname, dateCreated FROM teacher ORDER BY teacherID ASC");

    $result = $conn->query("SELECT COUNT(*) AS total FROM teacher");
    $row = $result->fetch_assoc();
    $totalTeachers = $row['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Swiper JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <!-- Tailwind -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- DataTable -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css"/>
    <!-- CSS -->
    <link rel="stylesheet" href="../dist/css/index.css">
    <link rel="icon" type="image/x-icon" href="../images/Al-ghaya_logoForPrint.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Ghaya - Admin Dashboard</title>
</head>
<body>
    <?php include '../../components/admin-nav.php'; ?>

    <div class="container flex mx-auto mt-5">
        <form id="teacherForm" class="form" action="admin-dashboard.php" method="POST">
            <div class="border p-4 border-lg border-neutral-800">
                <h2 class="text-base/7 font-semibold text-black">Register Teacher Account</h2>
                <label for="teacher_email">Teacher Email:</label><br>
                <input class="border border-sm border-black" type="email" id="teacher_email" name="teacher_email" required><br><br>
                <button class="button text-center bg-green-300 p-2 border border-black border-lg hover:cursor-pointer hover:bg-green-500" type="submit">Create Account</button>
            </div>
        </form>
    </div>

    <!-- Total Teacher Card -->
    <div class="max-w-sm mx-auto mt-6">
        <div class="bg-white shadow-lg rounded-lg p-6 flex items-center justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium uppercase">Total Teachers</h3>
                <p class="text-3xl font-bold text-gray-900"><?= $totalTeachers ?></p>
            </div>
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                <!-- Icon (optional) -->
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H53.39a8,8,0,0,0,7.23-4.57,48,48,0,0,1,86.76,0,8,8,0,0,0,7.23,4.57H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM80,144a24,24,0,1,1,24,24A24,24,0,0,1,80,144Zm136,56H159.43a64.39,64.39,0,0,0-28.83-26.16,40,40,0,1,0-53.2,0A64.39,64.39,0,0,0,48.57,200H40V56H216ZM56,96V80a8,8,0,0,1,8-8H192a8,8,0,0,1,8,8v96a8,8,0,0,1-8,8H176a8,8,0,0,1,0-16h8V88H72v8a8,8,0,0,1-16,0Z"></path></svg>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Teacher Table -->
    <div class="container mx-auto mt-5 w-full border border-black p-4 rounded-md">
        <h2 class="title mb-2">Teacher Table</h2>
        <div class="overflow-x-auto">
            <div class="overflow-y-auto max-h-[400px]"> <!-- adjust max height as needed -->
                <table id="teacherTable" class="display min-w-full">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2">ID</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Username</th>
                            <th class="px-4 py-2">First Name</th>
                            <th class="px-4 py-2">Last Name</th>
                            <th class="px-4 py-2">Date Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultTable->num_rows > 0): ?>
                            <?php while($row = $resultTable->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-100">
                                    <td class="px-4 py-2"><?= $row['teacherID'] ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['username']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['fname']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['lname']) ?></td>
                                    <td class="px-4 py-2"><?= $row['dateCreated'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-2 text-center text-gray-500">No teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Back to Top button -->
    <button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer" id="scroll-to-top">
        <img src= "https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png" class="w-10 h-10 rounded-full bg-white" alt="">
    </button>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- JS -->
    <script src="../components/navbar.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="../dist/javascript/scroll-to-top.js"></script>
    <script src="../dist/javascript/carousel.js"></script>
    <script src="../dist/javascript/user-dropdown.js"></script>
    <script src="../dist/javascript/translate.js"></script>
    
    <script>
        let table = new DataTable("#teacherTable");
    </script>

    <script>
        document.getElementById('teacherForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);

            Swal.fire({
                title: 'Create Teacher Account?',
                text: "Are you sure you want to create this account?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, create it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('admin-dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.status,
                            title: data.status === 'success' ? 'Success' : 'Error',
                            text: data.message
                        });
                        if (data.status === 'success') {
                            form.reset();
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong while contacting the server.'
                        });
                    });
                }
            });
        });
    </script>

</body>
</html>