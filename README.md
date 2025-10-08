# Ghost Comment Manager

Moderate and “ghost” comments: publish as visible-to-mods only until trust is earned (or manual approval). Comes with trust toggles, list-table columns, and lightweight analytics.

- **Requires:** WordPress 6.0+
- **Tested up to:** 6.8
- **Requires PHP:** 7.4+
- **License:** GPLv2 or later

## Features
- Trust/untrust users quickly from the Comments list.
- “Ghost” publish mode via comment meta (`_gcmgr_ghost`) until confirmed.
- New status column and filters in the Comments screen.
- Minimal metrics stored in `gcmgr_metrics` (opt-in for future enhancements).

## Installation
1. Download the latest release ZIP.
2. WordPress Admin → Plugins → Add New → Upload Plugin → select the ZIP → Install → Activate.
3. Open **Ghost Comments → Settings**.

## FAQ
**Is this compatible with caching/security plugins?**  
Yes. All server-side checks are namespaced and prefixed; ABSPATH guards are in place.

**Will this conflict with other comment plugins?**  
All WordPress-visible identifiers use the `gcmgr_` prefix to avoid collisions.

## Changelog
See [Releases](../../releases) for detailed notes.

## Support
- For now, open a GitHub Issue with steps to reproduce.
- After wp.org approval, use the Support Forum on WordPress.org.

## Links
- **Plugin URI:** https://github.com/devfluxr/ghost-comment-manager
- **Author URI:** https://profiles.wordpress.org/devfluxr/
