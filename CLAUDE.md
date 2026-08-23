# CLAUDE.md — Court of Public Opinion

Project context file. Place this at the root of the project (`Court_of_Public_Opinion/CLAUDE.md`). Update it after every meaningful change so context carries over between sessions.

## Project Overview

**Name:** Court of Public Opinion
**Type:** Solo final web project
**Deadline:** Wednesday, August 26, 11:59 PM
**Concept:** A debate practice web app with two modes:
1. **Judge a Case** — user debates an AI companion, which argues the opposite stance
2. **Enter Courtroom** — user creates or joins a room to debate another real person, with AI acting as judge and delivering a verdict

Past debates/cases are saved so users can track their record and see how their argument skills develop over time (Logic, Rebuttal, Evidence, Persuasion scores).

## Tech Stack

- **Frontend:** Vanilla HTML/CSS/JavaScript, Axios for HTTP requests
- **Backend:** PHP (mysqli, not PDO)
- **Database:** MySQL (via XAMPP/phpMyAdmin), database name: `debate`
- **Local root:** `C:\xampp\htdocs\Court_of_Public_Opinion`
- **Base URL:** `http://localhost/Court_of_Public_Opinion` (set in `client/scripts/env.js`)

## Developer Context

The user is a **junior developer** working on this as a solo final project. Code written for this project should stay simple and readable over clever or terse:

