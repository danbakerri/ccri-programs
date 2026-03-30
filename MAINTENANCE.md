# CCRI Program Pages - Maintenance Guide

Quick reference guide for ongoing maintenance of the dynamic program pages system.

## Monthly Monitoring Checklist

### Run the Monitor (First Monday of Each Month)

```bash
cd /path/to/ccri-program-pages
php monitor-programs.php
```

### Review the Results

#### ✅ No Changes
```
✓ NO CHANGES DETECTED
  All programs match the baseline.
```
**Action:** Nothing needed - you're done!

#### ⚠️ Changes Detected
Follow the appropriate workflow below based on what changed.

---

## Workflow: New Programs Added

### Step 1: Review New Programs
Monitor shows:
```
📌 NEW PROGRAMS (2):
  • Digital Marketing - Certificate
    Generate: php generate-program-dynamic.php /programs-study/...
```

### Step 2: Generate PCF Files
Run the command shown for each new program:
```bash
php generate-program-dynamic.php /programs-study/business/cert/digital-marketing-certificate/
```

Output created in local directory (e.g., `digital-marketing-certificate/`)

### Step 3: Upload to Modern Campus
1. Connect to server: `smb://campus.ccri.edu/dfs01/Web/ccri_edu/webserver_www`
2. Navigate to `/programs/`
3. Upload the new program folder
4. Publish the page in Modern Campus

### Step 4: Update Baseline
```bash
php monitor-programs.php
```
Type **y** when prompted to update baseline

---

## Workflow: Programs Removed

### Step 1: Review Removed Programs
Monitor shows:
```
🗑️  REMOVED PROGRAMS (1):
  • Old Program Name
    Modern Campus path: /programs/old-program-slug/
```

### Step 2: Choose Action

**Option A: Delete Completely**
1. Log into Modern Campus
2. Navigate to `/programs/old-program-slug/`
3. Delete the entire folder
4. Publish

**Option B: Mark as Discontinued**
1. Log into Modern Campus
2. Open `/programs/old-program-slug/index.pcf`
3. Edit properties:
   - Change `display-catalog-description` to **"No"**
4. Edit main-content section:
   - Add message: "This program has been discontinued. Please contact [department] for alternatives."
5. Publish

### Step 3: Update Baseline
```bash
php monitor-programs.php
```
Type **y** when prompted

---

## Workflow: URL Changes

### Step 1: Review URL Changes
Monitor shows:
```
⚠️  URL CHANGED (1):
  • Cybersecurity - AS
    Old URL: /programs-study/old-path/...
    New URL: /programs-study/new-path/...
    Modern Campus: Edit /programs/cybersecurity-as/ properties
```

### Step 2: Update Page Properties
1. Log into Modern Campus
2. Open `/programs/cybersecurity-as/index.pcf`
3. Edit properties
4. Find `catalog-url` parameter
5. Update with new URL: `/programs-study/new-path/...`
6. Save and publish

### Step 3: Test
Visit the program page and verify content loads correctly

### Step 4: Update Baseline
```bash
php monitor-programs.php
```
Type **y** when prompted

---

## Workflow: Title Changes

### Step 1: Review Title Changes
Monitor shows:
```
📝 TITLE CHANGED (1):
  • journalism-aa
    Old Title: Journalism - Associate in Arts
    New Title: Journalism and Media - Associate in Arts
```

### Step 2: Update Page Properties
1. Log into Modern Campus
2. Open `/programs/journalism-aa/index.pcf`
3. Edit properties
4. Update `heading` parameter with new title
5. Save

### Step 3: Update Breadcrumb (if needed)
1. Open `/programs/journalism-aa/_props.pcf`
2. Edit `nav-heading` parameter if it needs to match
3. Save

### Step 4: Publish
Publish both files

### Step 5: Update Baseline
```bash
php monitor-programs.php
```
Type **y** when prompted

---

## Testing After Changes

### Test Individual Program Page
1. Visit: `https://www.ccri.edu/programs/{program-slug}/`
2. Verify content loads (requirements, outcomes, sequences)
3. Check breadcrumb shows correct title
4. Verify department links work

### Test Cache Status
Visit PHP directly:
```
https://www.ccri.edu/_resources-2025/php/fetch-program-content.php?url=/programs-study/.../
```

Check for cache comment:
- `<!-- FRESH FETCH -->` - First load, just cached
- `<!-- CACHE HIT -->` - Serving from cache (good!)

### Test Directory Page
1. Visit: `https://www.ccri.edu/programs/program-test.html`
2. Verify new programs appear in filters
3. Click card - should link to correct page

---

## Troubleshooting Common Issues

### Issue: Program Content Not Loading

**Check 1: Page Properties**
- Is `display-catalog-description` set to "Yes"?
- Is `catalog-url` parameter correct?

