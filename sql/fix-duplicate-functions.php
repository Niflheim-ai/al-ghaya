<?php
/**
 * Duplicate Function Detection and Fix Script
 * This script scans all PHP files in the al-ghaya project to identify
 * duplicate function declarations that cause redeclaration errors.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<html><head><title>Al-Ghaya Duplicate Function Fix</title></head><body>";
echo "<h1>Al-Ghaya Duplicate Function Detection and Fix</h1>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 20px; border-radius: 5px;'>";

// Define directories to scan
$scan_directories = [
    '../php/',
    '../pages/',
    '../components/'
];

$functions_found = [];
$duplicate_functions = [];
$files_scanned = [];

/**
 * Extract function names from PHP file content
 */
function extractFunctions($content, $filename) {
    $functions = [];
    
    // Match function declarations
    preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE);
    
    foreach ($matches[1] as $match) {
        $func_name = $match[0];
        $position = $match[1];
        
        // Get line number
        $line_number = substr_count(substr($content, 0, $position), "\n") + 1;
        
        $functions[] = [
            'name' => $func_name,
            'line' => $line_number,
            'file' => $filename
        ];
    }
    
    return $functions;
}

/**
 * Recursively scan directory for PHP files
 */
function scanDirectory($dir) {
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

echo "<h3>🔍 Scanning PHP Files for Function Declarations</h3>";

// Scan all directories
foreach ($scan_directories as $dir) {
    if (is_dir($dir)) {
        echo "<p>Scanning directory: <strong>$dir</strong></p>";
        
        $files = scanDirectory($dir);
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                echo "<p>⚠️ Could not read file: $file</p>";
                continue;
            }
            
            $relative_path = str_replace('../', '', $file);
            $functions = extractFunctions($content, $relative_path);
            $files_scanned[] = $relative_path;
            
            echo "<p>📄 {$relative_path}: " . count($functions) . " functions found</p>";
            
            // Track all functions
            foreach ($functions as $func) {
                if (!isset($functions_found[$func['name']])) {
                    $functions_found[$func['name']] = [];
                }
                $functions_found[$func['name']][] = $func;
            }
        }
    } else {
        echo "<p>⚠️ Directory not found: $dir</p>";
    }
}

echo "<p><strong>📊 Total files scanned: " . count($files_scanned) . "</strong></p>";
echo "<p><strong>📊 Total unique functions found: " . count($functions_found) . "</strong></p>";

// Find duplicates
echo "<h3>🔍 Analyzing for Duplicate Function Declarations</h3>";

foreach ($functions_found as $func_name => $locations) {
    if (count($locations) > 1) {
        $duplicate_functions[$func_name] = $locations;
    }
}

if (empty($duplicate_functions)) {
    echo "<p style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px;'>";
    echo "<strong>✅ SUCCESS: No duplicate function declarations found!</strong>";
    echo "</p>";
} else {
    echo "<p style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ FOUND " . count($duplicate_functions) . " DUPLICATE FUNCTIONS:</strong>";
    echo "</p>";
    
    foreach ($duplicate_functions as $func_name => $locations) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>❌ Function: <code>{$func_name}</code></h4>";
        echo "<p>Found in " . count($locations) . " locations:</p>";
        echo "<ul>";
        
        foreach ($locations as $location) {
            echo "<li><strong>{$location['file']}</strong> at line <strong>{$location['line']}</strong></li>";
        }
        
        echo "</ul>";
        echo "</div>";
    }
}

// Provide recommendations
echo "<h3>💡 Recommendations</h3>";

