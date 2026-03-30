# CCRI Dynamic Program Pages System

Automated system for generating and maintaining 100+ academic program pages with dynamic content from the CourseLeaf catalog.

## Overview

This system automatically generates Modern Campus PCF files for CCRI's academic programs (Associate Degrees, Certificates, Diplomas) with content that updates dynamically from the CourseLeaf catalog. No more manual copy/paste - program requirements, learning outcomes, and course sequences stay current automatically.

## Features

- ✅ **Bulk generation** of 98+ program pages from catalog data
- ✅ **Dynamic content** that updates from live catalog (6-hour cache, 7-day stale fallback)
- ✅ **Department auto-mapping** from program titles to 19 CCRI departments
- ✅ **Change monitoring** - detects new programs, removed programs, URL changes, title changes
- ✅ **Strict XML compliance** with proper encoding for special characters
- ✅ **Simplified property templates** for easy customization in Modern Campus

## System Architecture

```
User visits program page
    ↓
XSL template checks display-catalog-description parameter
    ↓
If enabled: XSL calls document() function → fetch-program-content.php
    ↓
PHP checks cache (6hr fresh, 7 day stale fallback)
    ↓
If cache miss: Fetch from catalog, parse XML, format HTML, cache
    ↓
Return XML-wrapped content to XSL
    ↓
XSL renders content on page
```

## Files in This Repository

### Generation Scripts (Local)
- **`generate-all-programs.php`** - Bulk generate all academic programs at once
- **`generate-program-dynamic.php`** - Generate a single program
- **`monitor-programs.php`** - Monitor catalog for changes (new/removed/modified programs)

### Server Files (Upload to `/_resources-2025/php/`)
- **`fetch-program-content.php`** - Fetches and caches program content from catalog
- **`program-list.php`** - Generates JSON list of all programs for directory page
- **`department-urls.php`** - Department name normalization and URL mappings

### JavaScript Files (Upload to `/_resources-2025/js/`)
- **`program-config.js`** - Centralized configuration for pathways, types, hidden programs
- **`program-directory.js`** - Directory page filtering, search, and pagination

### Workforce Management (Upload to `/workforce/manage-programs/`)
- **`index.html`** - SSO-protected management interface
- **`save-program.php`** - Add/edit programs
- **`get-programs.php`** - Retrieve program list
- **`delete-program.php`** - Delete programs

### Configuration/Documentation
- **`README.md`** - This file
- **`QUICKSTART.md`** - 5-minute setup guide
- **`MAINTENANCE.md`** - Ongoing maintenance guide
- **`XSL-INTEGRATION.md`** - XSL template integration documentation
- **`FILES.md`** - Complete file inventory
- **`javascript/README.md`** - JavaScript configuration and usage guide

## Initial Setup

### 1. Server Setup

**Upload PHP files** to your web server at `/_resources-2025/php/`:
```
fetch-program-content.php
program-list.php
department-urls.php
```

**Upload JavaScript files** to `/_resources-2025/js/`:
```
program-config.js
program-directory.js
```

Create cache directory and set permissions:
```bash
mkdir -p /var/www/_resources-2025/php/cache
chmod 755 /var/www/_resources-2025/php/cache
chown www-data:www-data /var/www/_resources-2025/php/cache
```

### 2. XSL Template Integration

Update your `program.xsl` template to include the dynamic content loader. See `XSL-INTEGRATION.md` for detailed instructions.

Key changes needed:
- Add `document()` function call in catalog-description section
- Reference `fetch-program-content.php` with catalog URL parameter
- Ensure `display-catalog-description` parameter exists in properties

### 3. Generate Initial Programs

Run the bulk generator locally:
```bash
cd /path/to/this/repo
php generate-all-programs.php
```

This creates `bulk-output/` folder with ~98 program directories, each containing:
- `_props.pcf` - Breadcrumb properties
- `index.pcf` - Full page properties and structure

### 4. Upload to Modern Campus

Upload each program folder from `bulk-output/` to `/programs/` in Modern Campus:
```
bulk-output/journalism-aa/ → Upload to /programs/journalism-aa/
bulk-output/cybersecurity-as/ → Upload to /programs/cybersecurity-as/
... (repeat for all programs)
```

### 5. Set Up Monitoring Baseline

Create initial baseline for change monitoring:
```bash
php monitor-programs.php
```

This creates `program-baseline.json` with current catalog state.

## Ongoing Maintenance

