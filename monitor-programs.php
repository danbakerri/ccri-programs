<?php
/**
 * Enhanced Program Monitoring Script
 * 
 * Detects:
 * - New programs added to catalog
 * - Programs removed from catalog
 * - URL/path changes for existing programs
 * - Title changes
 * 
 * Usage: php monitor-programs.php
 */

require_once 'department-urls.php';

echo "=== CCRI PROGRAM MONITORING TOOL ===\n\n";

// Fetch current program list from catalog
$program_list_url = 'https://www.ccri.edu/_resources-2025/php/program-list.php?v=7';
echo "Fetching current program list from catalog...\n";

$programs_json = @file_get_contents($program_list_url);
if ($programs_json === false) {
    die("ERROR: Could not fetch program list from {$program_list_url}\n");
}

$all_programs = json_decode($programs_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: Could not parse program list JSON\n");
}

echo "✓ Fetched " . count($all_programs) . " total programs from catalog\n";

// Filter to academic programs only
$catalog_programs = array_filter($all_programs, function($program) {
    return in_array($program['type'], ['assoc', 'cert', 'dipl']);
});

echo "✓ Filtered to " . count($catalog_programs) . " academic programs\n\n";

// Create catalog map by slug
$catalog_by_slug = [];
foreach ($catalog_programs as $program) {
    $catalog_by_slug[$program['id']] = [
        'title' => $program['title'],
        'type' => $program['type'],
        'department' => $program['department'] ?? '',
        'url' => $program['url']
    ];
}

// -------------------------------------------------------------------
// BASELINE FILE - Stores your current programs
// -------------------------------------------------------------------

$baseline_file = __DIR__ . '/program-baseline.json';

// Check if baseline exists
if (!file_exists($baseline_file)) {
    echo "⚠️  No baseline file found. Creating initial baseline...\n\n";
    
    // Create baseline from current catalog
    file_put_contents($baseline_file, json_encode($catalog_by_slug, JSON_PRETTY_PRINT));
    
    echo "✓ Created baseline file: program-baseline.json\n";
    echo "  Saved " . count($catalog_by_slug) . " programs as baseline\n\n";
    
    echo "=================================================\n";
    echo "INITIAL BASELINE CREATED\n";
    echo "=================================================\n\n";
    echo "This is your first run. The baseline has been saved.\n";
    echo "Run this script again in the future to detect changes.\n\n";
    
    echo "Programs in baseline:\n";
    foreach ($catalog_by_slug as $slug => $info) {
        echo "  • {$info['title']} ({$slug})\n";
    }
    
    exit(0);
}

// Load baseline
$baseline_json = file_get_contents($baseline_file);
$baseline = json_decode($baseline_json, true);

if (!is_array($baseline)) {
    die("ERROR: Could not parse baseline file\n");
}

echo "✓ Loaded baseline: " . count($baseline) . " programs\n\n";

// -------------------------------------------------------------------
// COMPARISON
// -------------------------------------------------------------------

$new_programs = [];
$removed_programs = [];
$url_changed = [];
$title_changed = [];
$unchanged = [];

// Check each catalog program against baseline
foreach ($catalog_by_slug as $slug => $catalog_info) {
    if (!isset($baseline[$slug])) {
        // New program
        $new_programs[$slug] = $catalog_info;
    } else {
        // Program exists - check for changes
        $baseline_info = $baseline[$slug];
        
        $has_changes = false;
        
        // Check URL change
        if ($catalog_info['url'] !== $baseline_info['url']) {
            $url_changed[$slug] = [
                'title' => $catalog_info['title'],
                'old_url' => $baseline_info['url'],
                'new_url' => $catalog_info['url']
            ];
            $has_changes = true;
        }
        
        // Check title change
        if ($catalog_info['title'] !== $baseline_info['title']) {
            $title_changed[$slug] = [
                'old_title' => $baseline_info['title'],
                'new_title' => $catalog_info['title'],
                'url' => $catalog_info['url']
            ];
            $has_changes = true;
        }
        
        if (!$has_changes) {
            $unchanged[] = $slug;
        }
    }
}

// Check for removed programs
foreach ($baseline as $slug => $baseline_info) {
    if (!isset($catalog_by_slug[$slug])) {
        $removed_programs[$slug] = $baseline_info;
    }
}

// -------------------------------------------------------------------
// REPORT
// -------------------------------------------------------------------

echo "=================================================\n";
echo "              MONITORING REPORT                  \n";
echo "=================================================\n\n";

$total_changes = count($new_programs) + count($removed_programs) + count($url_changed) + count($title_changed);

if ($total_changes === 0) {
    echo "✓ NO CHANGES DETECTED\n";
    echo "  All programs match the baseline.\n\n";
} else {
    echo "⚠️  {$total_changes} CHANGE(S) DETECTED\n\n";
}

// New Programs
if (!empty($new_programs)) {
    echo "📌 NEW PROGRAMS (" . count($new_programs) . "):\n";
    echo "Action: Generate PCF files and upload to Modern Campus\n\n";
    
    foreach ($new_programs as $slug => $info) {
        echo "  • {$info['title']}\n";
        echo "    Slug: {$slug}\n";
        echo "    Type: {$info['type']}\n";
        echo "    Department: {$info['department']}\n";
        echo "    Catalog URL: {$info['url']}\n";
        echo "    Generate: php generate-program-dynamic.php {$info['url']}\n";
        echo "\n";
    }
}

