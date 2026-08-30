<?php
// api/ai_generator.php - Optimized for Low RAM (Pattern-Based) with PDF and Image support

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(60);

require_once '../config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload Composer dependencies
requireLogin();

header('Content-Type: application/json');

// Enhanced pattern-based generation (NO AI REQUIRED)
// In projectMindmap/api/ai_generator.php

// REPLACE the entire generateMindmapFromText function with this new version:
function generateMindmapFromText($text) {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    
    if (empty($lines)) {
        return ['title' => 'Empty Document', 'nodes' => []];
    }
    
    // Use frequency analysis to identify key topics
    $words = str_word_count(strtolower($text), 1);
    $stopWords = ['the','a','an','and','or','but','in','on','at','to','for','of','with',
                  'by','is','are','was','were','be','been','being','have','has','had',
                  'do','does','did','will','would','should','could','may','might','must',
                  'can','this','that','these','those','i','you','he','she','it','we','they'];
    $words = array_diff($words, $stopWords);
    
    if (empty($words)) {
        $rootTitle = array_shift($lines) ?: 'Document';
        return [
            'title' => $rootTitle,
            'nodes' => [['nodeId' => 1, 'parentId' => null, 'content' => $rootTitle, 'x' => 400, 'y' => 300, 'color' => '#667eea']]
        ];
    }
    
    $wordFreq = array_count_values($words);
    arsort($wordFreq);
    $keywords = array_slice(array_keys($wordFreq), 0, 6);
    
    $nodes = [];
    $nodeIdCounter = 1;
    
    // Create root node
    $rootTitle = array_shift($lines) ?: ucfirst($keywords[0] ?? 'Document');
    $rootTitle = preg_replace('/^#+\s*/', '', $rootTitle);
    $rootTitle = substr($rootTitle, 0, 50);
    
    $rootNodeId = $nodeIdCounter++;
    $nodes[] = ['nodeId' => $rootNodeId, 'parentId' => null, 'content' => $rootTitle, 'x' => 1000, 'y' => 1000, 'color' => '#667eea'];
    
    // Group lines by keyword presence
    $branches = [];
    foreach ($keywords as $keyword) { $branches[$keyword] = []; }
    $branches['other'] = [];
    
    foreach ($lines as $line) {
        if (empty($line)) continue;
        $lineLower = strtolower($line);
        $matched = false;
        foreach ($keywords as $keyword) {
            if (strpos($lineLower, $keyword) !== false) {
                $branches[$keyword][] = $line;
                $matched = true;
                break;
            }
        }
        if (!$matched && count($branches['other']) < 5) { $branches['other'][] = $line; }
    }
    
    $branches = array_filter($branches, fn($items) => !empty($items));
    $branches = array_slice($branches, 0, 6, true);
    
    // Create nodes from branches
    $branchAngle = 0;
    $branchCount = count($branches);
    $angleStep = 360 / max(1, $branchCount);
    
    foreach ($branches as $keyword => $branchLines) {
        // Main branch node position
        $branchRadius = 250; // Increased radius for more space
        $x = 1000 + $branchRadius * cos(deg2rad($branchAngle));
        $y = 1000 + $branchRadius * sin(deg2rad($branchAngle));
        
        $branchNodeId = $nodeIdCounter++;
        $branchTitle = $keyword === 'other' ? 'Additional Points' : ucfirst($keyword);
        $nodes[] = ['nodeId' => $branchNodeId, 'parentId' => $rootNodeId, 'content' => $branchTitle, 'x' => intval($x), 'y' => intval($y), 'color' => '#4285f4'];
        
        // Add up to 4 details per branch in a stylish arc
        $details = array_slice($branchLines, 0, 4);
        $detailCount = count($details);
        
        if ($detailCount > 0) {
            // Arc properties
            $arcAngleSpan = 90; // The total angle the arc will span
            $startAngle = $branchAngle - ($arcAngleSpan / 2);
            $detailAngleStep = $arcAngleSpan / max(1, $detailCount);
            $detailRadius = 150; // Distance from the parent branch node

            foreach ($details as $i => $detail) {
                $detailAngle = $startAngle + ($i * $detailAngleStep) + ($detailAngleStep / 2);

                // Calculate sub-node position
                $subNodeX = $x + $detailRadius * cos(deg2rad($detailAngle));
                $subNodeY = $y + $detailRadius * sin(deg2rad($detailAngle));

                // Clean up text
                $detail = preg_replace('/^[-*•\d\.]+\s*/', '', $detail);
                $detail = substr($detail, 0, 60);
                
                $nodes[] = [
                    'nodeId' => $nodeIdCounter++,
                    'parentId' => $branchNodeId,
                    'content' => $detail,
                    'x' => intval($subNodeX),
                    'y' => intval($subNodeY),
                    'color' => '#34a853'
                ];
            }
        }
        $branchAngle += $angleStep;
    }
    
    return ['title' => $rootTitle, 'nodes' => $nodes];
}


// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['document'])) {
            throw new Exception('No file uploaded');
        }

        $file = $_FILES['document'];
        $fileContent = '';

        // Validate file upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File too large',
                UPLOAD_ERR_PARTIAL => 'File partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
            ];
            throw new Exception($errorMessages[$file['error']] ?? 'Upload error');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 5MB');
        }

        // Determine file type and extract text accordingly
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_text_types = ['text/plain', 'text/markdown', 'text/csv', 'application/x-empty'];
        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($mime_type, $allowed_text_types)) {
            // It's a plain text file, read it directly
            $fileContent = file_get_contents($file['tmp_name']);
        } elseif ($mime_type === 'application/pdf') {
            // It's a PDF, use PdfParser to extract text
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file['tmp_name']);
            $fileContent = $pdf->getText();
        } elseif (in_array($mime_type, $allowed_image_types)) {
            // It's an image, use Tesseract OCR to extract text
            $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($file['tmp_name']);
            $fileContent = $ocr->run();
        } else {
            // Unsupported file type
            throw new Exception('Unsupported file type: ' . $mime_type . '. Please upload a text file, PDF, or image.');
        }

        if ($fileContent === false) {
             throw new Exception('Failed to read or process the file.');
        }

        // Convert encoding if needed
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
        }

        // Generate mindmap using the extracted text
        $mindmapData = generateMindmapFromText($fileContent);

        // Store in session
        $_SESSION['ai_generated_map'] = $mindmapData;

        echo json_encode([
            'success' => true,
            'message' => 'Mindmap generated successfully!',
            'nodeCount' => count($mindmapData['nodes'])
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>