### Monthly Monitoring

Run the monitoring script to check for catalog changes:
```bash
php monitor-programs.php
```

The script will show:
- 📌 New programs added to catalog
- 🗑️ Programs removed from catalog
- ⚠️ URL/path changes for existing programs
- 📝 Title changes for existing programs

### When New Programs Are Added

If monitoring shows new programs:

1. **Generate the new program(s):**
   ```bash
   php generate-program-dynamic.php /programs-study/path/to/new-program/
   ```

2. **Upload to Modern Campus:**
   - Upload generated folder to `/programs/{program-slug}/`
   - Publish the page

3. **Update baseline:**
   - Run `php monitor-programs.php` again
   - Type "y" when prompted to update baseline

### When Programs Are Removed

If monitoring shows removed programs:

**Option 1: Delete the page**
- Navigate to `/programs/{program-slug}/` in Modern Campus
- Delete the folder

**Option 2: Mark as discontinued**
- Edit the program page in Modern Campus
- Change `display-catalog-description` to "No"
- Add custom content explaining the program is discontinued

### When URLs Change

If monitoring shows URL changes:

1. Open the program page in Modern Campus
2. Edit page properties
3. Update the `catalog-url` parameter with new URL
4. Republish the page

### When Titles Change

If monitoring shows title changes:

1. Open the program page in Modern Campus
2. Edit page properties
3. Update the `heading` parameter with new title
4. Update `nav-heading` in `_props.pcf` if needed
5. Republish the page

## Testing the System

### Test Dynamic Content Loading

Visit a program page directly:
```
https://www.ccri.edu/programs/journalism-aa/
```

View page source - you should see catalog content (requirements, outcomes, sequences) loaded dynamically.

### Test Caching

Visit the PHP script directly:
```
https://www.ccri.edu/_resources-2025/php/fetch-program-content.php?url=/programs-study/communication-film/assoc/journalism-aa/
```

**First visit:** Should show `<!-- FRESH FETCH: Just fetched from catalog and cached -->`

**Refresh immediately:** Should show `<!-- CACHE HIT: Served from cache (X minutes old) -->`

### Test Program List

Visit the program list JSON:
```
https://www.ccri.edu/_resources-2025/php/program-list.php?v=7
```

Should return JSON with all programs including department mappings.

### Check Cache Files

On server, verify cache files exist:
```bash
ls -lah /var/www/_resources-2025/php/cache/
```

Should see files like:
```
program_a1b2c3d4e5f6.html  (individual program caches)
program-list.json           (directory cache)
```

## Troubleshooting

### Cache Not Working

**Symptom:** Always shows `FRESH FETCH` comment, never `CACHE HIT`

**Solution:** Check cache directory permissions
```bash
chmod 755 /var/www/_resources-2025/php/cache
chown www-data:www-data /var/www/_resources-2025/php/cache
```

### Program Content Not Showing

**Symptom:** Program page loads but catalog content is missing

**Causes:**
1. `display-catalog-description` parameter not set to "Yes"
2. `catalog-url` parameter incorrect or missing
3. XSL template not updated with `document()` function
4. PHP file not uploaded to server

**Debug:** Check page source for error messages in catalog-description section

### Department Shows "Unknown Department"

**Symptom:** Generated PCF has blank or "Unknown Department"

**Cause:** Program title doesn't follow "Name, Concentration - Type" format

**Solution:** Manually update department-name parameter in Modern Campus after upload

### Monitoring Shows Wrong Numbers

**Symptom:** `monitor-programs.php` shows unexpected new/removed counts

**Solution:** 
1. Delete `program-baseline.json`
2. Run `php monitor-programs.php` to create fresh baseline
3. This resets monitoring to current catalog state

## Cache Behavior

### Individual Program Pages
- **Fresh cache:** 6 hours
- **Stale cache fallback:** 7 days (used if catalog unavailable)
- **Cache location:** `/var/www/_resources-2025/php/cache/program_*.html`

### Program List (Directory)
- **Fresh cache:** 1 hour
- **Cache location:** `/var/www/_resources-2025/php/cache/program-list.json`

### Cache Comments
All cached content includes debug comments visible in page source:
- `<!-- FRESH FETCH: Just fetched from catalog and cached -->`
- `<!-- CACHE HIT: Served from cache (X minutes old) -->`
- `<!-- STALE CACHE: Catalog unavailable, using stale cache (X hours old) -->`

