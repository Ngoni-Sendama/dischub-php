# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Read-only MCP server (`mcp/`) for documentation search, payment input validation and payment status lookups.

## [1.0.0] - 2026-10-26

### Added

- Redirect integration for creating orders, generating payment URLs and checking payment status.
- Non-redirect integration for EcoCash, InnBucks, Omari and ZimSwitch.
- Validation for order IDs, amounts, currencies, payment methods, URLs and phone numbers.
- Configurable HTTP and connection timeouts.
- Laravel service provider, configuration publishing and environment-based configuration.
- Test suite using Orchestra Testbench and HTTP fakes.
- GitHub Actions CI for PHP 8.2, 8.3, 8.4 and 8.5.
- Dependabot configuration for Composer and GitHub Actions.
- Security policy and PHP security guidance.

### Changed

- Requires Laravel 12 and PHP 8.2 or newer.
- Sender, EcoCash and Omari phone numbers are validated against the documented formats.
- Redirect and non-redirect integrations are exposed through separate `redirect()` and `nonRedirect()` methods.

### Removed

- Laravel 10 and Laravel 11 support. Both versions are end of life and no longer receive security fixes.
