<?php
/**
 * Generate Program Pages - DYNAMIC CONTENT VERSION
 * 
 * Creates PCF files with structure and properties only
 * Content is fetched dynamically from catalog via fetch-program-content.php
 */

require_once 'department-urls.php';

// Configuration
$CATALOG_BASE = 'https://catalog.ccri.edu';
$PROGRAM_SLUG = 'journalism-aa';
$PROGRAM_CATALOG_PATH = '/programs-study/communication-film/assoc/journalism-aa/';

// Output directory
$OUTPUT_DIR = __DIR__ . '/generated-program';

echo "=== CCRI Program Page Generator (DYNAMIC VERSION) ===\n\n";
echo "Generating program: {$PROGRAM_SLUG}\n";
echo "Catalog path: {$PROGRAM_CATALOG_PATH}\n\n";

// Create output directory
if (!file_exists($OUTPUT_DIR)) {
    mkdir($OUTPUT_DIR, 0755, true);
    echo "Created output directory: {$OUTPUT_DIR}\n";
}

// Fetch program list to get department info
echo "Fetching program list to get department info...\n";
$program_list_url = 'https://www.ccri.edu/_resources-2025/php/program-list.php?v=4';
$programs_json = @file_get_contents($program_list_url);

if ($programs_json === false) {
    die("ERROR: Could not fetch program list from {$program_list_url}\n");
}

$all_programs = json_decode($programs_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: Could not parse program list JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "First 500 chars of response:\n";
    echo substr($programs_json, 0, 500) . "\n";
    die();
}

// Find this program in the list
$program_info = null;
foreach ($all_programs as $program) {
    if ($program['url'] === $PROGRAM_CATALOG_PATH) {
        $program_info = $program;
        break;
    }
}

if (!$program_info) {
    echo "ERROR: Could not find program with URL {$PROGRAM_CATALOG_PATH} in program list\n";
    echo "Available programs (first 10):\n";
    for ($i = 0; $i < min(10, count($all_programs)); $i++) {
        echo "  - {$all_programs[$i]['url']}\n";
    }
    die("\nPlease check the PROGRAM_CATALOG_PATH matches exactly.\n");
}

$department_name_clean = $program_info['department'] ?? '';

if (empty($department_name_clean)) {
    // Try to extract from title (format: "Department Name, Program Name - Type")
    $title_parts = explode(',', $program_info['title']);
    if (count($title_parts) >= 2) {
        $department_name_clean = trim($title_parts[0]);
        echo "✓ Extracted department from title: {$department_name_clean}\n";
    } else {
        // Fallback: Map URL pathway to department
        $url_to_dept = [
            'health-sciences' => 'Nursing',
            'nursing' => 'Nursing',
            'biology' => 'Biology',
            'business-administration' => 'Business & Professional Studies',
            'chemistry' => 'Chemistry',
            'communication-film' => 'Communication and Media',
            'computer-studies-information-processing' => 'Computer Science and Cybersecurity',
            'criminal-justice' => 'Social Sciences',
            'english' => 'English',
            'fine-arts' => 'Performing Arts',
            'general-studies' => 'English',
            'human-services' => 'Human Services',
            'liberal-arts' => 'English',
            'math' => 'Mathematics',
            'performing-arts' => 'Performing Arts',
            'physics-engineering' => 'Physics and Engineering',
            'professional-studies' => 'Business & Professional Studies',
            'psychology' => 'Psychology',
            'science' => 'Biology',
            'social-sciences' => 'Social Sciences',
            'technical-studies' => 'Physics and Engineering',
            'world-languages' => 'World Languages & Cultures',
            'alliedrehabhealth' => 'Allied and Rehabilitative Health Programs',
            'dental' => 'Dental Health Programs',
            'firescience' => 'Fire Science',
        ];
        
        // Extract pathway from catalog path
        $path_parts = explode('/', trim($PROGRAM_CATALOG_PATH, '/'));
        if (count($path_parts) >= 2) {
            $pathway = $path_parts[1]; // e.g., 'health-sciences' from /programs-study/health-sciences/assoc/...
            if (isset($url_to_dept[$pathway])) {
                $department_name_clean = $url_to_dept[$pathway];
                echo "✓ Used URL-based department mapping: {$department_name_clean}\n";
            }
        }
        
        if (empty($department_name_clean)) {
            echo "WARNING: No department found in program data, title, or URL mapping\n";
            echo "Program info: " . print_r($program_info, true) . "\n";
            $department_name_clean = 'Unknown Department';
        }
    }
} else {
    echo "✓ Found department from program list: {$department_name_clean}\n";
}

