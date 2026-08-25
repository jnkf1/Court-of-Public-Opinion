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
- **No native `alert()`/`confirm()` dialogs, anywhere.** Destructive/irreversible actions (Delete Account, Cancel Room, Forfeit) use an "arm on first click, confirm on second click within 4s" pattern instead: first click sets a module-level `xArmed` flag, changes the button's own text to "CLICK AGAIN TO CONFIRM", and fires a `showNotification()` warning; a `setTimeout` resets both the flag and the button text after 4s if there's no second click; the second click (while armed) clears that timeout and actually performs the action. Same site-wide toast (`showNotification()`) is used for every other message. See `deleteAccountBtn` in `profile.js`, `room-cancel-btn` in `courtroom.js`, and `forfeitBtn` in `debate.js`/`room.js` for the pattern.
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
    │   ├── getRoomState.php   (token-authed; caller must be a participant; lazily closes the room + judges it once 1 min has passed since started_at, see below; returns room details incl. computed joiner_stance, full message log, and myVerdict)
    │   ├── getMyActiveRoom.php (token-authed; lazily closes any of the caller's own expired rooms first, then returns the room_id of their in_progress room, if any — powers the "return to your debate" banner)
    │   └── forfeitRoom.php    (token-authed; caller must be host or joiner of an in_progress room; closes it immediately and inserts fixed-score cases — LOST/0 for the forfeiter, WON/100 for the opponent, see below)
    ├── topics/
    │   ├── getDailyTopic.php  (no auth needed; picks/returns "today's case" from daily_topics, see below)
    │   └── getRandomTopic.php (no auth needed; random topic from daily_topics for "Surprise Me" — doesn't touch used_on)
    ├── cases/
    │   └── listCases.php      (no auth needed; returns AI-debated cases only — id, topic, verdict, formatted date — newest first)
    └── ai/
        ├── config.php          (gitignored; defines GEMINI_API_KEY — see Key Decisions below for how this got set up)
        ├── config.example.php  (tracked placeholder)
        ├── api.php             (callGemini() — shared Gemini caller; extractJsonFromText() — pulls a JSON object out of a text reply for the judging endpoints, see below)
        ├── getAiResponse.php   (token-less; takes topic/aiStance/history(JSON)/message, builds the stance-arguing system instruction, returns the AI's reply)
        ├── checkTopic.php      (token-less; takes topic, asks Gemini if it's genuinely debatable, fails open on any AI error/quota issue — see below)
        ├── judgeAiDebate.php   (token-authed; takes topic/userStance/history(JSON) for a finished AI debate, asks Gemini for a strict-JSON verdict, saves one row to `cases`)
        └── forfeitAiDebate.php (token-authed; takes topic/userStance, no Gemini call — directly inserts a fixed LOST/0 row into `cases`, see below)
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
| `getProfile.php` | POST | `token` | Resolves `user_id` from token, returns `record` (cases/wins/losses/draws), `argument_profile` (avg scores, rounded via SQL), `rebuttal_improvement` + `latest_rebuttal_score` (see below — no fixed case-count threshold), `recent_cases` (last 5) |
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
| `joinRoom.php` | POST | `token`, `room_id` | Resolves user by token. Rejects if the room doesn't exist, isn't `open`, belongs to the requester (`"You can't join your own room."`), or the requester is already in another room (same one-room-at-a-time rule as `createRoom.php`). On success sets `joiner_id`, `status = 'in_progress'`, **and `started_at = NOW()`** — this is the debate's real start time, used for the 1-minute timer. |
| `cancelRoom.php` | POST | `token`, `room_id` | Resolves user by token. Rejects if the room doesn't exist, isn't owned by the requester (`"You can only cancel your own room."`), or is no longer `open`. On success sets `status = 'closed'`. |
| `sendMessage.php` | POST | `token`, `room_id`, `message` | Resolves user by token. Rejects if the requester isn't the room's host or joiner (`"You're not part of this room."`), or the room isn't `in_progress`. On success inserts into `room_messages`. |
| `getRoomState.php` | POST | `token`, `room_id` | Resolves user by token, rejects non-participants same as `sendMessage.php`. **Lazily closes the room** if `TIMESTAMPDIFF(MINUTE, started_at, NOW()) >= 15` (checked on every call, no cron — same lazy pattern used elsewhere). **The request that actually flips `status` to `closed`** (guarded via `UPDATE ... WHERE status = 'in_progress'` + checking `affected_rows`, so simultaneous polls from both participants can't double-judge) **also judges the debate right then** — builds a transcript from `room_messages`, asks Gemini for a strict-JSON winner + per-side scores, and inserts two `cases` rows (one per participant, `opponent_id`/`room_id` cross-referenced). Returns room details (topic, host/joiner usernames, `host_stance`, a server-computed `joiner_stance`), the full `room_messages` log, and `myVerdict` — the caller's own row from `cases` if the room is closed (whether just judged or already judged earlier), `null` otherwise. |
| `getMyActiveRoom.php` | POST | `token` | Resolves user by token. First lazily closes any of *their own* `in_progress` rooms whose 1 minute has run out, then returns the `id` + `status` of whichever room they're still involved in — **as host, this now also includes a still-`open` room with nobody joined yet**, not just `in_progress` ones (originally missed: a host who navigated away before anyone joined had no way back in at all). A joiner can only ever match `in_progress`, since you can't be the joiner of an unjoined room. `null` if no active room. Powers `courtroom.html`'s "Return to your debate" banner. |
| `forfeitRoom.php` | POST | `token`, `room_id` | Resolves user by token, rejects non-participants same as `sendMessage.php`. If the room's already `closed`, just returns the caller's existing `cases` row (or an error if none) instead of re-processing. Otherwise closes it via the same `UPDATE ... WHERE status = 'in_progress'` + `affected_rows` guard as `getRoomState.php` (so a forfeit racing the 15-min auto-close, or two forfeit clicks, can't double-insert), then inserts two fixed-score `cases` rows directly — no Gemini call needed since the outcome isn't in question: the forfeiter gets `LOST`/`0` across all categories, the opponent gets `WON`/`100`. Returns the forfeiter's own verdict in the same shape `showVerdict()` already expects. |

Under `server/topics/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `getDailyTopic.php` | POST | *(none)* | Picks "today's case": returns the row already marked `used_on = CURDATE()` if one exists; otherwise picks a random `used_on IS NULL` row and marks it; if none are unused either, resets every `used_on` to `NULL` (recycles the rotation) and picks again. No cron needed — this on-demand check is what makes recycling work. |
| `getRandomTopic.php` | POST | *(none)* | `ORDER BY RAND() LIMIT 1` from `daily_topics`, returns just the topic text. Used by `debate.html`'s "Surprise Me" — deliberately ignores `used_on` entirely so it doesn't interfere with the daily-case rotation. |

Under `server/cases/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `listCases.php` | POST | *(none)* | No auth required — returns rows from `cases` **where `room_id IS NULL`** (AI-debated cases only; room/courtroom cases are excluded) — id, topic, verdict, `created_at` formatted via SQL `DATE_FORMAT` as e.g. "August 18, 2026", newest first. Deliberately simple: no opponent/stance detail. |

Under `server/ai/`:

| Endpoint | Method | Input | Notes |
|---|---|---|---|
| `getAiResponse.php` | POST | `topic`, `aiStance`, `history` (JSON string), `message` | No auth required — builds a system instruction telling Gemini to argue `aiStance` in 2-4 sentences and never break character, appends `message` to the decoded `history`, calls `callGemini()`, returns the reply. Conversation history is **not persisted server-side** — `debate.js` holds it in a JS variable and resends the whole thing each turn (same `FormData`-as-JSON-string convention used elsewhere). |
| `checkTopic.php` | POST | `topic` | No auth required — asks Gemini for strict JSON (`{"debatable": true/false, "reason": "..."}`) on whether the topic has two genuine opposing sides. **Fails open**: if the Gemini call itself errors (quota, network) or the reply can't be parsed, returns `debatable: true` rather than blocking the user over an unrelated AI hiccup. Called by both `debate.js` (custom topic) and `courtroom.js` (room creation) before proceeding — see Key Decisions below. |
| `judgeAiDebate.php` | POST | `token`, `topic`, `userStance`, `history` (JSON string) | Resolves user by token. Flattens `history` into a plain-text transcript, asks Gemini for a strict-JSON verdict (`WON`/`LOST`/`DRAW` + 4 category scores 0-100, via `extractJsonFromText()` since the reply isn't guaranteed to be bare JSON), computes an overall score as the average, inserts one row into `cases` (`opponent_id`/`room_id` both `NULL`), and returns the verdict/scores for `debate.js` to display. |
| `forfeitAiDebate.php` | POST | `token`, `topic`, `userStance` | Resolves user by token. No Gemini call — directly inserts one fixed `LOST`/`0`-across-the-board row into `cases` (`opponent_id`/`room_id` both `NULL`, same as a normal AI debate). Returns the verdict in the same shape `showVerdict()` expects. |

## Frontend Pages (current status)

- ✅ `index.html` — landing page, masthead layout, nav (now includes COURTROOM between DEBATE and COURT RECORDS), hero ("ENTER THE COURT" button now points to `courtroom.html`, not `debate.html`), footer now also links Contact Us alongside the policy pages. "Case of the Day" is now **live**: `index.js` fetches `getDailyTopic.php` on load and fills in `#caseNumber`/`#caseCategory`/`#caseDescription`/`#daily_case` (all show "Loading..." placeholders until the fetch resolves). FOR/AGAINST are `<button>`s that toggle a `selectedStance` without navigating; clicking "Debate This Case" with no stance picked shows a notification and blocks navigation. `localStorage` stance handoff: `index.js` saves `{ topic, stance }` under the `debateCase` key on every FOR/AGAINST click (cleared on deselect), and the plain `debate.html` link just navigates — no URL params.
- ✅ `profile.html` — Sign Up / Log In / Forgot Password (3-way toggle, all in one page), dynamic profile section (username, record, argument profile bars, rebuttal trend, recent case cards, Log Out + Delete Account at the bottom). Signing up auto-logs in (token stored + straight to profile view). Session persists across refresh — page checks `localStorage` on load and calls `showProfile()` if a user is already stored.
- ✅ `debate.html` — built: `#topicSelect` (custom topic input + FOR/AGAINST stance pick + Start Debate / Surprise Me), `#debateScreen` (topic heading + YOU/OPPONENT stance pills), `#chatSection`, and `#verdictScreen`. `debate.js` reads `loadStoredData("debateCase")` (clearing it after use) instead of URL params, and computes the AI's opposite stance for `#debateOpponentLabel`. "Surprise Me" calls `getRandomTopic.php` and randomizes the stance client-side. **The AI chat is real** — `#chatSection` (same `.chat-message.you`/`.opponent` bubble styling as `room.html`) sends each argument to `getAiResponse.php`; the reply is appended and the exchange is held in a JS array (`conversationHistory`), also mirrored to `localStorage` so it survives navigating away (see the `activeDebate` bullet below). A client-side 1-minute countdown (`#debateTimer`) disables input at zero. **When the timer hits zero, `judgeDebate()` fires automatically** — sends the full history to `judgeAiDebate.php` and swaps `#chatSection` for `#verdictScreen` (verdict + 4 score bars, reusing `profile.css`'s stat-bar visual pattern). A **`#forfeitBtn`** ("FORFEIT DEBATE", outlined burgundy → filled on hover, same visual family as `courtroom.html`'s Cancel button) sits below the message form — uses the arm-then-confirm-on-second-click pattern (see Coding Conventions above), then posts to `forfeitAiDebate.php` and shows the verdict immediately as a `LOST`. Also has the header-collapse animation (see below).
- ✅ `courtroom.html` — fully wired: `#createRoom` (topic input + stance pick + Create Room button) requires being logged in (checks `loadStoredData("user")`, same pattern as the profile page's account actions), checks the topic via `checkTopic.php` first (see Key Decisions below), then posts to `createRoom.php`. **Both creating and joining a room now redirect straight to `room.html?room_id=X`** — the host lands in the same live debate screen as the joiner, just showing "WAITING FOR OPPONENT..." with chat disabled until someone joins (`room.js`/`room.html` already handled a room's `open` status this way; only the redirect itself was missing). `#openRoomsList` loads from `listRooms.php` on page load and renders `.room-card`s — each card shows a Join button (`joinRoom.php`), or a Cancel button (`cancelRoom.php`, burgundy danger style matching Delete Account) instead if the viewer is the room's own host (compares `room.host_id` to the logged-in user) — falls back to the static "No open rooms right now" text when empty. Open rooms with nobody joined auto-close after 1 hour (checked lazily in `listRooms.php`, no cron). A user can only be host/joiner of **one** `open`/`in_progress` room at a time (enforced server-side in both `createRoom.php` and `joinRoom.php`). Also checks `getMyActiveRoom.php` on load **and every 3s while the page stays open** (see Key Decisions below) and shows a "Return to your debate" banner (gold-bordered, at the top of the page) if the user has an `in_progress` room they navigated away from — lets them get back in as long as the debate timer hasn't run out.
- ✅ `contact.html` — name/email/message form, submits to `sendMessage.php`, standard notification-on-success pattern.
- ✅ `cases.html` — built as a **public** archive (distinct from `profile.html`'s "My Record," which is the logged-in user's own history; no login required to view), now fully wired: `cases.js` fetches `listCases.php` on load and renders `.case-record`s (case number + verdict, topic, date), falling back to "No cases have been decided yet." when the list is empty. **Shows AI-debated cases only** — `listCases.php` filters `WHERE room_id IS NULL`, so courtroom (real-person) cases are excluded. Deliberately simple otherwise: no opponent/stance detail shown.
- ✅ `room.html` — the live 1-on-1 room debate, reached via `courtroom.html`'s Join flow. Shows the topic, YOU/OPPONENT stance pills (same visual treatment as `debate.html`'s `#debateScreen`, reusing the burgundy/gray convention), a scrolling chat log (`.chat-message.you` right-aligned burgundy, `.chat-message.opponent` left-aligned outlined), a message input, a **`#forfeitBtn`** (same styling/confirm-then-post pattern as `debate.html`'s, but posts to `forfeitRoom.php`), `#verdictScreen`, and a live countdown computed client-side from `room.started_at + 1 minute`. `room.js` polls `getRoomState.php` every 3 seconds (no websockets — plain polling) and guards against non-participants (redirects back to `courtroom.html` if `getRoomState.php` rejects the request). **Judging happens automatically server-side** the moment `getRoomState.php` lazily closes the room (see that endpoint's notes above) — `room.js` just checks the response's `myVerdict` field and swaps to `#verdictScreen` if present, no separate trigger needed on the frontend.
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
  **Second bug found (and fixed) once `forfeitRoom.php` existed to create judged rooms in test data:** the original statement order deleted `rooms WHERE host_id = ?` *before* touching `cases` at all — but `cases.room_id` has an FK to `rooms.id` with no `ON DELETE` clause, so if either participant's `cases` row still referenced that room (i.e. it had already been judged, whether normally or via forfeit), the room delete threw an uncaught `mysqli_sql_exception` (1451) and killed the whole script partway through, leaving the account and room in a stuck half-deleted state. Fixed by reordering: `cases` where `user_id` = the deleted user are deleted outright *first* (their own content), then any *remaining* `cases.room_id` pointing at rooms this user hosted gets nulled (the other participant's row — reference nulled, record preserved, same rule as `opponent_id`) — only then are the rooms/messages themselves deleted. Verified live: a host with an already-forfeited room now deletes cleanly, and the opponent's case row survives with `room_id` (and `opponent_id`, from the existing rule) both `NULL`.
- **"Return to your debate" banner (`courtroom.html`):** if someone navigates away from `room.html` mid-debate, there was previously no way back in — `courtroom.html` only ever listed `open` rooms, not ones you're already `in_progress` in. `getMyActiveRoom.php` + the banner fix this: shows up only while your 1-minute timer is still running (it lazily auto-closes your own expired rooms first, same pattern as everywhere else).
  **Bug found (and fixed):** `checkActiveRoom()` originally only ran once, on page load. If a host created a room and just sat on `courtroom.html` waiting, their room silently disappeared from the open-rooms list the moment someone joined (correctly, since `listRooms.php` only returns `status = 'open'` rooms) — but the banner never appeared to replace it, since nothing ever re-checked. The host had no way back into their own debate short of manually reloading the page. Fixed with `setInterval(checkActiveRoom, 3000)` (same 3s cadence as `room.js`'s polling), and `checkActiveRoom()` now also hides the banner again when there's no longer an active room, not just shows it — so it stays correct in both directions while the page sits open.
- **Daily case selection:** picks "today's case" on-demand (first visit of the day checks/sets `used_on = today` in `daily_topics`) rather than a scheduled cron job, since XAMPP has no task scheduler set up. **Implemented** in `getDailyTopic.php`. Only 3 seed topics exist right now, so the recycle path (reset all `used_on` to `NULL` once every topic has been used) will trigger within days — confirmed working, no admin UI needed since new topics inserted directly via phpMyAdmin (with `used_on = NULL`) get picked up automatically before any recycle is needed.
- **Rebuttal improvement trend has no fixed case-count threshold (reversed from an earlier "needs 20+ cases" gate).** The Argument Profile bars never had that gate — they've always shown a live average from however many cases exist — so the trend text was inconsistent with them by requiring 20. Now it compares the newest case's `rebuttal_score` against the average of every case *before* it (`getProfile.php`), computed via an exact `SUM(rebuttal_score)` query rather than reconstructing a sum from the already-`ROUND()`ed `argument_profile.avg_rebuttal` (an early version of this fix did that and introduced rounding error — caught before shipping by testing the exact math: 80 → 95 correctly computed as +19%, not some rounded-input approximation). Three display states in `profile.js`'s `renderTrend()`: 0 cases → "Complete a case...", exactly 1 case → shows that case's own `latest_rebuttal_score` with no percentage (nothing to compare against yet), 2+ cases → real % change, or "holding steady" if the prior average was exactly 0 (avoids a division-by-zero, e.g. right after a rebuttal-less forfeit). Verified live at each step: 0 → 1 → 2 cases, including a case starting from a genuine 0-rebuttal baseline correctly showing "holding steady" rather than crashing or showing a nonsensical percentage.
- **Reopen requests for closed rooms:** idea confirmed but explicitly deferred — do not build until asked.
- **Categories field:** intentionally denormalized (single string) rather than a proper many-to-many table, since it's currently just for display.
- **CORS is wide open for local dev:** `connection.php` reflects back whatever `Origin` header it receives, so the frontend can run from Live Server (`127.0.0.1:5500`) against XAMPP (`localhost`). Must be restricted to a real allowed-origin list before deployment.
- **Contact form doesn't send email:** `sendMessage.php` just inserts into `contact_messages` — no SMTP configured, so nothing is actually emailed. Someone has to manually check that table for now.
- **"Enter the Court" now points to `courtroom.html`, not `debate.html`:** deliberate — "Enter Courtroom" is one of the app's two named modes, so the hero CTA now matches that mode rather than the AI-debate one. `debate.html` is still reached via its own nav link and the "Debate This Case" flow.
- **Debate-screen header-collapse timer is tied to `startDebate()`, not page load:** this was changed mid-session — an earlier version fired 5s after `debate.html` loaded regardless of what was happening; now it only starts counting once an actual debate begins, so it doesn't collapse the header while someone's still picking a topic.
- **Real debate timer: 1 minute, implemented for both AI debates and room debates.** Same limit for both. `room.html`/`room.js` + `getRoomState.php` enforce it for room debates (`rooms.started_at`, client-side countdown + server-side lazy auto-close). `debate.js` now has a matching client-side-only countdown (no `started_at` column needed there — nothing else needs to read it, unlike a room with two participants).
- **Rooms auto-expire after 1 hour with nobody joined**, and the host can also cancel manually at any time before someone joins (`cancelRoom.php`). Both implemented and tested. Deliberately does *not* try to detect "the host closed their tab/left the site" — browsers have no reliable signal for that (tab close, wifi loss, and a crashed laptop all look identical), so relying on it would be fragile. The 1-hour auto-expire plus manual cancel cover the real cases without needing that.
- **A user can only be in one room at a time** (as host or joiner, `status` `open` or `in_progress`) — enforced server-side in both `createRoom.php` and `joinRoom.php`, tested.
- **Live room debate is a real chat now, not just a placeholder screen** — `room.html` polls for new messages every 3s (plain polling, no websockets/real-time infra). This works *without* the AI provider decision, since the AI only judges the final verdict for room debates — the two humans argue directly with each other. Only the verdict/scoring step at the end is still blocked on that decision.
- **AI's role in room debates is confirmed to stay small once a provider is picked**: judge/score at the end only, never a participant in the actual back-and-forth. Contrast with "Judge a Case," where the AI *is* the opponent.

- **AI provider: decided — Google Gemini, free tier, via the newer "Interactions API"** (`POST https://generativelanguage.googleapis.com/v1beta/interactions`, not the older `generateContent` endpoint — confirmed via live docs, since training knowledge of this API was stale). Request shape: `{model, system_instruction, store: false, input: [{type: "user_input"|"model_output", content: [{type: "text", text}]}]}`; auth via `x-goog-api-key` header. Response has a `steps` array — **the first step is often a `"thought"` step (Gemini's internal reasoning), not the answer** — `callGemini()` in `server/ai/api.php` searches for the `"model_output"` step rather than assuming `steps[0]`.
- **API key storage: gitignored `server/ai/config.php`** (`define('GEMINI_API_KEY', '...')`), not an OS environment variable. A `GEMINI_KEY` Windows user env var was tried first but XAMPP's Apache never picked it up even after multiple full Control Panel restarts (env vars only propagate to a process's *own* startup, not to a long-running parent relaunching a child) — abandoned in favor of the same gitignored-PHP-file pattern `connection.php` already uses. `config.example.php` is the tracked placeholder.
- **Model is `gemini-3.6-flash`, not `gemini-3.7-flash`.** `gemini-3.7-flash` was tried first (it's the model Google's own docs pointed to) but its free-tier quota is only **20 requests per (rolling, short) window** and got exhausted during development/testing alone — a second API key under the same Google Cloud project hit the identical quota (confirms quota is project-scoped, not per-key). Switching to `gemini-3.6-flash` gave a separate, working quota bucket immediately, no new project needed. **If `gemini-3.6-flash` also runs dry during grading/demo, the fix is the same: try another Flash-family model name, or generate a key under a genuinely different Google Cloud project** — don't assume the integration is broken, check for a `too_many_requests`/quota error first.
- **AI's role stays asymmetric by design:** in "Judge a Case," the AI *is* the opponent — `getAiResponse.php` argues `aiStance` directly against the user, called once per message with the full running history. In room debates, the AI is confirmed to stay out of the actual back-and-forth entirely and only judge/score at the end (see below) — the two humans argue with each other, unassisted.
- **Topic debatability is checked wherever a user types their own topic** (both `debate.html`'s custom-topic field and `courtroom.html`'s create-room form — the daily case and "Surprise Me" pull from the pre-seeded `daily_topics` table, so those skip the check entirely). `checkTopic.php` asks Gemini for a strict-JSON debatable/not verdict before `startDebate()`/`createRoom()` runs; a rejection shows a notification and stops there. **Deliberately fails open**: if the Gemini call itself fails (quota, network) or the JSON can't be parsed, both the endpoint and the frontend's `.catch()` treat it as debatable and let the user proceed anyway — a broken AI check should never be the thing that blocks someone from starting a debate. Verified live against a real topic (accepted), gibberish (rejected), and a plain fact with no opposing side (rejected).
- **Conversation history is never persisted to a DB table for AI debates** — `debate.js` holds `conversationHistory` as a plain JS array for the duration of the debate. It's not lost on navigation/refresh, though (see next bullet) — this just means, unlike `room.html`/`room_messages`, there's no server record of an AI debate until it's judged.
- **AI debates now survive navigating away and coming back, via `localStorage` (not a DB table).** `debate.js` saves `{ topic, userStance, aiStance, conversationHistory, startedAt }` under the `activeDebate` key on `startDebate()` and after every message exchange; on page load, `resumeDebate()` restores the chat screen from it (topic/stance labels, replayed chat bubbles, header-collapse state) and resumes the 1-minute countdown from `startedAt` — rather than falling through to the topic-picker. If the timer already ran out while away, it judges immediately using the saved history (or just clears the stale entry if no messages were ever sent). Cleared once `showVerdict()` runs. This is a plain client-side fix — no backend changes — and only survives within the same browser (same tradeoff as everything else in this project that goes through `localStorage`).
- **Judging/verdict is built and live for both modes.** Both `judgeAiDebate.php` and the judging logic inside `getRoomState.php` ask Gemini for **strict JSON only** (`{"verdict"/"winner": ..., ..._score: 0-100 each}`) via a dedicated system instruction — replies aren't guaranteed to be bare JSON (Gemini sometimes wraps it in prose or a code fence), so `extractJsonFromText()` in `server/ai/api.php` defensively finds the first `{` and last `}` and decodes that substring rather than trusting `json_decode()` on the raw text.
  - **AI debates** are judged client-side-triggered: `debate.js`'s 1-minute countdown calls `judgeDebate()` itself when it hits zero, which posts to `judgeAiDebate.php`. One `cases` row is inserted (`opponent_id`/`room_id` both `NULL`).
  - **Room debates** are judged server-side, embedded in `getRoomState.php`'s existing lazy 1-minute auto-close check — no separate endpoint or frontend trigger. Two `cases` rows are inserted (one per participant, sharing `room_id`, each `opponent_id` pointing at the other), verdict computed per side (WON/LOST split, or DRAW/DRAW).
  - **Duplicate-judging guard:** since both room participants poll `getRoomState.php` every 3s, more than one request can race to be "the one" that notices the room expired. The auto-close UPDATE is `SET status='closed' WHERE id=? AND status='in_progress'` — only the request whose `affected_rows > 0` actually flipped the status runs the judging + insert; every other concurrent/later request just sees `status = 'closed'` already and skips straight to reading back the existing `cases` row via `myVerdict`. Verified live: polling both participants near-simultaneously against a naturally-expired room produced exactly one `cases` row per side, no duplicates.
  - `room.js` requires no separate "judging" trigger at all — it just reads `myVerdict` off the normal `getRoomState.php` response and swaps to `#verdictScreen` if present.
- **Forfeit is available in both debate modes and always resolves as a guaranteed loss — no AI judging involved.** A confirm-then-post `#forfeitBtn` sits below the chat input on both `debate.html` and `room.html`. Deliberately skips Gemini entirely (unlike normal judging) since a forfeit's outcome isn't actually in question — `forfeitAiDebate.php` inserts a fixed `LOST`/`0`-across-the-board row directly; `forfeitRoom.php` does the same guarded `status='in_progress' → 'closed'` + `affected_rows` race-check as `getRoomState.php`'s auto-close (so a forfeit racing the 15-min timer, or a double-click, can't double-insert), then inserts fixed `LOST`/`0` for the forfeiter and `WON`/`100` for the opponent. If the room's already closed by the time `forfeitRoom.php` runs, it just returns the caller's existing `cases` row instead of erroring.

## Next Steps (as of last session — 2026-08-25; **deadline is 2026-08-26 11:59 PM**)

All planned features are built and tested: AI debates (chat, judging, forfeit, resume-on-navigation), room debates (chat, judging, forfeit, cancel, return-to-your-debate), auth/profile, court records, contact us, policies. Also fixed tonight, found via real browser/live testing rather than static review: a UTF-8 BOM in `config.php` that was corrupting every AI JSON response; a `let` temporal-dead-zone crash in `debate.js` that silently broke the daily-case auto-start flow (timer + Send button both dead); a `cases.room_id` foreign-key ordering bug in `deleteAccount.php` that crashed mid-delete for any host of an already-judged/forfeited room; and a stale-`debateCase` edge case where resuming an active debate wouldn't clear out an unrelated pending daily-case pick. What's left is pre-deployment hardening only — low priority for a school project graded locally, but worth doing if time allows before the deadline:

1. Lock down the CORS origin in `connection.php` (currently reflects any `Origin` header back, to support running the frontend from Live Server against XAMPP during development).
2. Reconsider the no-verification password reset flow in `resetPassword.php` (no SMTP is configured, so there's no real reset-link/email flow — anyone who knows an account's email can currently reset its password).
3. Keep an eye on the Gemini free-tier quota during grading/demo — if `gemini-3.6-flash` runs dry, switch to another Flash-family model name or a different Google Cloud project (see the AI provider decision above); this is an expected possibility, not a sign the integration is broken.
4. **Recommended before submission**: a full manual click-through of every page/flow in a real browser. Most of this session's real bugs (the BOM, the TDZ crash, the FK ordering) were only caught once actual browser/live testing happened, not from code review alone — worth doing one pass end-to-end (signup → daily case debate → forfeit/finish → profile record; create room → join from a second account → chat → forfeit/finish → court records) before calling it done.
