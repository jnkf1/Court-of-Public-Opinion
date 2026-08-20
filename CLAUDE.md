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
│   │   ├── debate.html        (Judge a Case — vs AI; currently EMPTY, see below)
│   │   ├── courtroom.html     (not yet built — rooms lobby)
│   │   ├── cases.html         (not yet built — Court Records / past cases list)
│   │   └── policies/
│   │       ├── privacy_policy.html
│   │       └── ai_policy.html
│   ├── scripts/
│   │   ├── env.js             (BASE_URL constant)
│   │   ├── axios.min.js
│   │   ├── helper.js          (loadStoredData / saveStoredData / showNotification)
│   │   ├── profile.js         (signup/login/forgot-password/profile logic — renamed from profie.js)
│   │   ├── debate.js          (Judge a Case page logic — written, but debate.html has no matching markup yet)
│   │   └── index.js           (homepage daily-case FOR/AGAINST stance selection + link wiring)
│   └── styles/
│       ├── style.css          (homepage)
│       ├── profile.css        (profile page — self-contained, imports its own fonts/vars like style.css does)
│       ├── policies.css       (privacy/AI policy pages — same self-contained pattern)
│       └── debate.css         (currently EMPTY)
└── server/
    ├── database/
    │   └── connection.php     (mysqli connection: $mysql; also sets the dev CORS header)
    └── profile/
        ├── signUp.php
        ├── logIn.php
        ├── getProfile.php
        ├── resetPassword.php  (no-verification password reset: email + new password)
        └── deleteAccount.php  (token-authed; blocks deletion via FK error if user has case history)
```

Note: `signUp.php`/`logIn.php`/etc. live under `server/profile/`, not directly under `server/` — the folder name is about the *feature* (profile/auth), not the page.

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id),
    FOREIGN KEY (joiner_id) REFERENCES users(id)
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
| `deleteAccount.php` | POST | `token` | Resolves user by token, deletes the row. If the user still has rows in `cases` (FK, MySQL 1451), returns `"Can't delete an account with existing case history."` instead of crashing — cascading deletes were deliberately *not* implemented since a case row also belongs to the opponent's history. |

## Frontend Pages (current status)

- ✅ `index.html` — landing page, masthead layout, nav, hero, "Case of the Day" section (currently static topic/case number — daily-case backend not built yet). FOR/AGAINST are `<button>`s that toggle a `selectedStance` without navigating; clicking "Debate This Case" with no stance picked shows a notification and blocks navigation, otherwise it sets the link's `href` to `debate.html?topic=...&stance=...` right before following it (`index.js`).
- ✅ `profile.html` — Sign Up / Log In / Forgot Password (3-way toggle, all in one page), dynamic profile section (username, record, argument profile bars, rebuttal trend, recent case cards, Log Out + Delete Account at the bottom). Signing up auto-logs in (token stored + straight to profile view). Session persists across refresh — page checks `localStorage` on load and calls `showProfile()` if a user is already stored.
- ⬜ `debate.html` — **currently an empty file**, along with `debate.css`. `debate.js` is already written and expects specific markup that doesn't exist yet: `#topicSelect`, `#debateScreen`, `#debateTopicLabel`, `#debateStanceLabel`, `#customTopic`, `#startCustomDebate`, `#startRandomDebate`, and `.stance-btn` elements with `data-stance`. It reads `topic`/`stance` from the URL and skips straight to the debate screen if both are present (that's how `index.html`'s FOR/AGAINST → Debate This Case flow is meant to land here) — **but there's nothing to land on yet.** This is the next real blocker.
- ⬜ `courtroom.html` — not started (rooms lobby: create room / browse open rooms)
- ⬜ `cases.html` — not started (Court Records — list of past cases)
- ⬜ Live courtroom/debate screen (real-time 1-on-1 room debate) — not started
- ⬜ Case result display tied to real data — no verdict screen exists yet since `debate.html` is empty; scoring/AI-judging not wired to the database
- ✅ `policies/privacy_policy.html`, `policies/ai_policy.html` — static content pages matching the site's newspaper theme, linked from the footer on `index.html` and `profile.html`

## Key Decisions & Open Questions

- **Sessions vs. token-only auth:** currently token-only (no PHP `$_SESSION`). User was undecided on adding real PHP sessions later — revisit if needed.
- **Token lifecycle:** generated once at signup, never rotated on login. If this needs to change (e.g. rotate on each login, or add expiry), treat it as a deliberate decision, not a bug fix — several endpoints depend on the token being stable.
- **Password reset is intentionally insecure:** `resetPassword.php` has no way to confirm the requester owns the email (no reset link/token sent). Chosen over a real email-based flow specifically because SMTP isn't set up in this project. Acceptable for a local/school project; would need a real token+email flow before any real deployment.
- **Account deletion doesn't cascade:** `cases.user_id`/`opponent_id` reference `users.id` with no `ON DELETE CASCADE`. Deleting an account with case history currently just fails with a friendly message rather than cascading — because cascading would also delete the *opponent's* case record. Revisit as a deliberate design choice (e.g. anonymize instead of delete) rather than just adding a cascade.
- **Daily case selection:** decided to pick "today's case" on-demand (first visit of the day checks/sets `used_on = today` in `daily_topics`) rather than a scheduled cron job, since XAMPP has no task scheduler set up. **Not implemented yet** — still using static HTML content in `index.html`.
- **Rebuttal improvement trend:** requires 20+ total cases before showing a real percentage; otherwise shows "Complete N more cases to see your trend."
- **Reopen requests for closed rooms:** idea confirmed but explicitly deferred — do not build until asked.
- **Categories field:** intentionally denormalized (single string) rather than a proper many-to-many table, since it's currently just for display.
- **CORS is wide open for local dev:** `connection.php` reflects back whatever `Origin` header it receives, so the frontend can run from Live Server (`127.0.0.1:5500`) against XAMPP (`localhost`). Must be restricted to a real allowed-origin list before deployment.

## Next Steps (as of last session)

1. **Build `debate.html` + `debate.css`** — `debate.js` already expects specific markup (`#topicSelect`, `#debateScreen`, stance buttons, etc.) that doesn't exist yet. This is what "Debate This Case" currently lands on as a blank page.
2. Build `courtroom.html` (rooms lobby) HTML/CSS
3. Build `cases.html` (Court Records) HTML/CSS
4. Once pages are done, return to backend:
   - Daily topic selection endpoint
   - AI integration for Judge a Case (chat exchange + verdict/scoring)
   - Rooms endpoints (create room, list open rooms, join room)
   - Live 1-on-1 courtroom debate + AI-judged verdict
   - Wire `cases.html` to real data
5. Before any real deployment: lock down the CORS origin, and reconsider the no-verification password reset flow.
