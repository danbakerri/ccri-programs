# XSL Integration Instructions

## How to Integrate Dynamic Content into Your Program Template

### Step 1: Upload the PHP Script

Upload `fetch-program-content.php` to:
```
https://www.ccri.edu/_resources-2025/php/fetch-program-content.php
```

Make sure the `cache` subdirectory is writable:
```
/_resources-2025/php/cache/
```

---

### Step 2: Update Your XSL Template

In your `program.xsl` file, find where you currently display the catalog-description content.

**REPLACE THIS:**
```xml
<div class="catalog-description">
    <xsl:apply-templates select="ouc:div[@label='catalog-description']"/>
</div>
```

**WITH THIS:**
```xml
<div class="catalog-description">
    <xsl:choose>
        <xsl:when test="ouc:properties[@label='config']/parameter[@name='display-catalog-description']/option[@value='display-catalog-description']/@selected='true'">
            <!-- Fetch dynamic content from catalog -->
            <xsl:variable name="catalog-url" select="ouc:properties[@label='config']/parameter[@name='catalog-url']"/>
            <xsl:if test="$catalog-url != ''">
                <xsl:copy-of select="ou:include-url(concat('https://www.ccri.edu/_resources-2025/php/fetch-program-content.php?url=', $catalog-url))"/>
            </xsl:if>
        </xsl:when>
        <xsl:otherwise>
            <!-- Use custom content from editable region -->
            <xsl:apply-templates select="ouc:div[@label='catalog-description']"/>
        </xsl:otherwise>
    </xsl:choose>
</div>
```

---

### What This Does:

1. **Checks the `display-catalog-description` parameter:**
   - If "Yes" → Fetches content dynamically from catalog via PHP script
   - If "No" → Shows custom content from the editable region

2. **Uses the `catalog-url` parameter** to know which program to fetch

3. **Calls `fetch-program-content.php`** which:
   - Fetches from catalog
   - Caches for 6 hours
   - Returns formatted HTML with accordions

---

### Step 3: Clear Catalog Description Content in PCF Files

For existing program PCF files, you can now EMPTY the `catalog-description` editable region since content will be fetched dynamically.

The region can be blank:
```xml
<ouc:div label="catalog-description"></ouc:div>
```

Or have a placeholder:
```xml
<ouc:div label="catalog-description">
    <!-- Content loaded dynamically from catalog -->
</ouc:div>
```

---

### Step 4: Test

1. Publish a program page
2. View it on the website
3. Content should load from the catalog
4. Check browser dev tools Network tab - you should see the PHP script being called
5. Content is cached - subsequent page loads use cached version

---

### Troubleshooting:

**If content doesn't appear:**
- Check that `fetch-program-content.php` is uploaded correctly
- Verify the `catalog-url` parameter is set in the PCF properties
- Check PHP error logs on the server
- Make sure the cache directory is writable

**To force refresh cache:**
- Delete files in `/_resources-2025/php/cache/`
- Or wait 6 hours for automatic refresh
