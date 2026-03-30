<?php
/**
 * Bulk Program Generation Script
 * 
 * Generates PCF files for ALL programs from the program list
 * Creates organized directory structure ready for Modern Campus upload
 * 
 * Usage: php generate-all-programs.php
 */

require_once 'department-urls.php';

// Configuration
$CATALOG_BASE = 'https://catalog.ccri.edu';
$PROGRAM_LIST_URL = 'https://www.ccri.edu/_resources-2025/php/program-list.php?v=7';
$OUTPUT_BASE_DIR = __DIR__ . '/bulk-output';

// Stats tracking
$stats = [
    'total' => 0,
    'success' => 0,
    'failed' => 0,
    'skipped' => 0,
    'types' => [
        'assoc' => 0,
        'cert' => 0,
        'dipl' => 0
    ]
];

echo "\n";
echo "=====================================\n";
echo "   BULK PROGRAM GENERATOR\n";
echo "=====================================\n\n";

// Create output directory
if (!is_dir($OUTPUT_BASE_DIR)) {
    mkdir($OUTPUT_BASE_DIR, 0755, true);
    echo "✓ Created output directory: {$OUTPUT_BASE_DIR}\n\n";
}

// Fetch program list
echo "Fetching program list from: {$PROGRAM_LIST_URL}\n";
$programs_json = @file_get_contents($PROGRAM_LIST_URL);

if ($programs_json === false) {
    die("ERROR: Could not fetch program list\n");
}

$programs = json_decode($programs_json, true);
if (!is_array($programs)) {
    die("ERROR: Could not parse program list JSON\n");
}

echo "✓ Found " . count($programs) . " programs\n\n";

// Filter to only academic programs (assoc, cert, dipl)
$academic_programs = array_filter($programs, function($p) {
    return in_array($p['type'], ['assoc', 'cert', 'dipl']);
});

echo "✓ Filtered to " . count($academic_programs) . " academic programs (assoc/cert/dipl)\n";
echo "  (Skipping " . (count($programs) - count($academic_programs)) . " transfer/workforce programs)\n\n";

$stats['total'] = count($academic_programs);

// Confirm before proceeding
echo "This will generate PCF files for " . count($academic_programs) . " programs.\n";
echo "Continue? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) != 'y') {
    echo "Cancelled.\n";
    exit;
}
fclose($handle);

echo "\n=== STARTING BULK GENERATION ===\n\n";

// Process each program
foreach ($academic_programs as $index => $program) {
    $num = $index + 1;
    $total = count($academic_programs);
    
    echo "[{$num}/{$total}] Processing: {$program['title']}\n";
    
    // Skip if no catalog URL (shouldn't happen, but be safe)
    if (empty($program['url'])) {
        echo "  ⚠ SKIPPED: No URL\n\n";
        $stats['skipped']++;
        continue;
    }
    
    // Build catalog path from URL
    $PROGRAM_CATALOG_PATH = $program['url'];
    $program_id = $program['id'];
    
    // Get department info
    $department_name_clean = !empty($program['department']) ? $program['department'] : 'Unknown Department';
    
    // Normalize department name using mapping (if exists)
    if (isset($DEPARTMENT_NAME_MAP[$department_name_clean])) {
        $department_name_clean = $DEPARTMENT_NAME_MAP[$department_name_clean];
    }
    
    // Fetch program XML to get title
    $program_xml_url = $CATALOG_BASE . $PROGRAM_CATALOG_PATH . 'index.xml';
    $xml_content = @file_get_contents($program_xml_url);
    
    if ($xml_content === false) {
        echo "  ✗ FAILED: Could not fetch XML from catalog\n\n";
        $stats['failed']++;
        continue;
    }
    
    // Parse XML
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_content);
    
    if ($xml === false) {
        echo "  ✗ FAILED: Could not parse XML\n\n";
        $stats['failed']++;
        continue;
    }
    
    // Extract program title
    $program_title = (string)$xml->title;
    
    // XML-encode all values to ensure strict XML compliance
    $program_title_encoded = htmlspecialchars($program_title, ENT_XML1, 'UTF-8');
    
    // Department info
    $department_name = $department_name_clean;
    $department_name_encoded = htmlspecialchars($department_name, ENT_XML1, 'UTF-8');
    
    // Look up URLs from mapping
    if (isset($DEPARTMENT_URLS[$department_name_clean])) {
        $department_link = $DEPARTMENT_URLS[$department_name_clean]['main'];
        $department_contact = $DEPARTMENT_URLS[$department_name_clean]['contact'];
    } else {
        $department_link = "/";
        $department_contact = "/";
    }
    
    // Encode catalog path
    $catalog_path_encoded = htmlspecialchars($PROGRAM_CATALOG_PATH, ENT_XML1, 'UTF-8');
    
    // Widget code - empty (will be added manually later)
    $widget_code = '';
    
    // Create output directory for this program
    $OUTPUT_DIR = $OUTPUT_BASE_DIR . '/' . $program_id;
    if (!is_dir($OUTPUT_DIR)) {
        mkdir($OUTPUT_DIR, 0755, true);
    }
    
    // Generate _props.pcf
    $props_content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<?pcf-stylesheet path="/_resources-2025/xsl/properties.xsl" title="Properties" extension="html"?>
