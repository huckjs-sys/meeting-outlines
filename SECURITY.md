# Security Policy — Meeting Outlines

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅ Active  |

Only the latest release receives security fixes.

---

## Vulnerability Disclosure Policy (VDP)

To report a security vulnerability, please **do not open a public GitHub issue**.

Open a [GitHub Security Advisory](https://github.com/huckjs-sys/meeting-outlines/security/advisories/new)
(private disclosure) or send an email to the maintainer listed in `plugin.json`.

Include:
- A clear description of the vulnerability
- Steps to reproduce
- Potential impact
- ChurchCRM and PHP versions affected

You will receive an acknowledgement within 72 hours and a fix or mitigation
plan within 14 days for confirmed issues.

---

## Security Capabilities & Data Access

### Database

The plugin reads from and writes to **two dedicated tables** it owns:

| Table | Operations |
|---|---|
| `worship_service` | `SELECT`, `INSERT`, `UPDATE`, `DELETE` |
| `worship_service_item` | `SELECT`, `INSERT`, `UPDATE`, `DELETE` |

It also performs read-only `SELECT` queries on ChurchCRM core tables:

| Table | Purpose |
|---|---|
| `person_per` | Resolve preacher / responsible names |
| `group_grp` | List groups for settings dropdowns |
| `person2group2role_p2g2r` | List group members |

No access to financial, authentication, email, or family tables.

---

### File System

| Operation | Path | Purpose |
|---|---|---|
| `fs.read` | `<plugin>/data/bible-structure.json` | Bible book/chapter/verse structure |
| `fs.read` | `<plugin>/data/bible-versions.json` | Available Bible versions |
| `fs.read` | `<plugin>/lib/vendor/` | mPDF library (PHP autoload) |
| `fs.write` | `sys_get_temp_dir()/mpdf_meeting_outlines/` | mPDF temporary files during PDF generation |

The plugin **never writes inside the plugin directory at runtime** and never
accesses paths outside of the above.

---

### Network

| Direction | Origin | Destination | Purpose |
|---|---|---|---|
| Outbound (browser) | End-user browser | `cdn.jsdelivr.net` | SortableJS 1.15.3 (drag & drop UI) |

No server-side outbound network calls are made. The CDN request originates
from the user's browser when loading the edit view, not from the PHP server.
The plugin has no inbound network listeners beyond the standard Slim 4 routes
registered through ChurchCRM's router.

---

### Permissions Summary

```json
"permissions": ["db.read", "db.write", "fs.read", "fs.write", "network.outbound"]
```

`network.outbound` reflects the browser-side CDN load of SortableJS (jsDelivr).
No server-side HTTP client is used.

---

## Known Limitations

- SortableJS is loaded from jsDelivr CDN — an internet-blocked environment must
  self-host the library and update `views/edit.php` accordingly.
- mPDF temporary files are not automatically purged; the OS temp-cleanup policy
  applies (typically on reboot or via cron).