## Department Mappings

Programs are automatically mapped to 19 CCRI departments based on title parsing:

- Biology
- Business & Professional Studies
- Chemistry
- Communication and Media
- Computer Science and Cybersecurity
- English
- History and Political Science
- Human Services
- Languages
- Mathematics
- Performing Arts
- Philosophy and Sociology
- Physics and Engineering
- Psychology

Transfer programs (e.g., "Biology Transfer") automatically map to base department ("Biology").

Mappings defined in `department-urls.php`.

## Property Template Structure

Generated PCF files include these configurable parameters:

### Basic Settings
- Page heading (program title)
- Green first word toggle
- Gallery type

### Department Section
- Department name (auto-filled)
- Department link (file chooser)
- Contact department link (file chooser)
- Display catalog description toggle
- Catalog URL (auto-filled)

### Header Image
- Header image (file chooser with path)
- Image position (5 options)

### Additional Edit Sections
Three customizable sections with:
- Display toggle
- Background color (white/grey)
- Width (wide/narrow)

## Directory Page Integration

The main program directory page uses JavaScript for filtering and pagination.

### Required Script Tags

Add to your directory page HTML (in order):
```html
<script src="/_resources-2025/js/program-config.js"></script>
<script src="/_resources-2025/js/program-directory.js"></script>
```

### Configuration

Edit `program-config.js` to customize:
- **Pathway names** - Change display labels for pathways
- **Program type labels** - Customize type names
- **Hidden programs** - Hide specific programs from directory
- **Filter order** - Control pathway dropdown order

See `javascript/README.md` for complete configuration guide.

### Directory Features

- **Search** - Full-text search across program titles
- **Filters** - Pathway, type, A-Z, workforce category
- **Pagination** - 24 programs per page
- **URL state** - Filters persist in shareable URLs
- **Active chips** - Visual display of active filters
- **Letter dividers** - Automatic A, B, C section headers

The directory fetches program data from `program-list.php?v=7`.

**Update version number** in directory XSL when making changes:
```javascript
fetch('/_resources-2025/php/program-list.php?v=7')  // Increment to v=8, v=9, etc.
```

Increment version (v=8, v=9, etc.) to bypass cache after updates.

## Workforce Program Management

### Web Interface for Non-Catalog Programs

Workforce programs don't exist in the CourseLeaf catalog, so they need a separate management system. The workforce management interface allows authorized users to add, edit, and delete workforce programs through a web form.

### Features

- **Add/Edit/Delete** - Simple form interface
- **SSO Protected** - Secured by your existing authentication
- **Category Support** - Programs organized by workforce category
- **JSON Storage** - Simple file-based data storage
- **Auto-Integration** - Programs automatically appear in directory

### Setup

1. Upload files to `/workforce/manage-programs/`:
   ```
   index.html
   save-program.php
   get-programs.php
   delete-program.php
   ```

2. Set directory permissions for file creation

3. Configure SSO protection for the directory

4. Access at: `https://www.ccri.edu/workforce/manage-programs/`

See `workforce/README.md` for complete setup and usage guide.

## Technical Notes

### XML Encoding
All program titles, department names, and catalog URLs are XML-encoded with `htmlspecialchars()` using `ENT_XML1` flag to ensure strict XML compliance.

**Example:**
```
Business & Professional Studies → Business &amp; Professional Studies
```

### Catalog Structure
System expects CourseLeaf catalog structure:
```
https://catalog.ccri.edu/programs-study/{category}/{type}/{program-slug}/index.xml
```

### Modern Campus Structure
Generated pages expect this Modern Campus structure:
```
/programs/{program-slug}/
  _props.pcf
  index.pcf
```

### XSL Template
Uses `document()` function to fetch dynamic content:
```xml
<xsl:copy-of select="document(concat('https://www.ccri.edu/_resources-2025/php/fetch-program-content.php?url=', $catalog-url))/content/node()"/>
```

## Version History

### v1.0 (March 2026)
- Initial system deployment
- 98 academic programs generated
- Dynamic content loading implemented
- Cache system operational
- Monitoring tools created

## Support

For issues or questions:
1. Check `MAINTENANCE.md` for common tasks
2. Check `XSL-INTEGRATION.md` for template questions
3. Review troubleshooting section above
4. Contact web development team

## License

Internal CCRI tool - not licensed for external use.

## Credits

Developed for Community College of Rhode Island (CCRI)
March 2026
