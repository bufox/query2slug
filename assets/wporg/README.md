# WordPress.org Assets

These files go in the `/assets/` directory of the SVN repository (not inside the plugin zip).

## Required files

### Screenshots (referenced in readme.txt)
- `screenshot-1.png` — Rules list with status toggle, URL preview, and filter summary
- `screenshot-2.png` — Rule editor with slug preview and filter autocomplete

### How to capture
1. Go to http://localhost:8888/wp-admin/admin.php?page=q2s-rules (rules list)
2. Screenshot the full page → save as `screenshot-1.png`
3. Go to http://localhost:8888/wp-admin/admin.php?page=q2s-edit (add/edit rule)
4. Fill in example data and screenshot → save as `screenshot-2.png`
5. Recommended size: 1280x800 or similar, PNG format

### Banner (top of plugin page on wordpress.org)
- `banner-772x250.png` — standard resolution
- `banner-1544x500.png` — retina (optional but recommended)

### Icon (search results, plugin list)
- `icon-128x128.png` — standard resolution
- `icon-256x256.png` — retina (optional but recommended)

## Notes
- All files must be PNG or JPG
- These are deployed separately via the SVN `/assets/` directory
- The `.distignore` excludes this directory from the plugin zip
