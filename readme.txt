=== Ghost Comment Manager ===
Contributors: devfluxr
Tags: comments, moderation, spam, trust, ghost
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 0.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Trust once → comments auto-publish with a moderator-only “ghost” flag. Includes a light spam shield, filters, bulk actions, and a clear dashboard.

== Description ==

Ghost Comment Manager is designed to reduce the time you spend moderating comments. Instead of re-approving the same people over and over, you mark a person as Trusted one time. From then on:

- Their new comments publish immediately.
- A subtle “ghost” indicator is shown to moderators only so you can spot and confirm at your convenience.
- Visitors see a normal comment; nothing changes on the public site.

Alongside this workflow improvement, the plugin includes a lightweight Shield that blocks common spam patterns without external services. A simple dashboard gives you live counts and a clear picture of what is happening.

This plugin focuses on workflow, clarity, and speed. It plays nicely with Akismet or Antispam Bee if you already use them.

=== Why use this plugin ===

1. Save time: stop re-approving loyal commenters.
2. Stay safe: every trusted comment is highlighted to moderators until confirmed.
3. Cut spam: built-in Shield blocks common abusive behavior before it reaches your queue.
4. See everything: a simple dashboard with trusted totals and block reasons.
5. Keep control: bulk trust/untrust, user-profile control, and comment-screen filters.

== Features ==

Core workflow
- Trust / Untrust a user from the Comments list.
- Auto-trust after X approved comments (configurable).
- Ghosted auto-publish for trusted users (mod-only highlight until confirmed).
- One-click Confirm to remove the ghost indicator.
- Role exclusions so specific roles (for example Editors) publish normally without ghosting.
- Custom ghost indicator icon and background color.

Shield Lite (no external API)
- Honeypot field that bots tend to fill.
- Minimum submit time to stop instant bot posts.
- Rate limits per IP (per minute and per hour).
- Maximum links per comment.
- Keyword and regular expression blocklist.
- Auto-close comments on old posts after X days.
- Minimum and maximum comment length.
- Duplicate comment protection within a time window.

Moderation UX
- Comment-screen filters:
  - Pending (New Users): only untrusted comments awaiting approval.
  - Ghost (Trusted): approved comments still awaiting moderator confirmation.
- Bulk actions: Trust or Untrust the user associated with selected comments.
- Trust from the User Profile screen (checkbox).

UI and Dashboard
- Colorful dashboard cards for trusted users, ghost-pending count, and totals.
- Shield Lite “blocks by reason” table.
- Clean and organized settings pages.
- “Pro Features” preview tab (coming soon items).

Integrations and compatibility
- Respects Akismet / Antispam Bee: if a comment is flagged as spam, this plugin does not ghost-mark or auto-approve it.
- Works with block themes and classic themes.
- Multisite compatible on a per-site basis.

## How it works (non-technical)

### 🧠 Approve vs Trust
- **Approve** = you approve **one** comment only.  
- **Trust** = you approve the **person**.  
  Once a user is trusted, their future comments are **published instantly** (no moderation wait).  
  - **Example:** You approve Sarah’s first few comments. After that, she’s trusted — her next comments appear immediately.

---

### 👻 Ghost indicator (moderator-only)
- Trusted users’ comments publish instantly **but can be optionally “ghosted”** (hidden from public) depending on your settings.  
- If ghosting applies:
  - **Public visitors** do **not** see ghosted comments yet.  
  - **Moderators** see them with a ghost icon 👻 or colored background.  
  - When you click **“Confirm (remove ghost)”**, the comment becomes visible to everyone.  

**Example:**  
John is a trusted user. His comment posts immediately but shows a ghost icon only moderators see.  
You review and click **Confirm** → it’s now public and the ghost mark disappears.

If the user’s role is excluded in settings (for example, “Subscriber”), their comments publish **publicly right away** with **no ghost step**.

---

### ⚙️ Auto-trust threshold
- In **Settings → Ghost Comment Manager → General**, set **Auto-trust after X approvals**.  
  **Example:** set it to **3**.  
  - When any commenter reaches **3 approved comments**, the plugin automatically trusts them.  
  - Their future comments post instantly without waiting for moderation.  
- Changing this number later affects **new users only**; existing trusted users stay trusted.

---

### 🔐 Role exclusions (no ghosting)
- Choose which roles should **never** be ghosted.  
  **Example:** check **Administrator** and **Editor**.  
  - Comments by these roles will always publish normally — no ghosting, no confirmation step.  
  - This ensures your staff or editors aren’t delayed or hidden from public view.

---

