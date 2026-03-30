# JavaScript Files - Program Directory & Pages

JavaScript files that power the program directory page filtering and individual program page label transformations.

## Files

### program-config.js
**Purpose:** Centralized configuration for both directory and individual pages  
**Upload to:** `/_resources-2025/js/program-config.js`  
**Used by:** Directory page, individual program pages

**What it contains:**
- `PATHWAY_MAP` - Maps catalog pathway names to display names
- `PROGRAM_TYPE_LABELS` - Maps program types to friendly labels
- `HIDDEN_PROGRAMS` - List of programs to hide from directory
- `PATHWAY_ORDER` - Controls order of pathways in filter dropdown
- `normPathway()` - Helper function to normalize pathway names
- Auto-transformation code for individual program pages

### program-directory.js
**Purpose:** Directory page filtering, searching, and pagination  
**Upload to:** `/_resources-2025/js/program-directory.js`  
**Requires:** program-config.js (must be loaded first)  
**Used by:** Main program directory page only

**What it does:**
- Builds filter dropdowns from program data
- Handles search, pathway, type, A-Z, and workforce category filtering
- Manages pagination (24 programs per page)
- URL state management (filters persist in URL)
- Active filter chips display
- Letter dividers (A, B, C, etc.)

## How They Work Together

```
Directory Page HTML
├── <script src="/_resources-2025/js/program-config.js"></script>  ← Loaded first
└── <script src="/_resources-2025/js/program-directory.js"></script> ← Uses config

Individual Program Page HTML
└── <script src="/_resources-2025/js/program-config.js"></script>  ← Auto-transforms labels
```

## Configuration Guide

### Adding/Changing Pathway Names

Edit `PATHWAY_MAP` in program-config.js:

```javascript
const PATHWAY_MAP = {
  'Arts and Humanities': 'Arts & Humanities',  // Catalog name: Display name
  'New Pathway Name': 'Friendly Display Name'
};
```

### Changing Program Type Labels

Edit `PROGRAM_TYPE_LABELS` in program-config.js:

```javascript
const PROGRAM_TYPE_LABELS = {
  'assoc': 'Associate Degree',
  'cert': 'Certificate',
  'dipl': 'Diploma',
  'transfer': 'Transfer Program',
  'workforce': 'Workforce Partnerships (Non-Degree)'
};
```

### Hiding Specific Programs

Add catalog URLs to `HIDDEN_PROGRAMS`:

```javascript
const HIDDEN_PROGRAMS = [
  '/programs-study/old-program/assoc/discontinued-program/',
  '/programs-study/another/cert/hidden-cert/'
];
```

Programs in this list won't appear in the directory at all.

### Reordering Pathway Filter

Edit `PATHWAY_ORDER` to control dropdown order:

```javascript
const PATHWAY_ORDER = [
  'Arts & Humanities',
  'Business, Economics & Data Analytics',
  // ... order matters!
];
```

**Note:** Pathways not in this list still appear, just after the ordered ones.

## Directory Page Requirements

The directory HTML must have these elements for the JavaScript to work:

```html
<!-- Filters -->
<select id="pathwayF"></select>      <!-- Pathway dropdown -->
<select id="typeF"></select>         <!-- Type dropdown -->
<select id="azF"></select>           <!-- A-Z dropdown -->
<select id="workforceCategoryF"></select>  <!-- Workforce category -->
<input id="q" type="text">           <!-- Search input -->

<!-- Display -->
<div id="grid">                      <!-- Card container -->
  <div class="program-card" 
       data-pathway="..." 
       data-type="..."
       data-letter="..."
       data-url="..."
       data-search="...">
    <div class="program-title">...</div>
    <div class="program-type-tag">...</div>
    <div class="program-pathway-tag">...</div>
  </div>
</div>

<!-- Controls -->
<span id="cnt">0</span>              <!-- Filtered count -->
<span id="total">0</span>            <!-- Total count -->
<div id="chips"></div>               <!-- Active filters -->
<button id="clearBtn"></button>      <!-- Clear all filters -->
<div id="pager"></div>               <!-- Pagination -->
<div id="empty" style="display:none">No programs found</div>
```

## Individual Program Page Requirements

Individual program pages just need these elements:

```html
<span class="program-type-tag">assoc</span>
<span class="program-pathway-tag">Arts and Humanities</span>
```

