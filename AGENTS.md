# AGENTS.md

## Overview

This package provides an additional layer on top of the TYPO3 testing framework.
It helps creating functional tests using frontend requests with the help of Symfony's BrowserKit component.
It leverages Symfony "Application Tests" for TYPO3.

- Package: `r3h6/typo3-browserkit-testing`
- Namespace: `R3H6\Typo3BrowserkitTesting\`
- PHP: `>= 8.2`
- TYPO3: `12.4`, `13.4`

This project uses DDev as a local dockerized development environment.

## Commands

- Composer: `ddev composer`
- PHP shell: `ddev php`
- Run PHPStan analysis: `ddev composer ci:php:stan`
- Apply code style rules (php-cs-fixer): `ddev composer fix:php:cs`
- Run all functional tests: `ddev runTests tests/Functional/`

## Code Style Guidelines

- Respect `.editorconfig` rules
- Apply php-cs-fixer: `ddev composer fix:php:cs`
- Type hints required for all parameters and return types
- PHPDoc only when types cannot be expressed in PHP
- Follow clean code principles (SRP, DRY, KISS, Readability over Cleverness, Separation of Concerns, ...)

## Project Structure

```
.Build/                # Local TYPO3 environment (bin/, public/, vendor/)
.github/               # Do not modify except explicit asked
.ddev/                 # DDev configuration
Build/                 # Utility scripts
config/                # Local TYPO3 environment configuration
res/                   # Resources
src/                   # Source code for the framework
tests/                 # Tests for testing the framework itself
var/                   # Runtime files from local TYPO3 environment (logs, cache)
```

TYPO3 CMS core packages and TYPO3 testing framework are installed in `.Build/vendor/typo3/`.

## Commit message format rules

- Prefix summary line with a keyword: `[TASK]`, `[FEATURE]`, `[BUGFIX]`, `[DOCS]`
- Keep the whole summary line under 52 characters if possible, but below 72 in any case
- Use following pattern: if applied, this commit will **"your subject line here"**

## Security

- Never hard-code LLM or API keys
- Do not log or echo API keys or full provider responses

## External documentation and resources

Official TYPO3 documentation:
- [Functional testing with the TYPO3 testing framework ](https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Testing/FunctionalTesting/Index.html)

Good examples for extensions:
- [Example TYPO3 extension for code quality checks and automated tests](https://github.com/TYPO3BestPractices/tea)
