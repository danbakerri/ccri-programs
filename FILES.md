# File Inventory - CCRI Program Pages System

Complete list of files for the GitHub repository.

## Documentation Files

| File | Purpose | Location |
|------|---------|----------|
| **README.md** | Main documentation - system overview, setup, usage | Root |
| **QUICKSTART.md** | 5-minute setup guide for new users | Root |
| **MAINTENANCE.md** | Ongoing maintenance workflows and troubleshooting | Root |
| **XSL-INTEGRATION.md** | XSL template integration instructions | Root |
| **FILES.md** | This file - inventory of all files | Root |
| **javascript/README.md** | JavaScript configuration and usage guide | javascript/ |

## Generation Scripts (Local)

| File | Purpose | Usage |
|------|---------|-------|
| **generate-all-programs.php** | Bulk generate all academic programs | `php generate-all-programs.php` |
| **generate-program-dynamic.php** | Generate single program | `php generate-program-dynamic.php /path/` |
| **monitor-programs.php** | Monitor catalog for changes | `php monitor-programs.php` |

## Server Files (Upload to /_resources-2025/php/)

| File | Purpose | Upload Location |
|------|---------|-----------------|
| **fetch-program-content.php** | Fetch & cache program content from catalog | `/_resources-2025/php/` |
| **program-list.php** | Generate JSON list of all programs | `/_resources-2025/php/` |
| **department-urls.php** | Department mappings and normalization | `/_resources-2025/php/` |

## JavaScript Files (Upload to /_resources-2025/js/)

| File | Purpose | Upload Location |
|------|---------|-----------------|
| **program-config.js** | Configuration for pathways, types, hidden programs, label transformations | `/_resources-2025/js/` |
| **program-directory.js** | Directory filtering, search, pagination, URL state management | `/_resources-2025/js/` |

## Configuration Files

| File | Purpose | Notes |
|------|---------|-------|
| **.gitignore** | Git ignore rules | Excludes bulk-output, baselines, OS files |
| **program-baseline.json** | Monitoring baseline (auto-generated) | Created by monitor-programs.php |

## Generated Files (Not in Repo)

