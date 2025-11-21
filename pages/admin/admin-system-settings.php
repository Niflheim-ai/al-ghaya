<?php
session_start();
require_once '../../php/dbConnection.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

$adminId = $_SESSION['userID'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_certificate') {
        $stmt = $conn->prepare("
            UPDATE certificate_settings SET
                header_title = ?,
                intro_text = ?,
                completion_text = ?,
                show_grade = ?,
                grade_text = ?,
                signature_name = ?,
                signature_title = ?,
                show_certificate_code = ?,
                certificate_code_label = ?,
                date_label = ?,
                primary_color = ?,
                font_family = ?,
                updated_by = ?
            WHERE id = 1
        ");
        
        $show_grade = isset($_POST['show_grade']) ? 1 : 0;
        $show_cert_code = isset($_POST['show_certificate_code']) ? 1 : 0;
        
        $stmt->bind_param(
            "sssississsssi",
            $_POST['header_title'],
            $_POST['intro_text'],
            $_POST['completion_text'],
            $show_grade,
            $_POST['grade_text'],
            $_POST['signature_name'],
            $_POST['signature_title'],
            $show_cert_code,
            $_POST['certificate_code_label'],
            $_POST['date_label'],
            $_POST['primary_color'],
            $_POST['font_family'],
            $adminId
        );
        
        if ($stmt->execute()) {
            $success_message = "Certificate settings updated successfully!";
        } else {
            $error_message = "Failed to update certificate settings.";
        }
        $stmt->close();
    }
    
    // Handle image uploads
    if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../certificate/backgrounds/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $extension = pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION);
        $filename = 'CertificateBG_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['background_image']['tmp_name'], $uploadPath)) {
            $conn->query("UPDATE certificate_settings SET background_image = '$filename' WHERE id = 1");
            $success_message = "Background image uploaded successfully!";
        }
    }

    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../certificate/logos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $extension = pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION);
        $filename = 'Logo_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $uploadPath)) {
            $conn->query("UPDATE certificate_settings SET logo_image = '$filename' WHERE id = 1");
            $success_message = "Logo image uploaded successfully!";
        }
    }
}

