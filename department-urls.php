<?php
/**
 * Department URL Mappings
 * Maps department names to their main page and contact page URLs
 */

$DEPARTMENT_URLS = [
    "Allied and Rehabilitative Health Programs" => [
        "main" => "/alliedrehabhealth/",
        "contact" => "/alliedrehabhealth/contact.html"
    ],
    "Art, Art History & Design" => [
        "main" => "/art/",
        "contact" => "/art/contact.html"
    ],
    "Biology" => [
        "main" => "/biology/",
        "contact" => "/biology/contact.html"
    ],
    "Business & Professional Studies" => [
        "main" => "/business/",
        "contact" => "/business/contact.html"
    ],
    "Chemistry" => [
        "main" => "/chemistry/",
        "contact" => "/chemistry/contact.html"
    ],
    "Communication and Media" => [
        "main" => "/cmmd/",
        "contact" => "/cmmd/contact.html"
    ],
    "Computer Science and Cybersecurity" => [
        "main" => "/comp/",
        "contact" => "/comp/contact.html"
    ],
    "Dental Health Programs" => [
        "main" => "/dental/",
        "contact" => "/dental/contact.html"
    ],
    "English" => [
        "main" => "/engl/",
        "contact" => "/engl/contact.html"
    ],
    "Fire Science" => [
        "main" => "/firescience/",
        "contact" => "/firescience/contact.html"
    ],
    "Human Services" => [
        "main" => "/hmns/",
        "contact" => "/hmns/contact.html"
    ],
    "Library" => [
        "main" => "/library/",
        "contact" => ""
    ],
    "Mathematics" => [
        "main" => "/math/",
        "contact" => "/math/contact.html"
    ],
    "Nursing" => [
        "main" => "/nursing/",
        "contact" => "/nursing/contact.html"
    ],
    "Performing Arts" => [
        "main" => "/performingarts/",
        "contact" => "/performingarts/contact.html"
    ],
    "Physics and Engineering" => [
        "main" => "/physandengr/",
        "contact" => "/physandengr/contact.html"
    ],
    "Psychology" => [
        "main" => "/psych/",
        "contact" => "/psych/contact.html"
    ],
    "Social Sciences" => [
        "main" => "/socsci/",
        "contact" => "/socsci/contact.html"
    ],
    "World Languages & Cultures" => [
        "main" => "/worldlang/",
        "contact" => "/worldlang/contact.html"
    ]
];

// Department code to name mapping (from Ribbit API)
$DEPARTMENT_CODE_MAP = [
    "CSIP" => "Computer Science and Cybersecurity",
    "BSAT" => "Business & Professional Studies",
    "CMMD" => "Communication and Media",
    "ART" => "Art, Art History & Design",
    "BIOL" => "Biology",
    "CHEM" => "Chemistry",
    "ENGL" => "English",
    "MATH" => "Mathematics",
    "NURS" => "Nursing",
    "HMNS" => "Human Services",
    "PSYC" => "Psychology",
    "SOSC" => "Social Sciences",
    "PERF" => "Performing Arts",
    "PHYS" => "Physics and Engineering",
    "WRLD" => "World Languages & Cultures",
    "DENT" => "Dental Health Programs",
    "FIRE" => "Fire Science",
    "ALLH" => "Allied and Rehabilitative Health Programs",
    // Add more as needed
];

// Department name normalization - map catalog names to actual department names
$DEPARTMENT_NAME_MAP = [
    // Map catalog department names to actual CCRI departments
    "Fine Arts" => "Performing Arts",
    "Administrative Office Technology" => "Business & Professional Studies",
    "Advanced Manufacturing and Design" => "Physics and Engineering",
    "Biology Transfer" => "Biology",
    "Biotechnology" => "Biology",
    "Business Administration" => "Business & Professional Studies",
    "Business Transfer" => "Business & Professional Studies",
    "Case Management" => "Human Services",
    "Chemical Technology" => "Chemistry",
    "Chemistry Transfer" => "Chemistry",
    "Communication and Media Transfer" => "Communication and Media",
    "Computed Tomography Imaging" => "Allied and Rehabilitative Health Programs",
    "Computer Studies" => "Computer Science and Cybersecurity",
    "Computer Studies and Information Processing" => "Computer Science and Cybersecurity",
    "Computer Studies Transfer" => "Computer Science and Cybersecurity",
    "Court Reporting" => "Business & Professional Studies",
    "Criminal Justice" => "Social Sciences",
    "Criminal Justice and Public Safety" => "Social Sciences",
    "Cyber Defense" => "Computer Science and Cybersecurity",
    "Cybersecurity" => "Computer Science and Cybersecurity",
    "Dental Assisting" => "Dental Health Programs",
    "Dental Hygiene" => "Dental Health Programs",
    "Diagnostic Medical Sonography" => "Allied and Rehabilitative Health Programs",
    "Early Childhood Education" => "Human Services",
    "Ecology and Environmental Biology" => "Biology",
    "Education" => "Human Services",
    "Engineering" => "Physics and Engineering",
    "English Transfer" => "English",
    "Environment" => "Biology",
    "Fine Arts Transfer" => "Performing Arts",
    "Fire Science" => "Fire Science",
    "General Studies" => "English",
    "Geographic Information Systems (
