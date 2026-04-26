# Updater System

Gopanel includes an admin-side update checker based on a manifest file.

## Manifest

Root file:

```text
gopanel_updates.json
```

Example:

```json
{
  "current_version": "1.1.0",
  "updates": [
    {
      "version": "1.1.0",
      "date": "2026-04-26",
      "description": "Updated log pages",
      "files": [
        {
          "path": "app/Http/Controllers/Gopanel/Activity/FileLogController.php",
          "action": "modified"
        }
      ]
    }
  ]
}
```

## Actions

- `added` - download and create a new file.
- `modified` - download and replace an existing file with backup.
- `deleted` - remove a local file with backup.

## Configuration

Optional `.env` values:

```env
GOPANEL_UPDATER_ENABLED=true
GOPANEL_GITHUB_OWNER=goweb-az
GOPANEL_GITHUB_REPO=gopanel
GOPANEL_GITHUB_BRANCH=master
GOPANEL_GITHUB_TOKEN=
```

Important files:

```text
config/gopanel/updater.php
app/Services/Gopanel/GitHubUpdateService.php
app/Http/Controllers/Gopanel/System/UpdateController.php
public/assets/gopanel/js/modules/updater.js
```