**Check 2: Cache**
- Clear cache on server: Delete files in `/_resources-2025/php/cache/`
- Refresh program page

**Check 3: Catalog URL**
- Visit catalog URL directly: `https://catalog.ccri.edu{catalog-url}`
- Does it exist? Or 404?

### Issue: Cache Not Working

**Symptoms:** Always shows `FRESH FETCH`, never `CACHE HIT`

**Fix:**
Contact IT to check permissions on:
```
/_resources-2025/php/cache/
```

Should be writable by web server (IUSR or IIS_IUSRS)

### Issue: Baseline Shows Wrong Count

**Fix:** Reset baseline
```bash
rm program-baseline.json
php monitor-programs.php
```

This creates fresh baseline from current catalog

### Issue: Department Shows Blank

**Cause:** Program title doesn't follow standard format

**Fix:** Manually edit in Modern Campus
1. Open program page properties
2. Update `department-name` parameter
3. Update `department-link` parameter
4. Update `contact-department-link` parameter
5. Publish

---

## Emergency Procedures

### Catalog is Down / Unavailable

**What Happens:**
- Fresh cache (< 6 hours) continues to work
- Expired cache (> 6 hours) falls back to stale cache (up to 7 days)
- After 7 days: Error message shown to users

**Action:**
1. Monitor catalog status
2. If down < 7 days: Wait for restoration (stale cache covers you)
3. If down > 7 days: Temporarily disable dynamic content:
   - Edit program pages in Modern Campus
   - Change `display-catalog-description` to "No"
   - Add static content to `catalog-description` section
   - Re-enable when catalog restored

### All Program Pages Broken

**Likely Cause:** PHP file issue on server

**Check:**
1. Verify files exist: `/_resources-2025/php/fetch-program-content.php`
2. Test PHP directly: Visit fetch-program-content.php URL
3. Check server error logs

**Temporary Fix:**
Disable dynamic content site-wide until issue resolved

### Mass URL Change (Catalog Restructure)

**Scenario:** Catalog reorganizes entire structure

**Action:**
1. Get list of all URL changes from catalog team
2. Create update script or manually update each:
   ```bash
   # For each affected program:
   # 1. Edit page in Modern Campus
   # 2. Update catalog-url parameter
   # 3. Publish
   ```
3. After all updates, refresh baseline:
   ```bash
   rm program-baseline.json
   php monitor-programs.php
   ```

---

## Regular Maintenance Tasks

### Weekly
- None required (system is automated)

### Monthly
- Run monitoring script
- Process any changes
- Update baseline

### Quarterly
- Review cache hit rate (check cache files on server)
- Review error logs for any fetch failures
- Verify all department links still valid

### Annually
- Review department mappings in `department-urls.php`
- Update if departments change
- Re-upload `department-urls.php` to server
- Regenerate all programs if needed:
  ```bash
  php generate-all-programs.php
  ```

---

## Updating the System

### Update PHP Files on Server

When you update:
- `fetch-program-content.php`
- `program-list.php`
- `department-urls.php`

1. Upload new version to `/_resources-2025/php/`
2. Clear cache: Delete files in `/_resources-2025/php/cache/`
3. Test with direct URL access
4. Verify program pages load correctly

### Update Generation Scripts

When you update:
- `generate-all-programs.php`
- `generate-program-dynamic.php`
- `monitor-programs.php`

1. Commit changes to GitHub
2. Pull latest on your local machine
3. Run monitor to verify: `php monitor-programs.php`

### Update XSL Template

When you update `program.xsl`:

1. Make changes in Modern Campus
2. Test on a single program page
3. Publish globally
4. Clear Modern Campus cache if needed

---

## Contact & Support

### Internal Team
- **Web Developer:** Primary contact for system issues
- **IT Department:** Server access, permissions, cache directory
- **Catalog Team:** Notify of catalog structure changes

### External Resources
- GitHub Repository: [URL when published]
- Documentation: README.md, XSL-INTEGRATION.md
- Server Files: `/_resources-2025/php/` on campus.ccri.edu

---

## Quick Command Reference

```bash
# Monthly monitoring
php monitor-programs.php

# Generate single new program
php generate-program-dynamic.php /programs-study/path/to/program/

# Regenerate all programs (rarely needed)
php generate-all-programs.php

# Reset baseline
rm program-baseline.json && php monitor-programs.php

# Test program list
curl https://www.ccri.edu/_resources-2025/php/program-list.php?v=7

# Test individual program
curl "https://www.ccri.edu/_resources-2025/php/fetch-program-content.php?url=/programs-study/path/"
```

---

## Change Log Template

Keep a log of changes you make:

```
## [Date] - [Your Name]

### Changed
- List what changed

### Added  
- New programs added

### Removed
- Programs removed

### Fixed
- Issues fixed
```
