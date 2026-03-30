<?php
/**
 * Fetch Program Content from CCRI Catalog
 * 
 * Fetches program details from catalog XML and returns formatted HTML
 * Implements caching: 6 hours fresh, 7 days stale fallback
 * 
 * Usage: fetch-program-content.php?url=/programs-study/path/to/program/
 */

header('Content-Type: text/xml; charset=utf-8');

// Get catalog URL from parameter
$catalog_path = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($catalog_path)) {
    die('Error: No catalog URL provided. Use ?url=/programs-study/path/to/program/');
}

// Configuration
$CATALOG_BASE = 'https://catalog.ccri.edu';
$CACHE_DIR = __DIR__ . '/cache';
$FRESH_CACHE_DURATION = 6 * 3600; // 6 hours
$STALE_CACHE_DURATION = 7 * 24 * 3600; // 7 days

// Create cache directory if it doesn't exist
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0755, true);
}

// Generate cache filename from URL
$cache_filename = $CACHE_DIR . '/program_' . md5($catalog_path) . '.html';

// Check cache
$use_cache = false;
$cache_age = 0;

if (file_exists($cache_filename)) {
    $cache_age = time() - filemtime($cache_filename);
    
    // Use cache if less than 6 hours old
    if ($cache_age < $FRESH_CACHE_DURATION) {
        $use_cache = true;
    }
}

// If cache is fresh, return it
if ($use_cache) {
    $cache_age_minutes = round($cache_age / 60);
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<content>';
    echo "<!-- CACHE HIT: Served from cache ({$cache_age_minutes} minutes old) -->";
    echo file_get_contents($cache_filename);
    echo '</content>';
    exit;
}

// Fetch fresh data from catalog
$catalog_url = $CATALOG_BASE . $catalog_path . 'index.xml';
$xml_content = @file_get_contents($catalog_url);

// If fetch failed, try to use stale cache (up to 7 days old)
if ($xml_content === false) {
    if (file_exists($cache_filename) && $cache_age < $STALE_CACHE_DURATION) {
        // Use stale cache as fallback
        $cache_age_hours = round($cache_age / 3600);
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<content>';
        echo "<!-- STALE CACHE: Catalog unavailable, using stale cache ({$cache_age_hours} hours old) -->";
        echo file_get_contents($cache_filename);
        echo '</content>';
        exit;
    } else {
        die('Error: Could not fetch program data and no valid cache available.');
    }
}

// Parse XML
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xml_content);

if ($xml === false) {
    // XML parsing failed, try stale cache
    if (file_exists($cache_filename) && $cache_age < $STALE_CACHE_DURATION) {
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<content>';
        echo file_get_contents($cache_filename);
        echo '</content>';
        exit;
    } else {
        die('Error: Could not parse program XML and no valid cache available.');
    }
}

// Extract CDATA sections
$overview = '';
$learning_outcomes = '';
$requirements = '';
$sequence = '';
$transfer = '';

// Overview can be in <overviewtext> OR <text>
if (isset($xml->overviewtext)) {
    $overview = trim((string)$xml->overviewtext);
} elseif (isset($xml->text)) {
    $overview = trim((string)$xml->text);
}

if (isset($xml->learningoutcomestext)) {
    $learning_outcomes = trim((string)$xml->learningoutcomestext);
}

if (isset($xml->requirementstext)) {
    $requirements = trim((string)$xml->requirementstext);
}

// Sequence can be in <sequencetext> OR <newitemtext>
if (isset($xml->sequencetext)) {
    $sequence = trim((string)$xml->sequencetext);
} elseif (isset($xml->newitemtext)) {
    $sequence = trim((string)$xml->newitemtext);
}

if (isset($xml->transfertext)) {
    $transfer = trim((string)$xml->transfertext);
}

// Build catalog description content
$catalog_content = '';

// Add overview paragraph
if (!empty($overview)) {
    $catalog_content .= $overview . "\n\n";
}

// Start accordion wrapper
$catalog_content .= '<div class="accordion-box" style="margin-bottom: 20px;">' . "\n";

// Add Learning Outcomes accordion
if (!empty($learning_outcomes)) {
    $catalog_content .= '<div class="accordion-fin"><button class="acc-btn" aria-expanded="false"><p>Learning Outcomes</p></button></div>' . "\n";
    $catalog_content .= '<div class="accordion-desc">' . "\n";
    $catalog_content .= $learning_outcomes . "\n";
    $catalog_content .= '</div>' . "\n";
}

// Add Requirements accordion (wrap table)
if (!empty($requirements)) {
    $catalog_content .= '<div class="accordion-fin"><button class="acc-btn" aria-expanded="false"><p>Requirements</p></button></div>' . "\n";
    $catalog_content .= '<div class="accordion-desc">' . "\n";
    // Wrap tables in responsive-table div and fix duplicate class
    $requirements_wrapped = preg_replace('/<table\s+class="sc_courselist"/', '<div class="responsive-table"><table class="table"', $requirements);
    $requirements_wrapped = preg_replace('/<\/table>/', '</table></div>', $requirements_wrapped);
    $catalog_content .= $requirements_wrapped . "\n";
    $catalog_content .= '</div>' . "\n";
}

// Add Sequence accordion (wrap table)
if (!empty($sequence)) {
    $catalog_content .= '<div class="accordion-fin"><button class="acc-btn" aria-expanded="false"><p>Sequence</p></button></div>' . "\n";
    $catalog_content .= '<div class="accordion-desc">' . "\n";
    // Wrap tables in responsive-table div and fix duplicate class
    $sequence_wrapped = preg_replace('/<table\s+[^>]*class="[^"]*"/', '<div class="responsive-table"><table class="table"', $sequence);
    $sequence_wrapped = preg_replace('/<\/table>/', '</table></div>', $sequence_wrapped);
    $catalog_content .= $sequence_wrapped . "\n";
    $catalog_content .= '</div>' . "\n";
}

// Add Transfer accordion if exists
if (!empty($transfer)) {
    $catalog_content .= '<div class="accordion-fin"><button class="acc-btn" aria-expanded="false"><p>Transfer</p></button></div>' . "\n";
    $catalog_content .= '<div class="accordion-desc">' . "\n";
    $catalog_content .= $transfer . "\n";
    $catalog_content .= '</div>' . "\n";
}

// Close accordion wrapper
$catalog_content .= '</div>' . "\n";

// Convert code_bubble spans to actual catalog links
$catalog_content = preg_replace_callback(
    '/<span class="code_bubble" data-code-bubble="([^"]+)">([^<]+)<\/span>/',
    function($matches) {
        $course_code = $matches[1];
        $course_text = $matches[2];
        $encoded_code = urlencode($course_code);
        return '<a href="https://catalog.ccri.edu/search/?P=' . $encoded_code . '" target="_blank">' . $course_text . '</a>';
    },
    $catalog_content
);

// Convert all relative links to absolute catalog links
$catalog_content = preg_replace(
    '/href="\/([^"]+)"/',
    'href="https://catalog.ccri.edu/$1"',
    $catalog_content
);

// Strip out images
$catalog_content = preg_replace('/<img[^>]*>/', '', $catalog_content);

// Save to cache
file_put_contents($cache_filename, $catalog_content);

// Output content wrapped in XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<content>';
echo "<!-- FRESH FETCH: Just fetched from catalog and cached -->";
echo $catalog_content;
echo '</content>';
