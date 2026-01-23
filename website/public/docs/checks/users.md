---
url: /docs/checks/users.md
---
# Users Checks

User checks examine user accounts, authentication settings, and access control. These help identify security risks related to user management.

**Total checks in this category: 12**

## Admin User Security (4 checks)

### Super Admin Count

Monitors number of Super Admin accounts.

* **Good**: 1-2 Super Admins
* **Warning**: 3-5 Super Admins
* **Critical**: More than 5 Super Admins

**Why it matters**: Too many Super Admins increases attack surface and makes accountability difficult.

**Best practice**: Keep 2 Super Admins (primary + backup for emergencies)

### Default Username Avoided

Checks if default "admin" username is still in use.

* **Good**: No user named "admin"
* **Warning**: User "admin" exists

**Why it matters**: "admin" is the first username attackers try in brute-force attacks.

**How to fix**:

1. Create new Super Admin with unique username
2. Log in as new Super Admin
3. Delete "admin" account

### Super Admin Emails Unique

Verifies each Super Admin has unique email address.

* **Good**: All unique emails
* **Warning**: Duplicate emails found

**Why it matters**: Shared emails prevent proper attribution and password recovery.

**How to fix**: Update duplicate accounts with unique email addresses

### Super Admin Last Login

Checks when Super Admins last logged in.

* **Good**: Within last 30 days
* **Warning**: 30-90 days since login
* **Critical**: Over 90 days inactive

**Why it matters**: Inactive admin accounts may belong to former staff and pose security risks.

**How to fix**: Disable or delete inactive admin accounts

## User Account Health (4 checks)

### Inactive Admin Accounts

Identifies admin accounts not used recently.

* **Good**: All admins active within 180 days
* **Warning**: Some admins inactive 180+ days

**Why it matters**: Stale accounts often belong to former employees or contractors.

**How to fix**:

1. Review inactive accounts
2. Contact users to verify need
3. Disable or delete if no longer needed

### Blocked Users Count

Monitors number of blocked user accounts.

* **Good**: Less than 5 blocked users
* **Warning**: 5-20 blocked users
* **Critical**: More than 20 blocked users

**Why it matters**: Excessive blocked users may indicate:

* Spam registration attacks
* Brute-force attempts
* Configuration issues

**How to review**: Users → Manage → Filter by Status: Blocked

### Unactivated Users

Checks for users who haven't activated accounts.

* **Good**: Less than 10 unactivated
* **Warning**: 10-50 unactivated
* **Critical**: More than 50 unactivated

**Why it matters**: Excessive unactivated users indicate:

* Spam registrations
* Email delivery problems
* Activation process issues

**How to clean up**:

1. Users → Manage → Filter by Status: Unactivated
2. Review and delete spam accounts
3. Check email sending works
4. Consider adding CAPTCHA to registration

### Duplicate Email Addresses

Identifies multiple accounts using same email.

* **Good**: All emails unique
* **Warning**: Duplicate emails found

**Why it matters**: Indicates:

* Potential account takeover attempts
* Configuration errors
* User confusion

**How to fix**: Contact users and assign unique emails

## User Configuration (6 checks)

### User Registration Settings

Reviews registration configuration.

* **Good**: Registration disabled or properly secured
* **Warning**: Public registration without protection

**Why it matters**: Open registration attracts spam and fake accounts.

**Recommendations**:

* Disable if not needed
* Enable admin approval
* Add CAPTCHA
* Use email verification
* Monitor new registrations

### New User Group Assignment

Checks default group for new users.

* **Good**: Registered (standard user group)
* **Warning**: Higher privilege groups
* **Critical**: Admin or Super Admin

**Why it matters**: New users should have minimal permissions.

**How to fix**: System → Global Configuration → Users → New User Registration Group: Registered

### Guest User Group Configured

Verifies guest access settings.

* **Good**: Guest group properly configured
* **Warning**: Guests have excessive permissions

**Why it matters**: Guests (non-logged-in users) shouldn't access admin content.

**How to check**: Users → Groups → Guest → Permissions

### CAPTCHA on Registration

Checks if CAPTCHA is enabled for registration.