// Removed Programs
if (!empty($removed_programs)) {
    echo "🗑️  REMOVED PROGRAMS (" . count($removed_programs) . "):\n";
    echo "Action: Delete from Modern Campus or mark as discontinued\n\n";
    
    foreach ($removed_programs as $slug => $info) {
        echo "  • {$info['title']} ({$slug})\n";
        echo "    Was at: {$info['url']}\n";
        echo "    Modern Campus path: /programs/{$slug}/\n";
        echo "\n";
    }
}

// URL Changed
if (!empty($url_changed)) {
    echo "⚠️  URL CHANGED (" . count($url_changed) . "):\n";
    echo "Action: Update 'catalog-url' parameter in Modern Campus page properties\n\n";
    
    foreach ($url_changed as $slug => $info) {
        echo "  • {$info['title']}\n";
        echo "    Slug: {$slug}\n";
        echo "    Old URL: {$info['old_url']}\n";
        echo "    New URL: {$info['new_url']}\n";
        echo "    Modern Campus: Edit /programs/{$slug}/ properties\n";
        echo "\n";
    }
}

// Title Changed
if (!empty($title_changed)) {
    echo "📝 TITLE CHANGED (" . count($title_changed) . "):\n";
    echo "Action: Update 'heading' parameter in Modern Campus page properties\n\n";
    
    foreach ($title_changed as $slug => $info) {
        echo "  • {$slug}\n";
        echo "    Old Title: {$info['old_title']}\n";
        echo "    New Title: {$info['new_title']}\n";
        echo "    Catalog URL: {$info['url']}\n";
        echo "    Modern Campus: Edit /programs/{$slug}/ properties\n";
        echo "\n";
    }
}

// Unchanged
if (!empty($unchanged)) {
    echo "✓ UNCHANGED PROGRAMS (" . count($unchanged) . "):\n";
    if (count($unchanged) <= 10) {
        foreach ($unchanged as $slug) {
            echo "  • {$slug}\n";
        }
    } else {
        echo "  (showing first 10 of " . count($unchanged) . ")\n";
        for ($i = 0; $i < 10; $i++) {
            echo "  • {$unchanged[$i]}\n";
        }
        echo "  ... and " . (count($unchanged) - 10) . " more\n";
    }
    echo "\n";
}

// Summary
echo "=================================================\n";
echo "SUMMARY:\n";
echo "  Baseline Programs: " . count($baseline) . "\n";
echo "  Current Catalog Programs: " . count($catalog_by_slug) . "\n";
echo "  New: " . count($new_programs) . "\n";
echo "  Removed: " . count($removed_programs) . "\n";
echo "  URL Changed: " . count($url_changed) . "\n";
echo "  Title Changed: " . count($title_changed) . "\n";
echo "  Unchanged: " . count($unchanged) . "\n";
echo "=================================================\n\n";

// -------------------------------------------------------------------
// UPDATE BASELINE OPTION
// -------------------------------------------------------------------

if ($total_changes > 0) {
    echo "Would you like to update the baseline with current catalog data? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if ($line === 'y' || $line === 'Y') {
        file_put_contents($baseline_file, json_encode($catalog_by_slug, JSON_PRETTY_PRINT));
        echo "✓ Baseline updated with current catalog data\n\n";
    } else {
        echo "Baseline not updated. Run this script again to see the same changes.\n\n";
    }
}

// -------------------------------------------------------------------
// INSTRUCTIONS
// -------------------------------------------------------------------

echo "=== NEXT STEPS ===\n\n";

if (!empty($new_programs)) {
    echo "For NEW programs:\n";
    echo "1. Run the generate command shown above for each new program\n";
    echo "2. Upload the generated folders to /programs/ in Modern Campus\n";
    echo "3. Publish the pages\n\n";
}

if (!empty($removed_programs)) {
    echo "For REMOVED programs:\n";
    echo "1. Navigate to /programs/{slug}/ in Modern Campus\n";
    echo "2. Either delete the folder OR\n";
    echo "3. Change 'display-catalog-description' to 'No' and add custom 'discontinued' message\n\n";
}

if (!empty($url_changed)) {
    echo "For URL CHANGED programs:\n";
    echo "1. Open the program page in Modern Campus\n";
    echo "2. Edit page properties\n";
    echo "3. Update the 'catalog-url' parameter with the new URL\n";
    echo "4. Republish the page\n\n";
}

if (!empty($title_changed)) {
    echo "For TITLE CHANGED programs:\n";
    echo "1. Open the program page in Modern Campus\n";
    echo "2. Edit page properties\n";
    echo "3. Update the 'heading' parameter with the new title\n";
    echo "4. Update the 'nav-heading' in _props.pcf if needed\n";
    echo "5. Republish the page\n\n";
}

echo "Run this script regularly (monthly) to monitor for catalog changes.\n";