- **Keep PHP simple.** Straightforward, procedural, one thing per block — matches the existing endpoint style (see Coding Conventions below). Avoid introducing OOP patterns, abstractions, or PHP features the rest of the codebase doesn't already use, unless there's no simpler way to do it.
- **Avoid complex JavaScript.** Prefer plain, explicit code (this is already the project's convention — see below) over clever one-liners, advanced array methods, closures-within-closures, or other patterns that aren't obvious at a glance.
- **If complex JS is genuinely unavoidable** (no simpler way to do it), add clear comments explaining *what it's doing and why* — don't leave it unexplained just because it's correct.

**Workflow: confirm before implementing.** Think an approach through completely and present it, but do not write or edit code until the user explicitly confirms it — this applies at the level of each meaningful sub-step, not just once per feature. For example, when building a page like `debate.html`, that means checking in on the HTML structure approach first, then again before writing the CSS, then again before wiring up the JS — not building the whole thing in one pass and presenting it as finished. This overrides any default toward moving straight to implementation.

## Coding Conventions

- PHP: `mysqli`, `include(__DIR__ . "/path")`, `isset($_POST["..."])` checks with `exit` on failure (one check per field), `$mysql->prepare()` / `bind_param()` / `execute()` / `get_result()` / `fetch_assoc()` (single row) or `fetch_all(MYSQLI_ASSOC)` (multiple rows), `echo json_encode([...])` for all responses — only one `json_encode` call per request.
- JS: Vanilla JS + Axios + `FormData` (backend reads `$_POST`, not JSON body). Plain `for` loops preferred over `.map()`/`.forEach()`. Regular function declarations. Descriptive variable names. One function per job.
- Auth data and other cross-page state goes through `localStorage`, accessed via generic helpers in `helper.js`:
  ```js
  loadStoredData(key, defaultValue = null)
  saveStoredData(key, value)
  ```
- Passwords are hashed with `password_hash()` / verified with `password_verify()` — never stored in plain text.
- Auth is **token-based**: the token (`bin2hex(random_bytes(32))`) is generated **once, at signup**, and saved to `users.token`. Login does *not* regenerate it — it just looks the user up by email/password and returns the token already on their row. Protected endpoints resolve the user by token (never by a trusted `user_id` from the frontend).
- Folder-organized backend: subfolders per feature under `server/` (e.g. `server/profile/`), so includes use `__DIR__ . "/../database/connection.php"` when nested one level deep.
- Category-style display text (e.g. "ARTIFICIAL INTELLIGENCE · EDUCATION") is stored as a single plain string field — not normalized into separate tables — since it's cosmetic/display-only for now.
- `connection.php` sends `Access-Control-Allow-Origin` reflecting the request's `Origin` header, so the frontend can be run from a different local origin (e.g. Live Server on `127.0.0.1:5500`) than XAMPP's `localhost`. **Dev-only convenience — lock this down before any real deployment.**
- CRUD endpoints that can hit a DB constraint (duplicate unique key, FK violation) wrap `execute()` in `try/catch (mysqli_sql_exception $e)` and check `$mysql->errno` (1062 = duplicate entry, 1451 = FK violation) to return a friendly JSON message instead of letting PHP's uncaught-exception fatal error leak raw HTML into the response.

## Version Control

A `.gitignore` is set up at the project root. It excludes OS/editor junk files, `client/scripts/env.js` and `server/database/connection.php` (since these hold local config/credentials — copy them to `.example` versions if teammates or graders need the structure without real values), logs, backup files, and raw `.sql` dumps (except a tracked `schema.sql`, if one gets added later for setup instructions).

## Folder Structure (current)

```
Court_of_Public_Opinion/
├── CLAUDE.md
├── .gitignore
├── client/
│   ├── index.html
│   ├── pages/
│   │   ├── profile.html
│   │   ├── debate.html        (Judge a Case — vs AI; topic-select + debate screen built, no chat UI yet)
│   │   ├── courtroom.html     (Enter Courtroom — create room + open rooms list; no JS/backend wired yet)
│   │   ├── contact.html       (Contact Us form)
│   │   ├── cases.html         (Court Records — public list of past cases; no backend/JS wired yet)
│   │   ├── room.html          (live 1-on-1 room debate — topic/stances/timer/chat; reached via ?room_id=X)
│   │   └── policies/
│   │       ├── privacy_policy.html
│   │       └── ai_policy.html
│   ├── scripts/
│   │   ├── env.js             (BASE_URL constant)
│   │   ├── axios.min.js
│   │   ├── helper.js          (loadStoredData / saveStoredData / showNotification)
│   │   ├── profile.js         (signup/login/forgot-password/profile logic — renamed from profie.js)
│   │   ├── debate.js          (topic-select, stance toggle, header-collapse-on-debate-start)
│   │   ├── index.js           (homepage daily-case FOR/AGAINST stance selection + link wiring)
│   │   ├── contact.js         (contact form submit handler)
│   │   ├── courtroom.js       (create room, list open rooms, join room, cancel own room)
│   │   ├── cases.js           (fetches and renders the public Court Records list)
│   │   └── room.js            (live room debate: polls getRoomState.php every 3s, sends messages, 15-min countdown)
│   └── styles/
│       ├── style.css          (homepage)
│       ├── profile.css        (profile page — self-contained, imports its own fonts/vars like style.css does)
│       ├── policies.css       (privacy/AI policy pages — same self-contained pattern)
│       ├── debate.css         (self-contained; includes the header/footer/topic-area collapse animation)
│       ├── courtroom.css      (self-contained; includes the `.room-card` styling used by courtroom.js)
│       ├── contact.css        (self-contained; form styled like the sign-up form)
│       ├── cases.css          (self-contained; `.case-record` styling now used by cases.js)
│       └── room.css           (self-contained; reuses debate.css's topic/stance-pill treatment + new chat bubble styles)
└── server/
    ├── database/
    │   └── connection.php     (mysqli connection: $mysql; also sets the dev CORS header)
    ├── profile/
    │   ├── signUp.php
    │   ├── logIn.php
    │   ├── getProfile.php
    │   ├── resetPassword.php  (no-verification password reset: email + new password)
    │   └── deleteAccount.php  (token-authed; cascades manually — see Key Decisions below — instead of blocking on FK error)
    ├── contact/
    │   └── sendMessage.php    (stores a contact form submission — no email is actually sent, see below)
    ├── rooms/
    │   ├── createRoom.php     (token-authed; blocks if the user is already host/joiner of another open/in_progress room; inserts a room with status='open')
    │   ├── listRooms.php      (no auth needed; auto-closes rooms open >1hr with nobody joined, then returns all status='open' rooms joined with host username + host_id)
    │   ├── joinRoom.php       (token-authed; blocks joining your own room, a non-open room, or if already in another room; sets joiner_id + status='in_progress' + started_at=NOW())
    │   ├── cancelRoom.php     (token-authed; host-only, room must still be 'open'; sets status='closed')
    │   ├── sendMessage.php    (token-authed; sender must be host or joiner of the room and room must be 'in_progress'; inserts into room_messages)
    │   ├── getRoomState.php   (token-authed; caller must be a participant; lazily closes the room once 15 min have passed since started_at; returns room details incl. computed joiner_stance + full message log)
    │   └── getMyActiveRoom.php (token-authed; lazily closes any of the caller's own expired rooms first, then returns the room_id of their in_progress room, if any — powers the "return to your debate" banner)
    ├── topics/
    │   ├── getDailyTopic.php  (no auth needed; picks/returns "today's case" from daily_topics, see below)
    │   └── getRandomTopic.php (no auth needed; random topic from daily_topics for "Surprise Me" — doesn't touch used_on)
    └── cases/
        └── listCases.php      (no auth needed; returns AI-debated cases only — id, topic, verdict, formatted date — newest first)
```

Note: `signUp.php`/`logIn.php`/etc. live under `server/profile/`, not directly under `server/` — the folder name is about the *feature* (profile/auth), not the page. Same pattern for `server/contact/`.

## Database Schema (current)

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    joiner_id INT DEFAULT NULL,
    topic VARCHAR(150) NOT NULL,
    host_stance ENUM('FOR', 'AGAINST') NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    started_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id),
    FOREIGN KEY (joiner_id) REFERENCES users(id)
);

