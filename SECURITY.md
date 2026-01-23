# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of Health Checker for Joomla seriously. If you have discovered a security vulnerability, please report it to us as described below.

### Where to Report

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via one of these methods:

1. **GitHub Security Advisories** (preferred): Use the [Security Advisories](https://github.com/mySites-guru/HealthCheckerForJoomla/security/advisories/new) feature
2. **Email**: Send details to [phil@phil-taylor.com](mailto:phil@phil-taylor.com)

### What to Include

Please include the following information in your report:

- Type of vulnerability (e.g., XSS, SQL injection, authentication bypass)
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the vulnerability, including how an attacker might exploit it

### Response Timeline

- **Initial Response**: Within 48 hours, we will acknowledge receipt of your vulnerability report
- **Status Update**: Within 7 days, we will send a more detailed response indicating next steps
- **Fix Timeline**: We aim to release a security patch within 30 days for critical vulnerabilities
- **Disclosure**: We will coordinate public disclosure timing with you after a fix is released

### What to Expect

After you submit a report, we will:

1. Confirm receipt of your vulnerability report
2. Investigate and validate the vulnerability
3. Determine the severity and impact
4. Develop and test a fix
5. Release a security update
6. Publicly disclose the vulnerability (with credit to you, if desired)

### Safe Harbor

We support safe harbor for security researchers who:

- Make a good faith effort to avoid privacy violations, destruction of data, and interruption or degradation of our services
- Only interact with accounts you own or with explicit permission of the account holder
- Do not exploit a security vulnerability beyond what is necessary to demonstrate it
- Report vulnerabilities promptly
- Keep vulnerability details confidential until we've had a reasonable time to address it

We will not pursue legal action against researchers who follow these guidelines.

## Security Best Practices for Users

### Installation

- Always download Health Checker for Joomla from official sources:
  - [GitHub Releases](https://github.com/mySites-guru/HealthCheckerForJoomla/releases)
- Verify package checksums when provided

### Configuration

- Health Checker is **Super Admin only** by design - never modify access controls
- Keep your Joomla installation and all extensions up to date
- Use the built-in security checks to monitor your Joomla installation
- Review warning and critical findings regularly

### Updates

- Subscribe to [GitHub releases](https://github.com/mySites-guru/HealthCheckerForJoomla/releases) for update notifications
- Apply security updates promptly when released
- Test updates in a staging environment before production deployment

## Known Security Considerations

### By Design

Health Checker for Joomla has the following security characteristics by design:

- **Super Admin Only**: All functionality requires Super Admin privileges
- **No Database Storage**: Results are stored in session only (no persistent storage)
- **Manual Execution**: No background processes or scheduled tasks in the free version
- **Read-Only Checks**: Health checks only read data, they never modify your site
- **No External Requests**: Core checks do not make external HTTP requests

### Third-Party Integrations

Optional plugins (Akeeba Backup, Admin Tools) integrate with third-party extensions. Security of those integrations depends on:

- The security of the integrated extension itself
- Proper API usage as documented by the extension author
- Regular updates to both Health Checker and the integrated extension

## Disclosure Policy

When we release security updates:

1. **Security Advisory**: Published on GitHub Security Advisories
2. **Release Notes**: Included in the GitHub release with severity rating
3. **Credit**: Security researchers are credited (unless they prefer anonymity)
4. **CVE**: We request CVE IDs for significant vulnerabilities

## Contact

For security concerns, contact:
- **Email**: [phil@phil-taylor.com](mailto:phil@phil-taylor.com)
- **GitHub**: [@PhilETaylor](https://github.com/PhilETaylor)

For general support (non-security), please use [GitHub Issues](https://github.com/mySites-guru/HealthCheckerForJoomla/issues).

## License

Security-related code changes are released under the same GPL v2+ license as the rest of the project.