<!DOCTYPE document SYSTEM "http://commons.omniupdate.com/dtd/standard.dtd"> 
<document xmlns:ouc="http://omniupdate.com/XSL/Variables">
	<ouc:properties label="metadata">
		<title></title>
		<meta name="Description" content="" />
		<meta name="Keywords" content="" />
		<meta name="tmsp_pagetype" content="nav"/>
	</ouc:properties>
	<ouc:properties label="config">
		<parameter section="Breadcrumb" name="nav-heading" group="Everyone" prompt="Navigation Heading" alt="The text to display in the left navigation. Leave blank to not have navigation.">{$program_title_encoded}</parameter>
	</ouc:properties>
</document>
XML;

    file_put_contents($OUTPUT_DIR . '/_props.pcf', $props_content);
    
    // Generate index.pcf
    $index_content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<?pcf-stylesheet path="/_resources-2025/xsl/program.xsl" extension="html" title="Program Page"?>
<!DOCTYPE document SYSTEM "http://commons.omniupdate.com/dtd/standard.dtd">

<document xmlns:ouc="http://omniupdate.com/XSL/Variables">

	<headcode>
		<meta name="robots" content="noindex, nofollow" />
</headcode>
	<bodycode></bodycode>
	<footcode>
</footcode>

	<parameter name="header-gallery-type">masthead-carousel</parameter>
<ouc:properties label="config">
        <parameter name="heading" group="Everyone" prompt="Page Heading" alt="Heading text for the main content region.">{$program_title_encoded}</parameter>
<parameter name="green-first-word" group="Everyone" type="select" prompt="Make First Word in Page Heading Green?" alt="Make First Word in Page Heading Green?">
<option value="true-green-word" selected="false">Yes</option>
<option value="false-green-word" selected="true">No</option>
</parameter>
	
	
	
        <parameter name="gallery-type" type="select" group="Everyone" prompt="Gallery Type" alt="Type of image gallery to include on the page.">
<option value="flex-slider" selected="false">Flex Slider</option>
<option value="fancy-box" selected="true">Fancy Box</option>
</parameter>
	
	
	 <parameter section="Department Section" name="department-name" group="Everyone" prompt="Department Name" alt="PLease Enter your academic department name.">{$department_name_encoded}</parameter>
	<parameter name="department-link" group="Everyone" type="filechooser" dependency="yes" source="production" prompt="Enter link to your Academic department website." alt="Enter link to you Academic department website.">{$department_link}</parameter>
	
	<parameter name="contact-department-link" group="Everyone" type="filechooser" dependency="yes" source="production" prompt="Enter link to your Academic department contact page." alt="Enter link to your Academic department contact page.">{$department_contact}</parameter>
	  <parameter name="display-catalog-description" group="Everyone" type="select" prompt="Display Decription from Catalog?" alt="Display Decription from Catalog?">
<option value="display-catalog-description" selected="true">Yes</option>
<option value="donot-display-catalog-description" selected="false">No</option>
</parameter>
	<parameter name="catalog-url" group="Everyone" prompt="Catalog Program URL" alt="URL path to this program in the catalog">{$catalog_path_encoded}</parameter>
	
       

        <parameter section="Header Image" name="header-Image" group="Everyone" type="filechooser" dependency="yes" source="production" path="/_resources-2025/images/header-images/" prompt="Choose your Header Image" alt="Choose your Header Image.">{{f:78571124}}</parameter>

        <parameter name="header-Image-position" group="Everyone" type="select" prompt="Header Image Position" alt="Adjust the up/down position of your image.">
<option value="50" selected="false">Middle</option>
<option value="25" selected="true">Middle Top</option>
<option value="0" selected="false">Top</option>
<option value="75" selected="false">Middle Bottom</option>
<option value="100" selected="false">Bottom</option>
</parameter>

   
       

        <parameter section="Additional Edit Sections" name="edit-sec-one" group="Everyone" type="select" prompt="Display Edit Section One" alt="Display Edit Section One?">
<option value="display-section" selected="false">Yes</option>
<option value="no-display-one" selected="true">No</option>
</parameter>
        <parameter name="sec-one-color" group="Everyone" type="select" prompt="Section One Color" alt="Section One Background Color?">
<option value="white-background" selected="false">White</option>
<option value="grey-background" selected="true">Grey</option>
</parameter>
        <parameter name="sec-one-width" group="Everyone" type="select" prompt="Section One Width" alt="Section One Width?">
<option value="add-edit-section edit-section-one wide" selected="true">Wide</option>
<option value="add-edit-section edit-section-one narrow" selected="false">Narrow</option>
</parameter>
        <parameter name="edit-sec-two" group="Everyone" type="select" prompt="Display Edit Section Two" alt="Display Edit Section Two?">
