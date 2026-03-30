# Quick Start Guide

Get up and running with the CCRI Program Pages system in 5 minutes.

## Prerequisites

- PHP installed locally (Mac: pre-installed, Windows: download from php.net)
- Access to CCRI server via SMB
- Modern Campus CMS access

## Step 1: Clone Repository

```bash
git clone [repository-url]
cd ccri-program-pages
```

## Step 2: Upload Server Files

Connect to server:
```
smb://campus.ccri.edu/dfs01/Web/ccri_edu/webserver_www
```

**Upload PHP files** to `/_resources-2025/php/`:
- `fetch-program-content.php`
- `program-list.php`
- `department-urls.php`

**Upload JavaScript files** to `/_resources-2025/js/`:
- `program-config.js`
- `program-directory.js`

## Step 3: Create Cache Directory

Ask IT to create and set permissions:
```
/_resources-2025/php/cache/
```

Permissions: writable by web server (IUSR or IIS_IUSRS)

## Step 4: Test Server Setup

Visit in browser:
```
https://www.ccri.edu/_resources-2025/php/program-list.php?v=7
```

Should return JSON with programs list.

Visit:
```
https://www.ccri.edu/_resources-2025/php/fetch-program-content.php?url=/programs-study/communication-film/assoc/journalism-aa/
```

Should return XML with program content.

## Step 5: Generate Programs

Run bulk generator:
```bash
php generate-all-programs.php
```

Wait ~5 minutes. Creates `bulk-output/` with ~98 program folders.

## Step 6: Upload to Modern Campus

Via SMB, upload each folder from `bulk-output/` to `/programs/` in Modern Campus.

Example:
```
bulk-output/journalism-aa/ → /programs/journalism-aa/
```

Publish each page in Modern Campus.

## Step 7: Set Up Monitoring

Create initial baseline:
```bash
php monitor-programs.php
```

This saves current state in `program-baseline.json`.

## You're Done!

### What You Have Now:
- ✅ 98 program pages with dynamic content
- ✅ Content updates automatically from catalog
- ✅ 6-hour cache for performance
- ✅ Monitoring system for changes

### Monthly Maintenance:
```bash
php monitor-programs.php
```

Follow prompts to handle any changes.

### Need Help?
- Read: `README.md` for full documentation
- Read: `MAINTENANCE.md` for detailed workflows
- Read: `XSL-INTEGRATION.md` for template details

## Common First-Time Issues

### "Permission denied" when generating
```bash
chmod +x generate-all-programs.php
php generate-all-programs.php
```

### Cache not working
Ask IT to set permissions on cache directory.

### Content not showing on pages
Check XSL template has `document()` function integration.
See `XSL-INTEGRATION.md`.

### Programs show "Unknown Department"
Normal for ~41 programs. Manually update department info in Modern Campus after upload.

## Next Steps

1. **Test a few program pages** - Make sure content loads
2. **Fill in blank departments** - Update the ~41 programs manually
3. **Customize as needed** - Add header images, job widgets, etc.
4. **Set up monthly monitoring** - Add to calendar

For detailed instructions, see `MAINTENANCE.md`.