// Normalize department name using mapping (if exists)
if (isset($DEPARTMENT_NAME_MAP[$department_name_clean])) {
    echo "  → Normalized '{$department_name_clean}' to '{$DEPARTMENT_NAME_MAP[$department_name_clean]}'\n";
    $department_name_clean = $DEPARTMENT_NAME_MAP[$department_name_clean];
}

// Fetch program XML to get title
$program_xml_url = $CATALOG_BASE . $PROGRAM_CATALOG_PATH . 'index.xml';
echo "Fetching program info: {$program_xml_url}\n";

$xml_content = @file_get_contents($program_xml_url);
if ($xml_content === false) {
    die("ERROR: Could not fetch program XML from {$program_xml_url}\n");
}

echo "✓ Fetched program XML\n";

// Parse XML
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xml_content);
if ($xml === false) {
    echo "ERROR: Could not parse XML\n";
    foreach(libxml_get_errors() as $error) {
        echo "  - {$error->message}\n";
    }
    die();
}

echo "✓ Parsed XML successfully\n";

// Extract program title
$program_title = (string)$xml->title;
echo "  Title: {$program_title}\n\n";

// XML-encode all values to ensure strict XML compliance
$program_title_encoded = htmlspecialchars($program_title, ENT_XML1, 'UTF-8');

// Department info - look up from mapping using department from program list
$department_name = $department_name_clean;
$department_name_encoded = htmlspecialchars($department_name, ENT_XML1, 'UTF-8');

// Look up URLs from mapping
if (isset($DEPARTMENT_URLS[$department_name_clean])) {
    $department_link = $DEPARTMENT_URLS[$department_name_clean]['main'];
    $department_contact = $DEPARTMENT_URLS[$department_name_clean]['contact'];
    echo "✓ Found department URLs in mapping\n";
} else {
    echo "⚠ Department not found in mapping, using defaults\n";
    $department_link = "/";
    $department_contact = "/";
}

// Encode catalog path
$catalog_path_encoded = htmlspecialchars($PROGRAM_CATALOG_PATH, ENT_XML1, 'UTF-8');

// Widget code - removed, will be added manually later
$widget_code = '';

// Generate _props.pcf
$props_content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<?pcf-stylesheet path="/_resources-2025/xsl/properties.xsl" title="Properties" extension="html"?>
<!DOCTYPE document SYSTEM "http://commons.omniupdate.com/dtd/standard.dtd"> 
<document xmlns:ouc="http://omniupdate.com/XSL/Variables">
	<ouc:info><tcf>section.tcf</tcf><tmpl>properties.tmpl</tmpl></ouc:info>
	<ouc:properties label="config">
		<parameter name="breadcrumb" type="text" group="Everyone" prompt="Section Title" alt="Enter the friendly name for the section's breadcrumb.">{$program_title_encoded}</parameter>
	</ouc:properties>	
</document>
XML;

file_put_contents($OUTPUT_DIR . '/_props.pcf', $props_content);
echo "✓ Generated _props.pcf\n";

// Generate index.pcf - DYNAMIC VERSION (no catalog content in file)
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
echo "✓ Generated index.pcf (DYNAMIC VERSION - no catalog content)\n\n";

echo "=== GENERATION COMPLETE ===\n";
echo "Files created in: {$OUTPUT_DIR}\n";
echo "  - _props.pcf\n";
echo "  - index.pcf (catalog content loaded dynamically)\n\n";
echo "Next steps:\n";
echo "1. Upload fetch-program-content.php to /_resources-2025/php/\n";
echo "2. Update XSL template (see XSL-INTEGRATION.md)\n";
echo "3. Upload these PCF files to Modern Campus\n";
echo "4. Test the page - content should load from catalog\n\n";