* **Good**: CAPTCHA enabled
* **Warning**: No CAPTCHA protection

**Why it matters**: CAPTCHA prevents automated spam registrations.

**How to enable**:

1. System → Plugins → CAPTCHA - reCAPTCHA
2. Get API keys from Google reCAPTCHA
3. Configure plugin
4. System → Global Configuration → Users → CAPTCHA: reCAPTCHA

### Session Lifetime

Checks user session duration.

* **Good**: 15-60 minutes
* **Warning**: Over 60 minutes
* **Critical**: Over 24 hours

**Why it matters**: Long sessions increase risk of session hijacking.

**How to configure**: System → Global Configuration → System → Session Lifetime

### Shared Sessions Setting

Verifies session isolation between frontend/backend.

* **Good**: Disabled (sessions not shared)
* **Warning**: Enabled

**Why it matters**: Shared sessions can allow frontend login to access admin.

**How to fix**: System → Global Configuration → System → Shared Sessions: No

## Common Issues & Solutions

### Too Many Super Admins

**Symptoms**: Difficulty tracking who made changes

**Solutions**:

1. Audit current Super Admins
2. Downgrade unnecessary Super Admins to Manager or Administrator
3. Keep only 2 Super Admins (primary + backup)
4. Document who has Super Admin access
5. Review quarterly

### Spam Registrations

**Symptoms**: Hundreds of fake user accounts

**Solutions**:

1. Enable CAPTCHA:
   * System → Plugins → CAPTCHA - reCAPTCHA
   * Configure with Google reCAPTCHA keys
2. Require admin approval:
   * System → Global Configuration → Users → New User Account Activation: Admin
3. Disable registration if not needed:
   * System → Global Configuration → Users → Allow User Registration: No
4. Clean up spam accounts:
   ```sql
   DELETE FROM #__users WHERE registerDate < '2024-01-01' AND activation != '';
   ```

### Inactive Administrator Accounts

**Symptoms**: Old employee accounts still active

**Solutions**:

1. Generate inactive admin report:
   * Users → Manage
   * Filter by Group: Administrator or higher
   * Sort by Last Visit Date
2. Contact users to verify need
3. Disable accounts (don't delete immediately):
   * Edit user → Status: Blocked
4. After 30 days, delete if still unused
5. Document access reviews

### Weak Password Policies

**Symptoms**: Users with simple passwords like "password123"

**Solutions**:

1. Configure password requirements:
   * Install password policy plugin
   * Set minimum length (12+ chars)
   * Require mixed case, numbers, symbols
2. Force password resets for weak passwords
3. Implement regular password rotation (every 90 days)
4. Consider passwordless authentication (WebAuthn)

### Session Security Issues

**Symptoms**: Users logged out too frequently or not frequently enough

**Solutions**:

1. Set appropriate session lifetime:
   * Admin: 15-30 minutes
   * Frontend: 30-60 minutes
2. Disable shared sessions
3. Use secure cookies:
   ```php
   public $cookie_secure = true;
   public $cookie_samesite = 'Strict';
   ```
4. Enable session IP checking (if stable IPs)

## User Management Best Practices

### Access Control

* Follow principle of least privilege
* Use specific access levels (Author, Editor, Manager)
* Don't overuse Administrator/Super Admin
* Review permissions quarterly

### Password Security

* Enforce strong passwords (12+ chars)
* Require 2FA for admin accounts
* Never share passwords
* Use password manager
* Rotate passwords every 90 days

### Account Lifecycle

* Standardized onboarding process
* Document access levels
* Regular access reviews (quarterly)
* Immediate offboarding process
* Disable first, delete after 30 days

### Monitoring

* Review new registrations weekly
* Monitor failed login attempts
* Check for unusual admin activity
* Audit user group changes
* Log all privilege escalations

### Registration Management

* Disable if not needed
* Enable CAPTCHA if public
* Require email verification
* Consider admin approval
* Clean up unactivated accounts monthly

## Next Steps

* [Security Checks](./security.md) - Evaluate authentication security
* [Extensions Checks](./extensions.md) - Review extension permissions
* [Content Quality Checks](./content.md) - Monitor user-generated content