### 🛡️ Shield Lite (Spam / Abuse Guard)
- Works quietly in the background to stop obvious spam before it reaches your moderation queue.  
- Uses:
  - **Honeypot field** to trap bots  
  - **Minimum submit time** (prevents instant spam posts)  
  - **Rate limits**, **link limits**, and **keyword blocklist**  
- The default settings are safe and balanced.  
  You can fine-tune them anytime to match your community’s needs.


== Step-by-step setup ==

1. Install and activate the plugin.
2. Open Ghost Comments → Settings → General:
   - Set “Auto-trust after X approvals” (0 disables auto-trust).
   - Choose any roles to exclude from ghosting.
   - Pick an icon and background color for the moderator-only ghost indicator.
3. Open Ghost Comments → Settings → Shield Lite:
   - Keep Honeypot on.
   - Set Minimum submit time (3–5 seconds is typical).
   - Set rate limits (for example 6 per minute and 60 per hour).
   - Set the maximum number of links (for example 2).
   - Add any keywords or regular expressions to block.
   - Optionally auto-close comments on posts older than X days.
   - Adjust minimum/maximum length and duplicate time window to taste.
4. Start using it:
   - In Comments → All Comments, click “Trust User” on a real commenter.
   - Their next comments auto-publish with a moderator-only ghost indicator.
   - Click Confirm to remove the indicator when you’re ready.

== Using each feature ==

Trust / Untrust from Comments
- Where: Comments → All Comments (hover a row).
- Action: click “Trust User” or “Untrust User”.
- Result: future comments from that user auto-publish (if trusted) and are ghost-flagged for moderators.

Auto-trust after X approvals
- Where: Ghost Comments → Settings → General.
- Action: set a number of approved comments required (0 disables).
- Result: users become trusted automatically when they reach the threshold.

Confirm (remove ghost)
- Where: Comments → All Comments on the trusted comment row.
- Action: click “Confirm (remove ghost)”.
- Result: the moderator-only highlight disappears; the comment remains published.

Role exclusions
- Where: Ghost Comments → Settings → General.
- Action: check roles that should not be ghosted.
- Result: users with those roles publish normally without a ghost indicator.

Ghost indicator style
- Where: Ghost Comments → Settings → General.
- Action: set icon and color.
- Result: the moderator-only highlight uses your chosen style.

Shield Lite: Honeypot
- Where: Ghost Comments → Settings → Shield Lite.
- Action: keep “Honeypot” enabled.
- Result: bots that fill the hidden field are blocked immediately.

Shield Lite: Minimum submit time
- Where: Ghost Comments → Settings → Shield Lite.
- Action: set a minimum number of seconds (0 disables).
- Result: submissions that happen too quickly are blocked.

Shield Lite: Rate limits
- Where: Ghost Comments → Settings → Shield Lite.
- Action: set per-minute and per-hour limits (0 disables).
- Result: repeated posting from the same IP is throttled.

Shield Lite: Maximum links
- Where: Ghost Comments → Settings → Shield Lite.
- Action: set the link limit (0 means no limit).
- Result: comments with too many links are blocked.

Shield Lite: Keyword / regex blocklist
- Where: Ghost Comments → Settings → Shield Lite.
- Action: enter one rule per line; plain words match case-insensitive; regular expressions in /pattern/ or /pattern/i form are supported.
- Result: comments matching a rule are blocked.

Shield Lite: Auto-close old posts
- Where: Ghost Comments → Settings → Shield Lite.
- Action: set days after which comments are closed (0 disables).
- Result: new comments are blocked on very old posts.

Shield Lite: Min / Max length and Duplicate window
- Where: Ghost Comments → Settings → Shield Lite.
- Action: set minimum and maximum characters, and a duplicate-detection window in seconds (0 disables).
- Result: very short, very long, or repeated comments are blocked.

Filters on the Comments screen
- Where: Comments → All Comments.
- Action: use the “GCM View” dropdown or the additional status links.
- Result: see either Pending (New Users) or Ghost (Trusted) items instantly.

Bulk actions: Trust / Untrust
- Where: Comments → All Comments.
- Action: select multiple comments → choose “Trust user of selected comments” or “Untrust user of selected comments” → Apply.
- Result: users associated with those comments are updated in bulk.

Trust from the User Profile
- Where: Users → All Users → Edit user.
- Action: check “Trusted Commenter” and update the profile.
- Result: that user is trusted without needing to find a specific comment.

Dashboard
- Where: Ghost Comments → Dashboard.
- Shows: trusted user total, ghost-pending count, totals for auto-trusted, ghosts marked, ghosts confirmed, and a table of Shield Lite blocks by reason.

== Compatibility, performance, privacy ==

