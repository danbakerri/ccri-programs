# Workforce Program Management System

Simple web interface for managing workforce programs that appear in the main program directory.

## Overview

This system allows authorized users to add, edit, and delete workforce programs through a web interface. Programs are stored in a JSON file and automatically integrated into the main program directory.

## Files

| File | Purpose |
|------|---------|
| **index.html** | Main management interface (SSO-protected) |
| **save-program.php** | Handles adding/editing programs |
| **get-programs.php** | Retrieves program list for display |
| **delete-program.php** | Handles program deletion |
| **.htaccess** | Apache security config (blocks direct JSON access) |
| **web.config** | IIS security config (blocks direct JSON access) |
| **workforce-programs.json** | Data storage (auto-created) |
| **README.md** | This file |

## Installation

### 1. Upload Files

Upload to server at `/workforce/manage-programs/`:
```
/workforce/manage-programs/
  index.html
  save-program.php
  get-programs.php
  delete-program.php
```

### 2. Set Security

**For Apache servers:**
- Upload `.htaccess` to the same directory
- This blocks direct access to `.json` files

**For IIS servers:**
- Upload `web.config` to the same directory  
- This blocks direct access to `.json` files

**Why:** Prevents anyone from directly accessing `workforce-programs.json` via browser. Only PHP scripts can read it.

See `SECURITY.md` in main repository for complete security setup.

### 3. Set Permissions

The app needs to create/write `workforce-programs.json` in the same directory.

**Using ACLs (Windows Server - Recommended):**
```powershell
# Set ACL on the directory to allow IIS to create files
icacls "C:\path\to\workforce\manage-programs" /grant "IIS_IUSRS:(OI)(CI)M"
```

**Using chmod (Linux - if needed):**
```bash
chmod 755 /var/www/html/workforce/manage-programs
```

The PHP files use `umask(0000)` to create files with `666` permissions (readable/writable by all).

### 3. Protect with SSO

Add SSO protection to `/workforce/manage-programs/` directory.

**Example Apache:**
```apache
<Location "/workforce/manage-programs">
    # Your SSO directives
    Require valid-user
    # Optional: Restrict to specific group
    Require group ccri-web-team
</Location>
```

**Example IIS:**
Configure Windows Authentication for the `/workforce/manage-programs` directory.

### 4. Test

Visit: `https://www.ccri.edu/workforce/manage-programs/`

You should:
- See the management interface
- Be able to add a test program
- See `workforce-programs.json` created in the directory

## Usage

### Adding a Program

1. Click "Add New Program"
2. Fill in:
   - **Program Name** (required) - Display name in directory
   - **Program URL** (required) - Link to program page (must start with `/` or `https://www.ccri.edu`)
   - **Category** (required) - Workforce category for filtering
   - **Description** (optional) - For future use
3. Click "Save Program"

### Editing a Program

1. Click "Edit" button next to program
2. Modify fields
3. Click "Save Program"

### Deleting a Program

1. Click "Delete" button
2. Confirm deletion
3. Program removed from directory

## Integration with Main Directory

Programs saved here automatically appear in the main program directory:

```
Program Directory ← Fetches from program-list.php
                  ← Includes workforce-programs.json data
                  ← Filters by category via JavaScript
```

The main `program-list.php` (in `/_resources-2025/php/`) merges workforce programs with catalog programs.

## Data Structure

**workforce-programs.json:**
```json
[
  {
    "id": "workforce-welding-basics",
    "title": "Welding Basics",
    "url": "/workforce/programs/welding/",
    "type": "workforce",
    "pathway": "Workforce Partnerships",
    "category": "Manufacturing and Trades",
    "description": "Introduction to welding techniques"
  }
]
```

### Fields Explained

| Field | Purpose | Required |
|-------|---------|----------|
| `id` | Unique identifier (auto-generated from title) | Yes |
| `title` | Program name shown in directory | Yes |
| `url` | Link to program page | Yes |
| `type` | Always "workforce" | Yes |
| `pathway` | Always "Workforce Partnerships" | Yes |
| `category` | Workforce category for filtering | Yes |
| `description` | Optional description for future use | No |

## Categories

Available workforce categories (matches directory filters):

- Business and Technology
- Education
- Healthcare
- Manufacturing and Trades
- Renewable Energy
- Fit2Serve
- GED & Adult Education
- Transportation Education

**To add/change categories:**
1. Edit category list in `index.html` (line ~120)
2. Update `program-directory.js` (line ~166) if used for filtering

## Troubleshooting

### "Failed to save program data"

**Cause:** Permission issue - PHP can't write to directory

**Fix:**
1. Check directory permissions
2. Verify web server user has write access
3. Check `umask(0000)` in save-program.php
4. Look for debug info in the error response

### Programs Not Appearing in Directory

**Check:**
1. Is `workforce-programs.json` created?
2. Does `program-list.php` include workforce programs?
3. Is directory JavaScript filtering them out?
4. Check browser console for errors

### Cannot Access Management Page

**Causes:**
- SSO not configured
- User not authorized
- Wrong URL

**Fix:**
- Verify SSO settings
- Check user group permissions
- Confirm path is `/workforce/manage-programs/`

## Security Notes

### Protected by SSO
- Only authorized users can access
- No additional authentication needed
- Server logs track access

### Input Validation
- Required fields enforced
- URL format validated
- XSS prevention via `escapeHtml()`
- JSON encoding prevents injection

### File Permissions
- Uses `umask(0000)` for file creation
- Creates files as `666` (rw-rw-rw-)
- ACL inheritance on Windows Server
- Directory must be writable by web server

## Maintenance

### Backup Data

Backup `workforce-programs.json` regularly:
```bash
cp workforce-programs.json workforce-programs-backup-$(date +%Y%m%d).json
```

### Manual Editing

You can manually edit `workforce-programs.json` if needed:
1. Make backup first
2. Edit with text editor
3. Validate JSON syntax
4. Refresh management page to verify

### Moving/Renaming

If you move the directory:
1. Update SSO configuration
2. Files reference data in same directory (no path updates needed)
3. Update any links to management page

## Related Files

**In Main System:**
- `/programs-2025/php/program-list.php` - Merges workforce with catalog programs
- `/programs-2025/js/program-directory.js` - Filters by workforce category
- `/programs-2025/js/program-config.js` - Pathway/type mappings

## Support

For issues:
1. Check server error logs
2. View browser console (F12)
3. Verify file permissions
4. Test with sample program
5. Check SSO configuration

## Version History

### v1.0 (March 2026)
- Initial release
- Add/edit/delete functionality
- Category support
- SSO authentication
- JSON storage
