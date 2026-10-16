# Security Policy

Tyro Login is an authentication package, so we take security reports seriously and appreciate responsible disclosure.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :x:                |

Security fixes are released on the latest 2.x line. Older major versions are no longer maintained.

## Reporting a Vulnerability

Please report suspected vulnerabilities **privately**. Do not open a public issue, and do not disclose the problem publicly until a fix has been released.

Preferred method: use GitHub's private vulnerability reporting from the repository's **Security** tab ("Report a vulnerability").

Please include:

- a description of the vulnerability and its impact
- steps to reproduce, and a proof of concept if you have one
- the affected version(s) and relevant configuration
- any suggested fix or mitigation

## What to Expect

- We aim to acknowledge reports within 3 business days.
- We will investigate, keep you updated on progress, and let you know whether the report is accepted or declined.
- Confirmed issues are fixed in a patch release on the latest 2.x line, and we are happy to credit you in the release notes if you wish.

## Scope

In scope: authentication, session handling, passkeys (WebAuthn), TOTP 2FA, email OTP, magic links, password reset, social login, and any other code shipped in this package.

Out of scope: vulnerabilities in third-party dependencies (please report those upstream) and issues that require an already-compromised host or elevated access.

## Preferred Languages

We prefer all communications in English.