<option value="display-section" selected="false">Yes</option>
<option value="no-display-two" selected="true">No</option>
</parameter>
        <parameter name="sec-two-color" group="Everyone" type="select" prompt="Section Two Color" alt="Section Two Background Color?">
<option value="white-background" selected="true">White</option>
<option value="grey-background" selected="false">Grey</option>
</parameter>
        <parameter name="sec-two-width" group="Everyone" type="select" prompt="Section Two Width" alt="Section Two Width?">
<option value="add-edit-section edit-section-two wide" selected="true">Wide</option>
<option value="add-edit-section edit-section-two narrow" selected="false">Narrow</option>
</parameter>


        <parameter name="edit-sec-three" group="Everyone" type="select" prompt="Display Edit Section three" alt="Display Edit Section three?">
<option value="display-section" selected="false">Yes</option>
<option value="no-display-three" selected="true">No</option>
</parameter>
        <parameter name="sec-three-color" group="Everyone" type="select" prompt="Section three Color" alt="Section three Background Color?">
<option value="white-background" selected="false">White</option>
<option value="grey-background" selected="true">Grey</option>
</parameter>
        <parameter name="sec-three-width" group="Everyone" type="select" prompt="Section three Width" alt="Section three Width?">
<option value="add-edit-section edit-section-three wide" selected="true">Wide</option>
<option value="add-edit-section edit-section-three narrow" selected="false">Narrow</option>
</parameter>
    </ouc:properties>

	<ouc:properties label="metadata">
		<title>{$program_title_encoded}</title>
		<meta name="Description" content="CCRI is the largest public institution of higher education in the state and has been a leader in education, training and ensuring student success since 1964." />
		
	</ouc:properties>
<ouc:div label="header-paragraph" group="Everyone" button-text="Header Paragraph" break="true"><ouc:editor toolbar="Heading Paragraph" csspath="/_resources-2025/ou/editor/wysiwyg-2025.css" cssmenu="/_resources-2025/ou/editor/styles-2025.txt" wysiwyg-class="maincontent"/></ouc:div>
	
	
	

	<ouc:div label="catalog-description" >
<!-- Content loaded dynamically from catalog via fetch-program-content.php -->
</ouc:div>
	
	

	<ouc:div label="main-content" group="Everyone" button-text="Menu Section Content" break="true"><ouc:editor csspath="/_resources-2025/ou/editor/wysiwyg-2025.css" cssmenu="/_resources-2025/ou/editor/styles-2025.txt" wysiwyg-class="maincontent"/>{$widget_code}</ouc:div>

	
	
	<ouc:div label="edit-section-one" group="Everyone" button-text="Edit Section One" break="true"><ouc:editor csspath="/_resources-2025/ou/editor/wysiwyg-2025.css" cssmenu="/_resources-2025/ou/editor/styles-2025.txt" wysiwyg-class="maincontent"/>
	
	
	
	</ouc:div>
	
	
		<ouc:div label="edit-section-two" group="Everyone" button-text="Edit Section Two" break="true"><ouc:editor csspath="/_resources-2025/ou/editor/wysiwyg-2025.css" cssmenu="/_resources-2025/ou/editor/styles-2025.txt" wysiwyg-class="maincontent"/></ouc:div>
	<ouc:div label="edit-section-three" group="Everyone" button-text="Edit Section Three" break="true"><ouc:editor csspath="/_resources-2025/ou/editor/wysiwyg-2025.css" cssmenu="/_resources-2025/ou/editor/styles-2025.txt" wysiwyg-class="maincontent"/></ouc:div>

</document>
XML;

    file_put_contents($OUTPUT_DIR . '/index.pcf', $index_content);
    
    echo "  ✓ Generated files in: {$program_id}/\n";
    echo "    Department: {$department_name}\n\n";
    
    $stats['success']++;
    $stats['types'][$program['type']]++;
}

// Print final stats
echo "\n=====================================\n";
echo "   GENERATION COMPLETE\n";
echo "=====================================\n\n";

echo "Statistics:\n";
echo "  Total programs: {$stats['total']}\n";
echo "  ✓ Successfully generated: {$stats['success']}\n";
echo "  ✗ Failed: {$stats['failed']}\n";
echo "  ⚠ Skipped: {$stats['skipped']}\n\n";

echo "By Type:\n";
echo "  Associate Degrees: {$stats['types']['assoc']}\n";
echo "  Certificates: {$stats['types']['cert']}\n";
echo "  Diplomas: {$stats['types']['dipl']}\n\n";

echo "Output Location:\n";
echo "  {$OUTPUT_BASE_DIR}\n\n";

echo "Next Steps:\n";
echo "1. Review the generated files in: {$OUTPUT_BASE_DIR}\n";
echo "2. Upload each program folder to Modern Campus at /programs/{program-id}/\n";
echo "3. Manually fill in department info for programs with blank departments (~41)\n";
echo "4. Publish pages in Modern Campus\n";
echo "5. Add job widgets manually as needed\n\n";

echo "The directory cards will automatically link to these pages!\n\n";