if (!empty($duplicate_functions)) {
    echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; padding: 15px; border-radius: 5px;'>";
    echo "<h4>🔧 How to Fix Duplicate Functions:</h4>";
    echo "<ol>";
    
    foreach ($duplicate_functions as $func_name => $locations) {
        echo "<li><strong>{$func_name}</strong>:";
        echo "<ul>";
        echo "<li>Keep the function in <strong>{$locations[0]['file']}</strong> (primary location)</li>";
        
        for ($i = 1; $i < count($locations); $i++) {
            echo "<li>Remove or rename the function in <strong>{$locations[$i]['file']}</strong> at line {$locations[$i]['line']}</li>";
        }
        
        echo "</ul>";
        echo "</li>";
    }
    
    echo "</ol>";
    echo "<p><strong>Alternative approach:</strong></p>";
    echo "<ul>";
    echo "<li>Use <code>function_exists('{$func_name}')</code> before declaring functions</li>";
    echo "<li>Create a single centralized function file</li>";
    echo "<li>Use namespaces to avoid conflicts</li>";
    echo "</ul>";
    echo "</div>";
    
    // Specific recommendations for common duplicates
    if (isset($duplicate_functions['getTeacherIdFromSession'])) {
        echo "<div style='background: #cff4fc; border: 1px solid #b6effb; padding: 15px; border-radius: 5px; margin-top: 15px;'>";
        echo "<h4>🎯 Specific Fix for getTeacherIdFromSession:</h4>";
        echo "<p>This function is critical for teacher authentication. Recommended approach:</p>";
        echo "<ol>";
        echo "<li>Keep the enhanced version from <strong>program-helpers.php</strong> (has auto-creation feature)</li>";
        echo "<li>Remove the duplicate from <strong>functions.php</strong></li>";
        echo "<li>Ensure all files include <strong>program-helpers.php</strong> before using this function</li>";
        echo "</ol>";
        echo "</div>";
    }
    
} else {
    echo "<p>🎉 All functions are unique! No action needed.</p>";
}

// Show detailed function inventory
echo "<h3>📋 Complete Function Inventory</h3>";
echo "<div style='max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-radius: 5px;'>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #e9ecef;'><th style='border: 1px solid #dee2e6; padding: 8px;'>Function Name</th><th style='border: 1px solid #dee2e6; padding: 8px;'>File</th><th style='border: 1px solid #dee2e6; padding: 8px;'>Line</th><th style='border: 1px solid #dee2e6; padding: 8px;'>Status</th></tr>";

foreach ($functions_found as $func_name => $locations) {
    $is_duplicate = count($locations) > 1;
    $row_class = $is_duplicate ? "background: #f8d7da;" : "background: #d1e7dd;";
    
    foreach ($locations as $location) {
        $status = $is_duplicate ? "❌ DUPLICATE" : "✅ UNIQUE";
        echo "<tr style='{$row_class}'>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><code>{$func_name}</code></td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$location['file']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$location['line']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$status}</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "</div>";

// Summary and next steps
echo "<h3>📈 Summary</h3>";
echo "<ul>";
echo "<li><strong>Files scanned:</strong> " . count($files_scanned) . "</li>";
echo "<li><strong>Total functions:</strong> " . array_sum(array_map('count', $functions_found)) . "</li>";
echo "<li><strong>Unique functions:</strong> " . count($functions_found) . "</li>";
echo "<li><strong>Duplicate functions:</strong> " . count($duplicate_functions) . "</li>";
echo "</ul>";

if (!empty($duplicate_functions)) {
    echo "<h3>🚨 Action Required</h3>";
    echo "<p style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px;'>";
    echo "<strong>You have duplicate function declarations that need to be resolved to prevent PHP errors.</strong><br>";
    echo "Please follow the recommendations above to fix these issues.";
    echo "</p>";
} else {
    echo "<h3>🎉 All Clear!</h3>";
    echo "<p style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px;'>";
    echo "<strong>No duplicate functions found. Your codebase is clean!</strong>";
    echo "</p>";
}

echo "</div>";
echo "<p style='text-align: center; margin-top: 40px;'><a href='../pages/teacher/teacher-programs.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Create Program</a></p>";
echo "</body></html>";
?>