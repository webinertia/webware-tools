# {{PACKAGE_NAME}}

[![PHP Version](https://img.shields.io/packagist/php-v/{{PACKAGE_NAME}})](https://packagist.org/packages/{{PACKAGE_NAME}})
[![Latest Version](https://img.shields.io/packagist/v/{{PACKAGE_NAME}})](https://packagist.org/packages/{{PACKAGE_NAME}})
[![License](https://img.shields.io/github/license/{{ORG}}/{{REPO}})](LICENSE)
[![Continuous Integration](https://github.com/{{ORG}}/{{REPO}}/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/{{ORG}}/{{REPO}}/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/{{ORG}}/{{REPO}}/graph/badge.svg)](https://codecov.io/gh/{{ORG}}/{{REPO}})
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2F{{ORG}}%2F{{REPO}}%2F{{DEFAULT_BRANCH}})](https://dashboard.stryker-mutator.io/reports/github.com/{{ORG}}/{{REPO}}/{{DEFAULT_BRANCH}})

Placeholders: `{{PACKAGE_NAME}}` = composer name (owner/package), `{{ORG}}` =
GitHub org, `{{REPO}}` = GitHub repo, `{{DEFAULT_BRANCH}}` = default branch
(e.g. `0.1.x`). CI and codecov badge URLs carry no `?branch=` parameter so they
always point at the default branch. The Stryker badge URL embeds the branch
segment — update it in both the badge URL and the dashboard link whenever the
default branch changes.