CREATE TABLE room_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    opponent_id INT DEFAULT NULL,
    room_id INT DEFAULT NULL,
    topic VARCHAR(150) NOT NULL,
    user_stance ENUM('FOR', 'AGAINST') NOT NULL,
    verdict ENUM('WON', 'LOST', 'DRAW') NOT NULL,
    score INT NOT NULL,
    logic_score INT NOT NULL,
    rebuttal_score INT NOT NULL,
    evidence_score INT NOT NULL,
    persuasion_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (opponent_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE daily_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categories VARCHAR(150) NOT NULL,
    topic VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    used_on DATE DEFAULT NULL
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Deferred (not built yet):** `reopen_requests` table — lets a user request that a `closed` room be reopened for the same topic. Explicitly postponed; revisit after core features are done.

## Backend Endpoints (current)

All under `server/profile/`.

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `signUp.php` | POST | `username`, `email`, `password` | Hashes password, generates the token, inserts into `users`, returns `user` object (incl. `token`) — frontend auto-logs the user in immediately after signup. Duplicate email/username (MySQL 1062) returns `"That username or email is already taken."` instead of crashing. |
| `logIn.php` | POST | `email`, `password` | Verifies password, returns the user's **existing** `token` (not regenerated) |
| `getProfile.php` | POST | `token` | Resolves `user_id` from token, returns `record` (cases/wins/losses/draws), `argument_profile` (avg scores, rounded via SQL), `rebuttal_improvement` (null + `cases_needed_for_trend` if under 20 total cases), `recent_cases` (last 5) |
| `resetPassword.php` | POST | `email`, `newPassword` | **No identity verification** — looks up by email and directly overwrites the password. Deliberately simple (no SMTP set up); fine for local/school use, not for production. |
| `deleteAccount.php` | POST | `token` | Resolves user by token, then **cascades manually** before deleting the row (see Key Decisions below for the reasoning): deletes rooms the user hosted (and all messages in them), deletes the user's own messages in rooms they only joined, detaches them as `joiner_id` from those rooms, deletes their own `cases` rows, detaches them as `opponent_id` from others' `cases` rows. The try/catch on the final `DELETE` (MySQL 1451) is now a defensive fallback rather than an expected path. |

Under `server/contact/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `sendMessage.php` | POST | `name`, `email`, `message` | Inserts into `contact_messages`. **No email is actually sent** — same no-SMTP constraint as `resetPassword.php`. This just stores the message for later manual review. |

Under `server/rooms/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `createRoom.php` | POST | `token`, `topic`, `stance` | Resolves user by token. Rejects if the user is already host or joiner of another `open`/`in_progress` room (`"You're already in a room..."`) — this is the "only one room at a time" rule. Inserts a room (`host_id`, `topic`, `host_stance`, `status = 'open'`). |
| `listRooms.php` | POST | *(none)* | No auth required. First auto-closes any `open` room with `created_at` older than 1 hour (checked lazily on every call — no cron, same pattern as the daily-topic recycle), then returns every remaining `status = 'open'` room, joined against `users` for the host's username, plus `host_id` (so the frontend can tell if the viewer is the host). |
| `joinRoom.php` | POST | `token`, `room_id` | Resolves user by token. Rejects if the room doesn't exist, isn't `open`, belongs to the requester (`"You can't join your own room."`), or the requester is already in another room (same one-room-at-a-time rule as `createRoom.php`). On success sets `joiner_id`, `status = 'in_progress'`, **and `started_at = NOW()`** — this is the debate's real start time, used for the 15-minute timer. |
| `cancelRoom.php` | POST | `token`, `room_id` | Resolves user by token. Rejects if the room doesn't exist, isn't owned by the requester (`"You can only cancel your own room."`), or is no longer `open`. On success sets `status = 'closed'`. |
| `sendMessage.php` | POST | `token`, `room_id`, `message` | Resolves user by token. Rejects if the requester isn't the room's host or joiner (`"You're not part of this room."`), or the room isn't `in_progress`. On success inserts into `room_messages`. |
| `getRoomState.php` | POST | `token`, `room_id` | Resolves user by token, rejects non-participants same as `sendMessage.php`. **Lazily closes the room** if `TIMESTAMPDIFF(MINUTE, started_at, NOW()) >= 15` (checked on every call, no cron — same lazy pattern used elsewhere). Returns room details (topic, host/joiner usernames, `host_stance`, a server-computed `joiner_stance` — just the opposite of `host_stance`, not stored), plus the full `room_messages` log joined with usernames, ordered oldest-first. |
| `getMyActiveRoom.php` | POST | `token` | Resolves user by token. First lazily closes any of *their own* `in_progress` rooms whose 15 minutes have run out, then returns the `id` of whichever `in_progress` room they're still host/joiner of (`null` if none). Powers `courtroom.html`'s "Return to your debate" banner — lets someone who navigated away from `room.html` find their way back in while the timer's still running. |

Under `server/topics/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `getDailyTopic.php` | POST | *(none)* | Picks "today's case": returns the row already marked `used_on = CURDATE()` if one exists; otherwise picks a random `used_on IS NULL` row and marks it; if none are unused either, resets every `used_on` to `NULL` (recycles the rotation) and picks again. No cron needed — this on-demand check is what makes recycling work. |
| `getRandomTopic.php` | POST | *(none)* | `ORDER BY RAND() LIMIT 1` from `daily_topics`, returns just the topic text. Used by `debate.html`'s "Surprise Me" — deliberately ignores `used_on` entirely so it doesn't interfere with the daily-case rotation. |

Under `server/cases/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `listCases.php` | POST | *(none)* | No auth required — returns rows from `cases` **where `room_id IS NULL`** (AI-debated cases only; room/courtroom cases are excluded) — id, topic, verdict, `created_at` formatted via SQL `DATE_FORMAT` as e.g. "August 18, 2026", newest first. Deliberately simple: no opponent/stance detail. |

## Frontend Pages (current status)

- ✅ `index.html` — landing page, masthead layout, nav (now includes COURTROOM between DEBATE and COURT RECORDS), hero ("ENTER THE COURT" button now points to `courtroom.html`, not `debate.html`), footer now also links Contact Us alongside the policy pages. "Case of the Day" is now **live**: `index.js` fetches `getDailyTopic.php` on load and fills in `#caseNumber`/`#caseCategory`/`#caseDescription`/`#daily_case` (all show "Loading..." placeholders until the fetch resolves). FOR/AGAINST are `<button>`s that toggle a `selectedStance` without navigating; clicking "Debate This Case" with no stance picked shows a notification and blocks navigation. `localStorage` stance handoff: `index.js` saves `{ topic, stance }` under the `debateCase` key on every FOR/AGAINST click (cleared on deselect), and the plain `debate.html` link just navigates — no URL params.
- ✅ `profile.html` — Sign Up / Log In / Forgot Password (3-way toggle, all in one page), dynamic profile section (username, record, argument profile bars, rebuttal trend, recent case cards, Log Out + Delete Account at the bottom). Signing up auto-logs in (token stored + straight to profile view). Session persists across refresh — page checks `localStorage` on load and calls `showProfile()` if a user is already stored.
- ✅ `debate.html` — built: `#topicSelect` (custom topic input + FOR/AGAINST stance pick + Start Debate / Surprise Me) and `#debateScreen` (topic heading + YOU/OPPONENT stance pills, styled like the homepage's "Case of the Day" card). `debate.js` reads `loadStoredData("debateCase")` (clearing it after use) instead of URL params, and computes the AI's opposite stance for `#debateOpponentLabel`. "Surprise Me" now calls `getRandomTopic.php` and randomizes the stance client-side (previously hardcoded to the same topic/stance every click). **No chat/argument UI yet** — that's still just these two screens, matching the agreed "minimum viable" scope. Also has a header-collapse animation (see below).
- ✅ `courtroom.html` — fully wired: `#createRoom` (topic input + stance pick + Create Room button) requires being logged in (checks `loadStoredData("user")`, same pattern as the profile page's account actions), posts to `createRoom.php`, then refreshes the list. `#openRoomsList` loads from `listRooms.php` on page load and renders `.room-card`s — each card shows a Join button (`joinRoom.php`), or a Cancel button (`cancelRoom.php`, burgundy danger style matching Delete Account) instead if the viewer is the room's own host (compares `room.host_id` to the logged-in user) — falls back to the static "No open rooms right now" text when empty. Open rooms with nobody joined auto-close after 1 hour (checked lazily in `listRooms.php`, no cron). A user can only be host/joiner of **one** `open`/`in_progress` room at a time (enforced server-side in both `createRoom.php` and `joinRoom.php`). **Joining a room now redirects to `room.html?room_id=X`** — the live debate screen. Also checks `getMyActiveRoom.php` on load and shows a "Return to your debate" banner (gold-bordered, at the top of the page) if the user has an `in_progress` room they navigated away from — lets them get back in as long as the 15-minute timer hasn't run out.
- ✅ `contact.html` — name/email/message form, submits to `sendMessage.php`, standard notification-on-success pattern.
- ✅ `cases.html` — built as a **public** archive (distinct from `profile.html`'s "My Record," which is the logged-in user's own history; no login required to view), now fully wired: `cases.js` fetches `listCases.php` on load and renders `.case-record`s (case number + verdict, topic, date), falling back to "No cases have been decided yet." when the list is empty. **Shows AI-debated cases only** — `listCases.php` filters `WHERE room_id IS NULL`, so courtroom (real-person) cases are excluded. Deliberately simple otherwise: no opponent/stance detail shown.
- ✅ `room.html` — the live 1-on-1 room debate, reached via `courtroom.html`'s Join flow. Shows the topic, YOU/OPPONENT stance pills (same visual treatment as `debate.html`'s `#debateScreen`, reusing the burgundy/gray convention), a scrolling chat log (`.chat-message.you` right-aligned burgundy, `.chat-message.opponent` left-aligned outlined), a message input, and a live countdown computed client-side from `room.started_at + 15 minutes`. `room.js` polls `getRoomState.php` every 3 seconds (no websockets — plain polling, matches the project's "keep it simple" convention) and guards against non-participants (redirects back to `courtroom.html` if `getRoomState.php` rejects the request). When the 15-minute mark passes, the input disables and shows "TIME'S UP" — **no verdict/AI-judging yet**, that's still blocked on the AI provider decision.
- ⬜ Case result display tied to real data — no verdict screen exists yet; scoring/AI-judging not wired to the database
- ✅ `policies/privacy_policy.html`, `policies/ai_policy.html` — static content pages matching the site's newspaper theme, linked from the footer on every page

**Header-collapse animation (`debate.html` only):** 5 seconds after `startDebate()` runs (not page load — this was deliberately changed from an earlier page-load-timer version), `document.body.classList.add("collapsed")` fires once. CSS keyed off `body.collapsed` then: collapses the masthead (height/opacity/padding all animate to 0), pins the header as `position: sticky; top: 0` with a background + shadow, reduces `#debateScreen`'s top padding so the topic sits higher, and fades the footer away — all to free vertical space for a future chat interface that doesn't exist yet. This is separate from the real debate timer decided below — this 5s delay is purely a cosmetic UI transition, not a countdown the user sees.

## Key Decisions & Open Questions

- **Sessions vs. token-only auth:** currently token-only (no PHP `$_SESSION`). User was undecided on adding real PHP sessions later — revisit if needed.
- **Token lifecycle:** generated once at signup, never rotated on login. If this needs to change (e.g. rotate on each login, or add expiry), treat it as a deliberate decision, not a bug fix — several endpoints depend on the token being stable.
- **Password reset is intentionally insecure:** `resetPassword.php` has no way to confirm the requester owns the email (no reset link/token sent). Chosen over a real email-based flow specifically because SMTP isn't set up in this project. Acceptable for a local/school project; would need a real token+email flow before any real deployment.
- **Account deletion now cascades manually (reversed from the original "block, don't cascade" decision).** No `ON DELETE CASCADE` exists at the DB level — `deleteAccount.php` does it in application code, and asymmetrically depending on the relationship, to avoid the original concern (destroying someone *else's* record):
  - **Their own content** (cases where they're `user_id`; rooms where they're `host_id`, plus all messages in those rooms) → **deleted outright**, since it's entirely theirs.
  - **Someone else's content that merely references them** (cases where they're `opponent_id`; rooms where they're only `joiner_id`) → **reference nulled, record preserved** (`opponent_id`/`joiner_id` set to `NULL`), so the other person keeps their own case/room history.
  - **Their own messages in a room they didn't host** → deleted individually (`room_messages` has no nullable "this was them" flag — the row itself is theirs), while the room and the host's messages stay intact.
  Tested against real data: deleting a room host correctly took the room and both participants' messages with it; deleting an account with zero ties succeeded cleanly with no errors from the added queries running against nothing.
  Bug found (and fixed) from the user's own live testing: detaching a joiner (`joiner_id = NULL`) originally left `status` at `'in_progress'` — a room stuck with a host, no joiner, and no way to progress or be rejoined by anyone else. Now also resets `status = 'open'` and `started_at = NULL` in that same query, so the room genuinely reopens.
- **"Return to your debate" banner (`courtroom.html`):** if someone navigates away from `room.html` mid-debate, there was previously no way back in — `courtroom.html` only ever listed `open` rooms, not ones you're already `in_progress` in. `getMyActiveRoom.php` + the banner fix this: shows up only while your 15-minute timer is still running (it lazily auto-closes your own expired rooms first, same pattern as everywhere else).
- **Daily case selection:** picks "today's case" on-demand (first visit of the day checks/sets `used_on = today` in `daily_topics`) rather than a scheduled cron job, since XAMPP has no task scheduler set up. **Implemented** in `getDailyTopic.php`. Only 3 seed topics exist right now, so the recycle path (reset all `used_on` to `NULL` once every topic has been used) will trigger within days — confirmed working, no admin UI needed since new topics inserted directly via phpMyAdmin (with `used_on = NULL`) get picked up automatically before any recycle is needed.
- **Rebuttal improvement trend:** requires 20+ total cases before showing a real percentage; otherwise shows "Complete N more cases to see your trend."
- **Reopen requests for closed rooms:** idea confirmed but explicitly deferred — do not build until asked.
- **Categories field:** intentionally denormalized (single string) rather than a proper many-to-many table, since it's currently just for display.
- **CORS is wide open for local dev:** `connection.php` reflects back whatever `Origin` header it receives, so the frontend can run from Live Server (`127.0.0.1:5500`) against XAMPP (`localhost`). Must be restricted to a real allowed-origin list before deployment.
- **Contact form doesn't send email:** `sendMessage.php` just inserts into `contact_messages` — no SMTP configured, so nothing is actually emailed. Someone has to manually check that table for now.
- **"Enter the Court" now points to `courtroom.html`, not `debate.html`:** deliberate — "Enter Courtroom" is one of the app's two named modes, so the hero CTA now matches that mode rather than the AI-debate one. `debate.html` is still reached via its own nav link and the "Debate This Case" flow.
- **Debate-screen header-collapse timer is tied to `startDebate()`, not page load:** this was changed mid-session — an earlier version fired 5s after `debate.html` loaded regardless of what was happening; now it only starts counting once an actual debate begins, so it doesn't collapse the header while someone's still picking a topic.
- **Real debate timer: 15 minutes, implemented for room debates, still pending for AI debates.** Same limit for both modes (an earlier idea of giving rooms more time was dropped). `room.html`/`room.js` + `getRoomState.php` now enforce this for room debates (`rooms.started_at`, client-side countdown + server-side lazy auto-close). The AI-debate side (`debate.html`) still has no timer — can't be wired in until the AI provider is picked and the actual chat mechanic exists there.
- **Rooms auto-expire after 1 hour with nobody joined**, and the host can also cancel manually at any time before someone joins (`cancelRoom.php`). Both implemented and tested. Deliberately does *not* try to detect "the host closed their tab/left the site" — browsers have no reliable signal for that (tab close, wifi loss, and a crashed laptop all look identical), so relying on it would be fragile. The 1-hour auto-expire plus manual cancel cover the real cases without needing that.
- **A user can only be in one room at a time** (as host or joiner, `status` `open` or `in_progress`) — enforced server-side in both `createRoom.php` and `joinRoom.php`, tested.
- **Live room debate is a real chat now, not just a placeholder screen** — `room.html` polls for new messages every 3s (plain polling, no websockets/real-time infra). This works *without* the AI provider decision, since the AI only judges the final verdict for room debates — the two humans argue directly with each other. Only the verdict/scoring step at the end is still blocked on that decision.
- **AI's role in room debates is confirmed to stay small once a provider is picked**: judge/score at the end only, never a participant in the actual back-and-forth. Contrast with "Judge a Case," where the AI *is* the opponent.

- **AI provider for the debate feature: undecided, deferred.** User initially said Claude, then asked about free tiers (neither Anthropic's nor OpenAI's API has one — only their consumer chat apps do). Looked at Google's Gemini API as a free alternative: it does have a real free tier (Flash model family, no credit card needed), but Google positions it for testing/low-volume only, with rate limits only visible per-account in Google AI Studio (not published as fixed numbers). User asked to come back to this later rather than commit. A rough integration design exists for the Claude path (PHP `curl_*` call, no SDK/Composer, per-debate system prompt arguing the AI's assigned stance, client-side-held conversation history sent as a JSON string each turn) but nothing is implemented and no provider is chosen yet.

## Next Steps (as of last session — 2026-08-23)

1. **Pick an AI provider** (see Key Decisions above) — this blocks two remaining things: (a) the actual chat UI on `debate.html` (still just topic-select + stance-label screens, no argument exchange — the AI *is* the opponent here, so this can't work at all without it) with its own 15-minute timer, and (b) the verdict/scoring step at the end of a room debate (`room.html`'s live chat itself is done and doesn't need this — only the final WON/LOST/DRAW judging does).
2. Before any real deployment: lock down the CORS origin, and reconsider the no-verification password reset flow.