// Get current settings
$settings = $conn->query("SELECT * FROM certificate_settings WHERE id = 1")->fetch_assoc();
include '../../components/header.php';
include '../../components/admin-nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Settings - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .preview-certificate {
            transform: scale(0.5);
            transform-origin: top center;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Certificate Customization</h1>
                <p class="text-gray-600 mt-2">Customize the appearance and content of student certificates</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-green-800"><?= $success_message ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-800"><?= $error_message ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Settings Form -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
                        <i class="ph ph-gear text-2xl"></i>
                        Certificate Settings
                    </h2>

                    <form method="POST" enctype="multipart/form-data" id="certForm">
                        <input type="hidden" name="action" value="update_certificate">

                        <!-- Header Title -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Header Title
                            </label>
                            <input type="text" name="header_title" 
                                   value="<?= htmlspecialchars($settings['header_title']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Intro Text -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Introduction Text
                            </label>
                            <input type="text" name="intro_text" 
                                   value="<?= htmlspecialchars($settings['intro_text']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Completion Text -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Completion Text
                            </label>
                            <textarea name="completion_text" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      onchange="updatePreview()"><?= htmlspecialchars($settings['completion_text']) ?></textarea>
                        </div>

                        <!-- Show Grade -->
                        <div class="mb-6">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="show_grade" value="1" 
                                       <?= $settings['show_grade'] ? 'checked' : '' ?>
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                       onchange="updatePreview()">
                                <span class="text-sm font-medium text-gray-700">Show Grade/Score</span>
                            </label>
                        </div>

                        <!-- Grade Text -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Grade Text
                            </label>
                            <input type="text" name="grade_text" 
                                   value="<?= htmlspecialchars($settings['grade_text']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Signature Name -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Signature Name
                            </label>
                            <input type="text" name="signature_name" 
                                   value="<?= htmlspecialchars($settings['signature_name']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Signature Title -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Signature Title
                            </label>
                            <input type="text" name="signature_title" 
                                   value="<?= htmlspecialchars($settings['signature_title']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Show Certificate Code -->
                        <div class="mb-6">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="show_certificate_code" value="1" 
                                       <?= $settings['show_certificate_code'] ? 'checked' : '' ?>
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                       onchange="updatePreview()">
                                <span class="text-sm font-medium text-gray-700">Show Certificate Code</span>
                            </label>
                        </div>

                        <!-- Certificate Code Label -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Certificate Code Label
                            </label>
                            <input type="text" name="certificate_code_label" 
                                   value="<?= htmlspecialchars($settings['certificate_code_label']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Date Label -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Date Label
                            </label>
                            <input type="text" name="date_label" 
                                   value="<?= htmlspecialchars($settings['date_label']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updatePreview()">
                        </div>

                        <!-- Primary Color -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Primary Color
                            </label>
                            <div class="flex gap-2 items-center">
                                <input type="color" name="primary_color" 
                                       value="<?= $settings['primary_color'] ?>"
                                       class="h-10 w-20 rounded border border-gray-300"
                                       onchange="updatePreview()">
                                <input type="text" 
                                       value="<?= $settings['primary_color'] ?>"
                                       readonly
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>

                        <!-- Font Family -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Font Family
                            </label>
                            <select name="font_family" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    onchange="updatePreview()">
                                <option value="Winky Rough" <?= $settings['font_family'] === 'Winky Rough' ? 'selected' : '' ?>>Winky Rough</option>
                                <option value="Arial, sans-serif" <?= $settings['font_family'] === 'Arial, sans-serif' ? 'selected' : '' ?>>Arial</option>
                                <option value="Georgia, serif" <?= $settings['font_family'] === 'Georgia, serif' ? 'selected' : '' ?>>Georgia</option>
                                <option value="Times New Roman, serif" <?= $settings['font_family'] === 'Times New Roman, serif' ? 'selected' : '' ?>>Times New Roman</option>
                            </select>
                        </div>

                        <!-- Background Image Upload -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Background Image
                            </label>
                            <input type="file" name="background_image" accept="image/*"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Current: <?= $settings['background_image'] ?></p>
                        </div>

                        <!-- Logo Image Upload -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Logo Image
                            </label>
                            <input type="file" name="logo_image" accept="image/*"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Current: <?= $settings['logo_image'] ?></p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-4">
                            <button type="submit" 
                                    class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                                Save Changes
                            </button>
                            <a href="admin-dashboard.php" 
                               class="px-6 py-3 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-center">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Live Preview -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold flex items-center gap-2">
                            <i class="ph ph-eye text-2xl"></i>
                            Live Preview
                        </h2>
                        
                        <div class="flex items-center gap-4">
                            <!-- Zoom Controls -->
                            <div class="flex items-center gap-3 bg-gray-100 px-4 py-2 rounded-lg">
                                <button onclick="zoomOut()" class="p-2 hover:bg-gray-200 rounded transition" title="Zoom Out">
                                    <i class="ph ph-minus-circle text-xl"></i>
                                </button>
                                <span id="zoomLevel" class="font-semibold text-sm min-w-[50px] text-center">50%</span>
                                <button onclick="zoomIn()" class="p-2 hover:bg-gray-200 rounded transition" title="Zoom In">
                                    <i class="ph ph-plus-circle text-xl"></i>
                                </button>
                                <button onclick="resetZoom()" class="p-2 hover:bg-gray-200 rounded transition ml-2" title="Reset Zoom">
                                    <i class="ph ph-arrows-out text-xl"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Draggable Preview Container -->
                    <div id="previewContainer" 
                        class="overflow-hidden border-2 border-gray-200 rounded-lg bg-gray-50 relative cursor-grab active:cursor-grabbing" 
                        style="height: 600px;">
                        <div id="previewWrapper" 
                            class="absolute flex justify-center items-center" 
                            style="width: 100%; height: 100%; transition: transform 0.1s ease-out;">
                            <div id="preview" style="transform: scale(0.5); transform-origin: center center; transition: transform 0.3s ease;">
                                <!-- Preview will be rendered here -->
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-500 mt-4 mb-4 text-center">
                        <i class="ph ph-hand-grabbing mr-1"></i>
                        Drag to pan • Use zoom controls or Ctrl+/- to zoom • Press Space to reset position
                    </p>
                    <!-- Download Button -->
                    <button 
                        onclick="downloadPreview()" 
                        class="flex items-center justify-self-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition"
                        title="Download Preview as PNG">
                        <i class="ph ph-download text-xl"></i>
                        Download Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Zoom level state
        let currentZoom = 0.5; // Start at 50%
        const minZoom = 0.2;   // 20% minimum
        const maxZoom = 1.5;   // 150% maximum
        const zoomStep = 0.1;  // 10% increment

        // Pan state
        let isPanning = false;
        let startX = 0;
        let startY = 0;
        let translateX = 0;
        let translateY = 0;

        const previewContainer = document.getElementById('previewContainer');
        const previewWrapper = document.getElementById('previewWrapper');

        // Zoom functions
        function updateZoom() {
            const preview = document.getElementById('preview');
            const zoomLevel = document.getElementById('zoomLevel');
            
            preview.style.transform = `scale(${currentZoom})`;
            zoomLevel.textContent = Math.round(currentZoom * 100) + '%';
        }

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
                updateZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom = Math.max(currentZoom - zoomStep, minZoom);
                updateZoom();
            }
        }

        function resetZoom() {
            currentZoom = 0.5;
            translateX = 0;
            translateY = 0;
            updateZoom();
            updatePan();
        }

        function updatePan() {
            previewWrapper.style.transform = `translate(${translateX}px, ${translateY}px)`;
        }

        // Pan/Drag functions
        function startPan(e) {
            isPanning = true;
            previewContainer.style.cursor = 'grabbing';
            
            // Handle both mouse and touch events
            const clientX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
            const clientY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
            
            startX = clientX - translateX;
            startY = clientY - translateY;
            
            e.preventDefault();
        }

        function doPan(e) {
            if (!isPanning) return;
            
            e.preventDefault();
            
            const clientX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
            const clientY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
            
            translateX = clientX - startX;
            translateY = clientY - startY;
            
            updatePan();
        }

        function endPan() {
            isPanning = false;
            previewContainer.style.cursor = 'grab';
        }

        // Mouse events
        previewContainer.addEventListener('mousedown', startPan);
        document.addEventListener('mousemove', doPan);
        document.addEventListener('mouseup', endPan);

        // Touch events for mobile
        previewContainer.addEventListener('touchstart', startPan, { passive: false });
        document.addEventListener('touchmove', doPan, { passive: false });
        document.addEventListener('touchend', endPan);

        // Prevent context menu on right-click while dragging
        previewContainer.addEventListener('contextmenu', (e) => {
            if (isPanning) e.preventDefault();
        });

        // Update preview content
        function updatePreview() {
            const form = document.getElementById('certForm');
            const formData = new FormData(form);
            
            const settings = {
                header_title: formData.get('header_title'),
                intro_text: formData.get('intro_text'),
                completion_text: formData.get('completion_text'),
                show_grade: formData.get('show_grade') ? true : false,
                grade_text: formData.get('grade_text'),
                signature_name: formData.get('signature_name'),
                signature_title: formData.get('signature_title'),
                show_certificate_code: formData.get('show_certificate_code') ? true : false,
                certificate_code_label: formData.get('certificate_code_label'),
                date_label: formData.get('date_label'),
                primary_color: formData.get('primary_color'),
                font_family: formData.get('font_family')
            };
            
            const bgImage = '<?= addslashes($settings['background_image']) ?>';
            const logoImage = '<?= addslashes($settings['logo_image']) ?>';
            
            const preview = document.getElementById('preview');
            preview.innerHTML = `
                <div style="
                    width: 842px; 
                    height: 595px; 
                    background-image: url('../../certificate/backgrounds/${bgImage}'); 
                    background-size: cover; 
                    background-position: center;
                    background-repeat: no-repeat;
                    background-color: #ffffff; 
                    display: flex; 
                    flex-direction: column; 
                    justify-content: space-between; 
                    align-items: center; 
                    padding: 50px; 
                    font-family: ${settings.font_family}, sans-serif; 
                    color: ${settings.primary_color};
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    position: relative;
                    user-select: none;
                ">
                    <!-- Upper Section -->
                    <div style="
                        display: flex; 
                        flex-direction: column; 
                        justify-content: center; 
                        align-items: center; 
                        text-align: center;
                    ">
                        <h1 style="
                            font-size: 24px; 
                            font-weight: 600; 
                            padding-bottom: 25px; 
                            color: ${settings.primary_color};
                            margin: 0;
                        ">${escapeHtml(settings.header_title)}</h1>
                        
                        <div style="
                            max-width: 600px; 
                            display: flex; 
                            flex-direction: column; 
                            align-items: center; 
                            gap: 34px;
                        ">
                            <p style="
                                font-size: 16px; 
                                color: ${settings.primary_color};
                                margin: 0;
                            ">${escapeHtml(settings.intro_text)}</p>
                            
                            <h2 style="
                                font-size: 40px; 
                                font-weight: 600; 
                                color: ${settings.primary_color};
                                margin: 0;
                            ">John Doe</h2>
                            
                            <p style="
                                font-size: 16px; 
                                color: ${settings.primary_color};
                                margin: 0;
                                line-height: 1.5;
                            ">
                                ${escapeHtml(settings.completion_text)} <span style="font-weight: 600;">Sample Program</span>
                                ${settings.show_grade ? ` ${escapeHtml(settings.grade_text)} <span style="font-weight: 600;">95%</span>` : ''}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Lower Section -->
                    <div style="
                        width: 100%; 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                        gap: 25px;
                    ">
                        <div style="
                            width: 100%; 
                            display: flex; 
                            justify-content: space-around; 
                            align-items: center; 
                            position: relative;
                            min-height: 80px;
                        ">
                            <img 
                                src="../../certificate/logos/${logoImage}" 
                                alt="Logo" 
                                style="
                                    width: 137.2px; 
                                    height: 67.25px; 
                                    position: absolute; 
                                    left: 50%; 
                                    top: 50%; 
                                    transform: translate(-50%, -50%);
                                    object-fit: contain;
                                    pointer-events: none;
                                "
                                onerror="this.style.display='none';"
                            >
                            
                            <div style="text-align: center; z-index: 1;">
                                <p style="
                                    font-size: 14px; 
                                    font-weight: 600; 
                                    margin: 2px 0;
                                    color: ${settings.primary_color};
                                ">${escapeHtml(settings.signature_name)}</p>
                                <p style="
                                    font-size: 14px; 
                                    margin: 2px 0;
                                    color: ${settings.primary_color};
                                ">${escapeHtml(settings.signature_title)}</p>
                            </div>
                            
                            ${settings.show_certificate_code ? `
                            <div style="text-align: center; z-index: 1;">
                                <p style="
                                    font-size: 14px; 
                                    margin: 2px 0;
                                    color: ${settings.primary_color};
                                ">${escapeHtml(settings.certificate_code_label)}</p>
                                <p style="
                                    font-size: 14px; 
                                    font-weight: 600; 
                                    margin: 2px 0;
                                    color: ${settings.primary_color};
                                ">AL-0001-0001</p>
                            </div>
                            ` : '<div style="width: 150px;"></div>'}
                        </div>
                        
                        <p style="
                            font-size: 14px;
                            margin: 0;
                            color: ${settings.primary_color};
                        ">${escapeHtml(settings.date_label)} <span>November 22, 2025</span></p>
                    </div>
                </div>
            `;
            
            updateZoom();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });

        // Color picker sync
        document.querySelector('input[name="primary_color"]').addEventListener('input', function(e) {
            this.nextElementSibling.value = e.target.value;
            updatePreview();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Plus (Zoom In)
            if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                zoomIn();
            }
            // Ctrl/Cmd + Minus (Zoom Out)
            if ((e.ctrlKey || e.metaKey) && e.key === '-') {
                e.preventDefault();
                zoomOut();
            }
            // Ctrl/Cmd + 0 (Reset Zoom)
            if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                e.preventDefault();
                resetZoom();
            }
            // Space (Reset Position)
            if (e.code === 'Space' && e.target === document.body) {
                e.preventDefault();
                translateX = 0;
                translateY = 0;
                updatePan();
            }
            // Arrow keys for fine-tune panning
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                e.preventDefault();
                const step = e.shiftKey ? 50 : 10;
                
                switch(e.key) {
                    case 'ArrowUp': translateY += step; break;
                    case 'ArrowDown': translateY -= step; break;
                    case 'ArrowLeft': translateX += step; break;
                    case 'ArrowRight': translateX -= step; break;
                }
                updatePan();
            }
        });

        // Mouse wheel zoom
        previewContainer.addEventListener('wheel', function(e) {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                
                if (e.deltaY < 0) {
                    zoomIn();
                } else {
                    zoomOut();
                }
            }
        }, { passive: false });

        // Download preview as PNG at 100% scale
        async function downloadPreview() {
            const downloadBtn = event.target.closest('button');
            const originalText = downloadBtn.innerHTML;
            
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<span style="display: inline-flex; align-items: center;"><svg style="animation: spin 1s linear infinite; width: 20px; height: 20px; margin-right: 8px;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"/></svg>Generating Image...</span>';
            
            try {
                // Create isolated iframe for rendering (completely separate from Tailwind)
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.left = '-9999px';
                iframe.style.top = '0';
                iframe.style.width = '842px';
                iframe.style.height = '595px';
                iframe.style.border = 'none';
                document.body.appendChild(iframe);
                
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                
                // Get current settings
                const form = document.getElementById('certForm');
                const formData = new FormData(form);
                
                const settings = {
                    header_title: formData.get('header_title'),
                    intro_text: formData.get('intro_text'),
                    completion_text: formData.get('completion_text'),
                    show_grade: formData.get('show_grade') ? true : false,
                    grade_text: formData.get('grade_text'),
                    signature_name: formData.get('signature_name'),
                    signature_title: formData.get('signature_title'),
                    show_certificate_code: formData.get('show_certificate_code') ? true : false,
                    certificate_code_label: formData.get('certificate_code_label'),
                    date_label: formData.get('date_label'),
                    primary_color: formData.get('primary_color'),
                    font_family: formData.get('font_family')
                };
                
                const bgImage = '<?= addslashes($settings['background_image']) ?>';
                const logoImage = '<?= addslashes($settings['logo_image']) ?>';
                
                // Write complete HTML document in iframe (NO TAILWIND)
                iframeDoc.open();
                iframeDoc.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            * {
                                margin: 0;
                                padding: 0;
                                box-sizing: border-box;
                            }
                            body {
                                font-family: ${settings.font_family}, sans-serif;
                                color: ${settings.primary_color};
                            }
                        </style>
                    </head>
                    <body>
                        <div id="certificate" style="
                            width: 842px;
                            height: 595px;
                            background-image: url('../../certificate/backgrounds/${bgImage}');
                            background-size: cover;
                            background-position: center;
                            background-color: #ffffff;
                            display: flex;
                            flex-direction: column;
                            justify-content: space-between;
                            align-items: center;
                            padding: 50px;
                            position: relative;
                            font-family: ${settings.font_family}, sans-serif;
                            color: ${settings.primary_color};
                        ">
                            <div style="
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                align-items: center;
                                text-align: center;
                            ">
                                <h1 style="
                                    font-size: 24px;
                                    font-weight: 600;
                                    padding-bottom: 25px;
                                    color: ${settings.primary_color};
                                    margin: 0;
                                ">${escapeHtml(settings.header_title)}</h1>
                                
                                <div style="
                                    max-width: 600px;
                                    display: flex;
                                    flex-direction: column;
                                    align-items: center;
                                    gap: 34px;
                                ">
                                    <p style="
                                        font-size: 16px;
                                        color: ${settings.primary_color};
                                        line-height: 1.5;
                                        margin: 0;
                                    ">${escapeHtml(settings.intro_text)}</p>
                                    
                                    <h2 style="
                                        font-size: 40px;
                                        font-weight: 600;
                                        color: ${settings.primary_color};
                                        margin: 0;
                                    ">John Doe</h2>
                                    
                                    <p style="
                                        font-size: 16px;
                                        color: ${settings.primary_color};
                                        line-height: 1.5;
                                        margin: 0;
                                    ">
                                        ${escapeHtml(settings.completion_text)} <span style="font-weight: 600;">Sample Program</span>
                                        ${settings.show_grade ? ` ${escapeHtml(settings.grade_text)} <span style="font-weight: 600;">95%</span>` : ''}
                                    </p>
                                </div>
                            </div>
                            
                            <div style="
                                width: 100%;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                gap: 25px;
                            ">
                                <div style="
                                    width: 100%;
                                    display: flex;
                                    justify-content: space-around;
                                    align-items: center;
                                    position: relative;
                                    min-height: 80px;
                                ">
                                    <img 
                                        src="../../certificate/logos/${logoImage}" 
                                        alt="Logo"
                                        crossorigin="anonymous"
                                        style="
                                            width: 137.2px;
                                            height: 67.25px;
                                            position: absolute;
                                            left: 50%;
                                            top: 50%;
                                            transform: translate(-50%, -50%);
                                            object-fit: contain;
                                        "
                                    >
                                    
                                    <div style="text-align: center; z-index: 1;">
                                        <p style="
                                            font-size: 14px;
                                            font-weight: 600;
                                            margin: 2px 0;
                                            color: ${settings.primary_color};
                                        ">${escapeHtml(settings.signature_name)}</p>
                                        <p style="
                                            font-size: 14px;
                                            margin: 2px 0;
                                            color: ${settings.primary_color};
                                        ">${escapeHtml(settings.signature_title)}</p>
                                    </div>
                                    
                                    ${settings.show_certificate_code ? `
                                    <div style="text-align: center; z-index: 1;">
                                        <p style="
                                            font-size: 14px;
                                            margin: 2px 0;
                                            color: ${settings.primary_color};
                                        ">${escapeHtml(settings.certificate_code_label)}</p>
                                        <p style="
                                            font-size: 14px;
                                            font-weight: 600;
                                            margin: 2px 0;
                                            color: ${settings.primary_color};
                                        ">AL-0001-0001</p>
                                    </div>
                                    ` : '<div style="width: 150px;"></div>'}
                                </div>
                                
                                <p style="
                                    font-size: 14px;
                                    margin: 0;
                                    color: ${settings.primary_color};
                                ">${escapeHtml(settings.date_label)} <span>November 22, 2025</span></p>
                            </div>
                        </div>
                    </body>
                    </html>
                `);
                iframeDoc.close();
                
                // Wait for images to load
                await new Promise(resolve => setTimeout(resolve, 500));
                
                const element = iframeDoc.getElementById('certificate');
                
                // Use html2canvas on the iframe content
                const canvas = await html2canvas(element, {
                    scale: 3,
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 0
                });
                
                // Download
                canvas.toBlob(function(blob) {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    const timestamp = new Date().toISOString().slice(0, 10);
                    link.href = url;
                    link.download = `certificate_preview_${timestamp}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    
                    downloadBtn.innerHTML = '<span style="display: inline-flex; align-items: center;"><svg style="width: 20px; height: 20px; margin-right: 8px;" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>✓ Downloaded!</span>';
                    setTimeout(() => {
                        downloadBtn.innerHTML = originalText;
                    }, 2000);
                }, 'image/png', 1.0);
                
                // Cleanup
                document.body.removeChild(iframe);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to download certificate. Error: ' + error.message);
                downloadBtn.innerHTML = originalText;
            } finally {
                downloadBtn.disabled = false;
            }
        }
    </script>

    <? include '../../components/footer.php'; ?>
</body>
</html>