# Security Policy

## Supported Versions

| PHP | Supported | Recommended minimum |
| --- | --- | --- |
| 8.5 | Yes | 8.5.12 |
| 8.4 | Yes | 8.4.27 |
| 8.3 | Yes | 8.3.36 |
| 8.2 | Yes | 8.2.35 |
| 8.1 and older | No | Upgrade required |

Laravel 10, 11 and 12 are supported. All of these can run on PHP 8.2 and newer.

These recommended minimums are required to fix CVE-2026-109618 and CVE-2026-109619. PHP 8.1 and older no longer receive security updates from the PHP project.

## Critical PHP Advisory - 21 October 2026

PHP 8.5.12, 8.4.27, 8.3.36 and 8.2.35 fix two critical vulnerabilities that can allow unauthenticated remote code execution:

- CVE-2026-109618 - RCE via OOM corruption.
- CVE-2026-109619 - RCE via stale global state.

All PHP users are strongly encouraged to update immediately. This package cannot mitigate these vulnerabilities; updating PHP is the only complete fix.

Read the official advisory: https://www.php.net/archive/2026.php#2026-10-21-5

## Reporting a Vulnerability

Please report security issues privately through the repository's GitHub security advisories:

https://github.com/Ngoni-Sendama/dischub-php/security/advisories/new

Do not open a public issue for a security vulnerability.
