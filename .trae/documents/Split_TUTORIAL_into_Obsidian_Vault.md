# Plan: Split TUTORIAL.md into Obsidian Vault

## Summary

Break the monolithic `TUTORIAL.md` (3137 lines, 9 sections + quick reference) into a folder of individual Obsidian-compatible markdown files with `index.md` as the entry point.

## Output Structure

```
apps/test9/test9-tutorial/
├── index.md              # Main index with Obsidian links to all sections
├── 01-system-overview.md
├── 02-authentication.md
├── 03-data-collections-api.md
├── 04-storage-api.md
├── 05-storage-folders-api.md
├── 06-notifications-api.md
├── 07-cloud-functions-api.md
├── 08-admin-api.md
├── 09-access-control.md
└── quick-reference.md    # Endpoint reference tables
```

## Mapping from TUTORIAL.md sections to files

| File | Source Lines | Content |
|------|-------------|---------|
| `01-system-overview.md` | 1-43 | Architecture, base URLs, getting started |
| `02-authentication.md` | 45-108 | Validate admin key endpoint |
| `03-data-collections-api.md` | 111-497 | CRUD operations on collections (7 sub-sections) |
| `04-storage-api.md` | 499-677 | Upload, get, delete files (3 sub-sections) |
| `05-storage-folders-api.md` | 679-1337 | Folder/file operations (12 sub-sections) |
| `06-notifications-api.md` | 1339-1565 | Notification CRUD (4 sub-sections) |
| `07-cloud-functions-api.md` | 1568-1650 | Execute custom functions |
| `08-admin-api.md` | 1652-2774 | Admin endpoints (22 sub-sections) |
| `09-access-control.md` | 2776-3059 | Collection/folder access control (5 sub-sections) |
| `quick-reference.md` | 3062-3133 | Complete endpoint reference tables |

## Each file will include

1. **Frontmatter** (Obsidian tags): `---\ntitle: ...\nsection: ...\n---`
2. **Obsidian wiki-links** `[[filename]]` for cross-references
3. **Back-links** at bottom: "← [[previous]] | [[index]] | → [[next]]"
4. **All code examples preserved** (curl, Python, PHP, JavaScript)

## Steps

1. Create folder `apps/test9/test9-tutorial/`
2. Create each file by extracting the corresponding section from `TUTORIAL.md`
3. Add Obsidian frontmatter, wiki-links, and navigation to each file
4. Create `index.md` with a table of contents linking to all files
5. Keep original `TUTORIAL.md` unchanged (not deleted)

## Verification

- Count: 10 new files (9 sections + index + quick reference = 11 files total)
- All code blocks preserved
- All Obsidian links resolve correctly
- Navigation links work (prev/index/next)