| File/Folder | Purpose | Created By |
|-------------|---------|------------|
| **bulk-output/** | Generated PCF files for all programs | generate-all-programs.php |
| **program-baseline.json** | Current catalog state for monitoring | monitor-programs.php |
| **{program-slug}/** | Single program PCF files | generate-program-dynamic.php |

## File Dependencies

### generate-all-programs.php
- Requires: `department-urls.php`
- Fetches from: `https://www.ccri.edu/_resources-2025/php/program-list.php?v=7`
- Fetches from: `https://catalog.ccri.edu/programs-study/.../index.xml`
- Creates: `bulk-output/{program-slug}/_props.pcf`
- Creates: `bulk-output/{program-slug}/index.pcf`

### generate-program-dynamic.php
- Requires: `department-urls.php`
- Fetches from: `https://www.ccri.edu/_resources-2025/php/program-list.php?v=7`
- Fetches from: `https://catalog.ccri.edu/programs-study/.../index.xml`
- Creates: `{program-slug}/_props.pcf`
- Creates: `{program-slug}/index.pcf`

### monitor-programs.php
- Requires: `department-urls.php`
- Fetches from: `https://www.ccri.edu/_resources-2025/php/program-list.php?v=7`
- Creates: `program-baseline.json` (first run)
- Updates: `program-baseline.json` (when changes confirmed)

### fetch-program-content.php (Server)
- Requires: Cache directory at `/_resources-2025/php/cache/`
- Fetches from: `https://catalog.ccri.edu/programs-study/.../index.xml`
- Creates: `/_resources-2025/php/cache/program_*.html` (cache files)
- Used by: XSL template `document()` function

### program-list.php (Server)
- Requires: `department-urls.php`
- Requires: Cache directory at `/_resources-2025/php/cache/`
- Fetches from: `https://catalog.ccri.edu/programs-study/index.xml`
- Creates: `/_resources-2025/php/cache/program-list.json` (cache)
- Used by: Directory page, generation scripts, monitoring script

### department-urls.php
- Standalone - no dependencies
- Used by: All generation scripts, program-list.php
- Contains: Department name mappings and URL mappings

## File Sizes (Approximate)

| File | Size |
|------|------|
| README.md | 18 KB |
| MAINTENANCE.md | 12 KB |
| QUICKSTART.md | 3 KB |
| XSL-INTEGRATION.md | 8 KB |
| FILES.md | 18 KB |
| javascript/README.md | 12 KB |
| generate-all-programs.php | 12 KB |
| generate-program-dynamic.php | 10 KB |
| monitor-programs.php | 11 KB |
| fetch-program-content.php | 8 KB |
| program-list.php | 10 KB |
| department-urls.php | 15 KB |
| program-config.js | 3 KB |
| program-directory.js | 11 KB |

**Total Repository Size:** ~150 KB (excluding generated files)

## Version Control Strategy

### Tracked in Git
- All documentation files (*.md)
- All PHP scripts
- .gitignore

### NOT Tracked in Git
- bulk-output/ (generated files - too large)
- program-baseline.json (user-specific baseline)
- OS-specific files (.DS_Store, Thumbs.db)
- IDE files (.vscode, .idea)

### Ignored via .gitignore
```
bulk-output/
program-baseline.json
.DS_Store
.vscode/
.idea/
*.log
```

## Update Frequency

| File | Update Frequency | Reason |
|------|------------------|--------|
| Generation scripts | Rarely | Only when template changes |
| Server PHP files | Rarely | Only when logic/cache changes |
| department-urls.php | Annually | When departments reorganize |
| Documentation | As needed | When workflows change |
| program-baseline.json | Monthly | Updated by monitoring |

## File Relationships

```
GitHub Repository
├── Documentation
│   ├── README.md (links to all other docs)
│   ├── QUICKSTART.md (new user guide)
│   ├── MAINTENANCE.md (ongoing workflows)
│   ├── XSL-INTEGRATION.md (template guide)
│   └── FILES.md (this file)
│
├── Local Scripts
│   ├── generate-all-programs.php → Uses department-urls.php
│   ├── generate-program-dynamic.php → Uses department-urls.php
│   └── monitor-programs.php → Uses department-urls.php
│
├── Server Files (Upload separately)
│   ├── PHP (/_resources-2025/php/)
│   │   ├── fetch-program-content.php
│   │   ├── program-list.php → Uses department-urls.php
│   │   └── department-urls.php
│   │
│   └── JavaScript (/_resources-2025/js/)
│       ├── program-config.js (standalone)
│       └── program-directory.js → Requires program-config.js
│
├── JavaScript Documentation
│   └── javascript/
│       ├── program-config.js (copy of server file)
│       ├── program-directory.js (copy of server file)
│       └── README.md (configuration guide)
│
└── Generated (Not in repo)
    ├── bulk-output/ (98 program folders)
    └── program-baseline.json
```

## Installation Checklist

- [ ] Clone repository
- [ ] Upload server files (fetch-program-content.php, program-list.php, department-urls.php)
- [ ] Create cache directory on server
- [ ] Set cache permissions (IT)
- [ ] Test server URLs
- [ ] Run generate-all-programs.php locally
- [ ] Upload bulk-output to Modern Campus
- [ ] Publish pages
- [ ] Run monitor-programs.php to create baseline
- [ ] Update XSL template (see XSL-INTEGRATION.md)
- [ ] Test live program pages

## Backup Strategy

### What to Back Up
- **GitHub repository** - All source files (already backed up via Git)
- **program-baseline.json** - Save locally (small file, easy to recreate)
- **Server cache directory** - Not needed (auto-regenerates)
- **bulk-output** - Optional (can regenerate anytime)

### Recovery Process
1. Clone repository from GitHub
2. Run `php monitor-programs.php` to recreate baseline
3. Regenerate any needed programs with generation scripts
4. Re-upload server files if needed

## Support Files

For questions about:
- **System architecture** → See README.md
- **Daily/monthly tasks** → See MAINTENANCE.md
- **Initial setup** → See QUICKSTART.md
- **XSL template** → See XSL-INTEGRATION.md
- **Specific files** → See this file (FILES.md)