The config script will automatically transform these on page load:
- `assoc` → `Associate Degree`
- `Arts and Humanities` → `Arts & Humanities`

## Workflow Categories

The directory supports filtering workforce programs by category:

```javascript
const workforceCategories = [
  'Business and Technology',
  'Education',
  'Healthcare',
  'Manufacturing and Trades',
  'Renewable Energy',
  'Fit2Serve',
  'GED & Adult Education',
  'Transportation Education'
];
```

These are hardcoded in `program-directory.js` (line 166).

To add/remove categories, edit that array.

## Features

### Search
- Searches program titles and descriptions
- Case-insensitive
- Updates count in real-time

### Filters
- **Pathway** - Filter by academic pathway
- **Type** - Filter by Associate, Certificate, Diploma, etc.
- **A-Z** - Filter by first letter
- **Workforce Category** - Filter workforce programs by category
- All filters can be combined

### Pagination
- 24 programs per page (configurable via `PER_PAGE` constant)
- Window-based page numbers (shows 5 pages at a time)
- Prev/Next buttons
- Shows "X–Y of Z" count

### URL State
- Filters and page persist in URL
- Shareable URLs with active filters
- Browser back/forward support

### Active Filter Chips
- Visual display of active filters
- Click X to remove individual filter
- "Clear all" button to reset everything

### Letter Dividers
- Automatic A, B, C dividers between programs
- Only shows when programs exist for that letter

## Customization

### Change Programs Per Page

Edit `PER_PAGE` constant in program-directory.js:

```javascript
const PER_PAGE = 24;  // Change to 12, 36, 48, etc.
```

### Change Pagination Window

Edit `WIN` constant in renderPager() function:

```javascript
const WIN = 5;  // Shows 5 page numbers at a time
```

### Disable Specific Filters

Comment out or remove unwanted filter code:

```javascript
// To remove workforce category filter:
// Comment out lines building workforceCategorySel
```

## Testing

### Test Configuration Changes

1. Edit program-config.js
2. Upload to `/_resources-2025/js/`
3. Hard refresh directory page (Ctrl+F5 or Cmd+Shift+R)
4. Verify pathway/type labels updated

### Test Directory Functionality

1. Visit directory page
2. Try each filter
3. Try search
4. Try pagination
5. Try combining filters
6. Test browser back button
7. Share URL with filters - verify it loads correctly

### Test Individual Pages

1. Visit any program page
2. View page source
3. Find `.program-type-tag` and `.program-pathway-tag`
4. Verify labels are transformed (not raw values)

## Troubleshooting

### Filters Not Showing Options

**Cause:** No programs match that filter  
**Fix:** Check that programs have correct data attributes

### Search Not Working

**Cause:** Missing `data-search` attribute  
**Fix:** Ensure XSL adds searchable text to data-search

### Pagination Broken

**Cause:** Missing page elements (grid, pager, etc.)  
**Fix:** Check HTML has all required IDs

### Labels Not Transforming

**Cause:** program-config.js not loaded  
**Fix:** Check script tag order - config must load first

### Console Errors

Check browser console (F12) for JavaScript errors. Common issues:
- Missing HTML elements (getElementById returns null)
- Script load order (directory.js before config.js)
- Typos in data attributes

## File Dependencies

```
program-config.js (standalone - no dependencies)
├── Used by directory page
└── Used by individual program pages

program-directory.js
├── Requires: program-config.js
├── Requires: HTML with specific element IDs
└── Used by: Directory page only
```

## Maintenance

### When to Update

**program-config.js:**
- New pathways added to catalog
- Pathway names change
- Need to hide programs
- Want to change display labels

**program-directory.js:**
- Need to change pagination
- Add new filter types
- Change filter behavior
- Fix bugs

### Version Control

Both files are in the GitHub repository at:
```
ccri-program-pages/
└── javascript/
    ├── program-config.js
    └── program-directory.js
```

Track changes in Git and upload to server after edits.

## Related Documentation

- **README.md** - Main system documentation
- **XSL-INTEGRATION.md** - How directory page XSL works
- **MAINTENANCE.md** - Ongoing system maintenance

## Support

For issues with:
- **Filtering** - Check program-directory.js
- **Labels** - Check program-config.js and PATHWAY_MAP
- **Missing programs** - Check HIDDEN_PROGRAMS list
- **Directory not loading** - Check HTML element IDs
