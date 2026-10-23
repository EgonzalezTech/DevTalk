# DevTalk — Lab 5 Patch Log

## Patch #1: Fix Login SQL Injection

**Lab 4 Finding:** Login SQL Injection  
**File:** `public/login.php`  
**OWASP Category:** A05 — Injection

### Before the Fix

The login page used the username and password directly in the SQL query. In Lab 4, I was able to bypass the login using SQL Injection.

### Root Cause

The application built SQL queries by joining user input directly into the query.

### Changes Made

I replaced the SQL query with a prepared statement. I also added `password_verify()` to check hashed passwords and regenerated the session ID after login.

### Security Test After the Fix

I repeated the SQL Injection attack using `admin' --`. The application rejected the login and displayed "Invalid username or password."

### Normal Functionality Test

I created a new user named `testlab5` and successfully logged in.

### Screenshot Evidence

- `docs/screenshots/patch-01-login-sqli.png`

---

## Patch #2: Fix Search SQL Injection

**Lab 4 Finding:** Search SQL Injection  
**File:** `public/search.php`  
**OWASP Category:** A05 — Injection

### Before the Fix

The search page used user input directly in the SQL query. An attacker could change the query using SQL Injection.

### Root Cause

The application joined the search text directly into SQL.

### Changes Made

I used a prepared statement with bound parameters for the search query.

### Security Test After the Fix

I repeated the SQL Injection payload. The application displayed "No matches found."

### Normal Functionality Test

I searched for `security`, and DevTalk returned the expected topic. I also checked the PHP syntax and found no errors.

### Screenshot Evidence

- `docs/screenshots/patch-02-search-sqli.png`

---

## Patch #3: Fix Reflected XSS in Search

**Lab 4 Finding:** Reflected XSS  
**File:** `public/search.php`  
**OWASP Category:** A05 — Injection

### Before the Fix

The search page displayed user input without properly encoding it. JavaScript could execute in the browser.

### Root Cause

The search value was displayed directly in the HTML response.

### Changes Made

I used `htmlspecialchars()` with `ENT_QUOTES` and UTF-8 to safely display search input.

### Security Test After the Fix

I repeated the payload `<script>alert('XSS Test')</script>`. The application displayed it as text, and the JavaScript did not execute.

### Normal Functionality Test

The normal search feature continued working correctly.

### Screenshot Evidence

- `docs/screenshots/patch-03-search-xss.png`

---

## Patch #4: Fix SQL Error Disclosure

**Lab 4 Finding:** Detailed SQL error messages  
**File:** `public/search.php`  
**OWASP Category:** A10 — Mishandling of Exceptional Conditions

### Before the Fix

The search page could display detailed database errors and the SQL query to the user.

### Root Cause

The application displayed exception details directly in the page.

### Changes Made

I changed the error handling to log technical details on the server and display a generic error message to the user.

### Security Test After the Fix

I tested the search using a single quotation mark. The page continued working without displaying a database error.

A controlled database exception test is still pending.

### Normal Functionality Test

The search feature returned results normally.

### Screenshot Evidence

- `docs/screenshots/patch-04-search-errors.png`

### Testing

**Before the fix:**

The application displayed detailed database errors that could reveal internal SQL information.

**After the fix:**

I temporarily changed the table name in `public/search.php` from `topics` to `topics_test_error` to trigger a database exception.

I searched for `security`. The application displayed the generic message:

"Search is temporarily unavailable. Please try again."

The page did not display SQL errors, database table names, or internal file paths.

After the test, I restored the original table name. I ran `php -l public/search.php` and confirmed there were no syntax errors. I also checked `git diff` to confirm the temporary change was removed.

### Screenshot Evidence

- `docs/screenshots/patch-04-sql-error-handled.png`

### Result

## The tested database error was handled without exposing internal SQL details to the user.

## Patch #5: Fix IDOR in Edit Topic

**Lab 4 Finding:** Insecure Direct Object Reference  
**File:** `public/edit_topic.php`  
**OWASP Category:** A01 — Broken Access Control

### Before the Fix

A logged-in user could edit another user's topic by changing the topic ID.

### Root Cause

The application checked whether the user was logged in but did not verify topic ownership.

### Changes Made

