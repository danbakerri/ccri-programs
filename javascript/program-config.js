/**
 * Program Directory & Individual Program Page Configuration
 * Shared between directory page and individual program pages
 * Update this file once to affect both pages
 */

/*
 * PATHWAY_MAP — rename or merge pathway categories
 * Key = exact value from the catalog
 * Value = the display name you want
 */
const PATHWAY_MAP = {
  'Arts and Humanities': 'Arts & Humanities',
  'Business Economics and Data Analytics': 'Business, Economics & Data Analytics',
  'Communication, Media and Film': 'Communication, Media, and Film',
  'Education, Government, and Human Services': 'Education, Government, and Human Services',
  'Environment and Sustainability': 'Environment & Sustainability',
  'Health and Health Administration': 'Health and Health Administration',
  'Science, Technology, Engineering, and Mathematics': 'Science, Technology, Engineering, and Math',
  'Workforce Partnerships': 'Workforce Partnerships (Non-Degree)'
};

/* Helper function: resolve a raw pathway to its display name */
function normPathway(raw) {
  return PATHWAY_MAP[raw] || raw;
}

/*
 * PROGRAM_TYPE_LABELS — friendly display names for program types
 */
const PROGRAM_TYPE_LABELS = {
  'assoc': 'Associate Degree',
  'transfer': 'Transfer Program',
  'cert': 'Certificate',
  'dipl': 'Diploma',
  'workforce': 'Workforce Partnerships (Non-Degree)'
};

/*
 * HIDDEN_PROGRAMS — completely hide specific programs from the directory
 * Use the program URL from the catalog
 */
const HIDDEN_PROGRAMS = [
  // Add URLs here to hide programs, e.g.:
  // '/programs-study/old-program/assoc/discontinued-program/',
];

/*
 * PATHWAY_ORDER — control the order pathways appear in filters
 * Programs will be grouped in this order
 * NOTE: "Workforce Partnerships" is intentionally excluded from this list
 * so it does NOT appear in the pathway filter dropdown
 */
const PATHWAY_ORDER = [
  'Arts & Humanities',
  'Business, Economics & Data Analytics',
  'Communication, Media, and Film',
  'Education, Government, and Human Services',
  'Environment & Sustainability',
  'Health and Health Administration',
  'Science, Technology, Engineering, and Math',
  'Workforce Partnerships (Non-Degree)'
];

// ═══════════════════════════════════════════════════════════════════
// INDIVIDUAL PROGRAM PAGE AUTO-TRANSFORMATION
// Automatically runs on program pages to transform labels
// ═══════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function() {
  // Check if we're on a program page (has .program-type-tag)
  const typeTag = document.querySelector('.program-type-tag');
  
  if (typeTag) {
    const rawType = typeTag.textContent.trim();
    const mappedType = PROGRAM_TYPE_LABELS[rawType] || rawType;
    typeTag.textContent = mappedType;
  }
  
  // Transform pathway labels if present
  const pathwayTag = document.querySelector('.program-pathway-tag');
  if (pathwayTag) {
    const rawPathway = pathwayTag.textContent.trim();
    const mappedPathway = normPathway(rawPathway);
    pathwayTag.textContent = mappedPathway;
  }
});