Compatibility
- Works with WordPress 6.0 and newer, classic and block themes.
- Plays well with Akismet and Antispam Bee; if a comment is flagged as spam, it will not be ghost-marked or auto-approved by this plugin.
- Multisite: activate per site or network-wide; settings are per site.

Performance
- Lightweight by design. No front-end JavaScript for visitors. Shield Lite uses simple server checks and transients.

Privacy
- Stores minimal user meta to remember trusted status and counters for the dashboard.
- No data is sent to external services by this plugin.

== Troubleshooting ==

I trusted a user but their comment did not auto-publish
- Confirm the user is logged in with the same account you trusted.
- Check if another plugin is forcing all comments to be held for moderation.
- If Akismet flagged the comment as spam, it will not auto-publish.

Ghost highlight is not visible to moderators
- Ensure you are logged in with a role that can moderate comments.
- Confirm the comment belongs to a trusted user and has not already been confirmed.
- Check the indicator color in settings; choose a more visible color if needed.

Auto-trust threshold is set but users are not becoming trusted
- The threshold only counts approved comments after you enabled it.
- Set the threshold to a smaller number to test quickly.

Too many legitimate comments are blocked
- Lower the minimum submit time.
- Increase rate limits or set them to 0 to disable.
- Raise the maximum links or remove specific keywords/regexes from the blocklist.
- Reduce duplicate window time.

== Frequently Asked Questions ==

**Does this replace Akismet/Antispam?**
No. It complements them. If Akismet (or another antispam plugin) flags a comment as spam, this plugin does not ghost-mark or approve it.

**What’s the difference between “Approve” and “Trust”?**
- **Approve** = one comment.
- **Trust** = the user (all future comments auto-publish with a temporary ghost flag until confirmed).

**Do visitors see the ghost flag/marker?**
No. Only logged-in users with moderation rights see it.

**Does it work with guests (unregistered commenters)?**
Trust is per **WordPress user account**, so guests are not auto-trusted. They can still comment—Shield Lite still protects you.

**Will changing the auto-trust threshold affect existing trusted users?**
No. It only affects users going forward.

**Can I exclude roles from ghosting?**
Yes. In **Settings → General**, you can exclude roles so their comments publish normally with no ghost flag.

**Does it work with block themes and the Comment block?**
Yes. The ghost flag/marker wraps the comment text for moderators only; public output is untouched.

**Multisite support?**
Yes. Works per-site. Network activate if you want it available network-wide; settings remain per individual site.

**Performance impact?**
Very small. Shield Lite uses simple checks and transients (no external API calls). No front-end JS is added for visitors.

**Can I export settings?**
Not in the free version. Export and analytics features are planned for Pro.

**Privacy – what data is stored?**
- **User meta**: `_gcm_trusted` (1/0), `_gcm_approved_count` (number).
- **Comment meta**: `_gcm_ghost` (1).
- **Options**: `gcm_settings` (your settings), `gcm_metrics` (dashboard counters).
- **Transients**: rate limit counters & duplicate hash (short-lived).

No data is sent to third parties.


== Screenshots ==

1. Dashboard with trusted counts and Shield Lite blocks.
2. Comments list with Trust / Untrust actions and the GCM status column.
3. Comments screen filters for Pending (New Users) and Ghost (Trusted).
4. Settings → General with auto-trust, role exclusions, and indicator style.
5. Settings → Shield Lite with anti-spam options.
6. User Profile screen with the Trusted Commenter checkbox.

== Installation ==

1. Upload the plugin through Plugins → Add New → Upload Plugin, or search for “Ghost Comment Manager”.
2. Activate the plugin.
3. Open Ghost Comments → Settings to configure thresholds and Shield Lite.
4. Start trusting users from the Comments screen.

== Changelog ==

= 0.1.0 =
- Initial release
- Trust / Untrust workflow and auto-trust threshold
- Ghosted auto-publish with one-click Confirm
- Role exclusions and indicator customization
- Shield Lite: honeypot, minimum submit time, rate limits, link limit, keyword/regex blocklist, auto-close, min/max length, duplicate protection
- Comment-screen filters for Pending (New Users) and Ghost (Trusted)
- Bulk actions (Trust / Untrust user of selected comments)
- Trust from the User Profile screen
- Dashboard with metrics and Pro preview

== Upgrade Notice ==

= 0.1.0 =
First public release with trusted workflow, ghost confirmation, Shield Lite, filters, bulk actions, user-profile control, and a dashboard.

== Roadmap / Pro ==

Coming soon in Pro:
- Trust levels with scoring and optional expiry
- Keyword rules with scoring and spam-gate thresholds
- Team assignments and internal notes
- Analytics with CSV export
- Advanced role and post-type overrides