I added a `user_id` ownership check when loading and updating a topic.

### Security Tests After the Fix

I logged in as Alice and tried to edit Bob's topic, ID 3. The application blocked unauthorized access.

I also sent a direct POST request to modify Bob's topic. The server returned HTTP 404.

I checked the SQLite database and confirmed that Bob's topic was not modified.

### Normal Functionality Test

Alice successfully edited her own topic, ID 2, and saved the changes.

### Screenshot Evidence

- `docs/screenshots/patch-05-idor-database.png`

The original `patch-05-idor.png` shows an earlier test with ID 6, which did not exist. Additional screenshots of the valid ID 3 tests should be saved.

### Screenshot Evidence

- docs/screenshots/patch-05-idor-blocked.png
- docs/screenshots/patch-05-idor-post.png
- docs/screenshots/patch-05-idor-database.png

---

## Patch #6: Fix CSRF and Insecure Topic Deletion

**Lab 4 Finding:** Insecure topic deletion  
**Files:** `public/delete_topic.php`, `public/topic.php`, `src/helpers.php`  
**OWASP Category:** A01 — Broken Access Control

### Before the Fix

Any logged-in user could delete a topic by opening a URL containing its ID.

### Root Cause

The application allowed deletion using GET requests. It did not check CSRF tokens or verify topic ownership.

### Changes Made

I changed topic deletion to use POST requests. I added CSRF token generation and validation. I also added an ownership check using `user_id`.

### Security Tests After the Fix

- GET deletion was blocked with HTTP 405.
- POST without a CSRF token was blocked with HTTP 403.
- Alice could not delete Bob's topic, even with a valid CSRF token. The server returned HTTP 404.

### Normal Functionality Test

I created a temporary topic as Alice and deleted it using the Delete button. The topic was successfully removed from the database.

### Screenshot Evidence

- `docs/screenshots/patch-06-delete-get-blocked.png`
- `docs/screenshots/patch-06-delete-csrf-blocked.png`
- `docs/screenshots/patch-06-delete-ownership-blocked.png`
- `docs/screenshots/patch-06-delete-normal.png`

---

## Additional Security Changes

### Password Hashing

**File:** `public/register.php`

Previously, DevTalk stored user passwords as plain text.

I changed registration to use `password_hash()` and updated login to use `password_verify()`.

I also updated the existing local laboratory accounts to use password hashes. The actual passwords are not included in this log.

### Error Configuration

**File:** `src/config.php`

I disabled detailed PHP error display and enabled server-side error logging.

This helps prevent internal application information from being displayed to users.

---

## Remaining Work

- Complete a controlled exception-handling test for Patch #4.
- Save screenshots from the valid IDOR test using Bob's topic ID 3.
- Fix the remaining Lab 4 vulnerabilities, including SSRF.
- Review other potentially unsafe actions, including comment deletion.
- Run final regression tests.
- Create descriptive Git commits and push the completed work to GitHub.

## Patch #7 — Server-Side Request Forgery (SSRF)

**File:** `public/fetch_image.php`

**Vulnerability:** Server-Side Request Forgery (SSRF)

### Problem

The application accepted an image URL from the user without checking if it was safe. An attacker could use the `file://` protocol to read local files from the server.

### Root Cause

The application passed the user-provided URL directly to `file_get_contents()` without validating the protocol or destination.

### Fix

I replaced the unsafe image fetching code with cURL and added security checks.

The application now:

- Accepts only HTTPS URLs from `images.unsplash.com`.
- Rejects local files and unauthorized destinations.
- Does not follow HTTP redirects.
- Limits the download size to 5 MB.
- Checks that the downloaded content is an allowed image type.
- Uses generic error messages.

### Testing

**Before the fix:**

I tested the endpoint using `file:///etc/hosts`. The application allowed access to local file content.

**After the fix:**

I repeated the same request. The application displayed `Image URL not allowed.` and did not reveal the file content.

**Normal functionality:**

I tested a valid HTTPS image from Unsplash. The image loaded successfully in the browser.

### Screenshot Evidence

- `docs/screenshots/patch-07-ssrf-blocked.png`
- `docs/screenshots/patch-07-image-working.png`

### Result

The tested SSRF attack was blocked, and the allowed image functionality continued working.
