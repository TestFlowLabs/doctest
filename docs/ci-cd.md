# CI/CD Integration

## GitHub Actions

```yaml
name: Documentation Tests

on:
  push:
    branches: [main]
    paths: ['docs/**/*.md', 'README.md']
  pull_request:
    paths: ['docs/**/*.md', 'README.md']

jobs:
  doctest:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run documentation tests
        run: vendor/bin/doctest --junit=doctest-results.xml

      - name: Upload test results
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: doctest-results
          path: doctest-results.xml
```

## GitLab CI

```yaml
doctest:
  stage: test
  image: php:8.4-cli
  before_script:
    - composer install --no-interaction --prefer-dist
  script:
    - vendor/bin/doctest --junit=doctest-results.xml
  artifacts:
    when: always
    reports:
      junit: doctest-results.xml
```

## Pre-commit Hook

Save as `.git/hooks/pre-commit` and make executable (`chmod +x`):

```bash
#!/bin/bash

STAGED_MD=$(git diff --cached --name-only --diff-filter=ACM | grep '\.md$')

if [ -n "$STAGED_MD" ]; then
    echo "Running doctest on staged markdown files..."
    vendor/bin/doctest --stop-on-failure $STAGED_MD

    if [ $? -ne 0 ]; then
        echo "Documentation tests failed. Commit aborted."
        exit 1
    fi
fi
```

## Reporter Formats

### JUnit XML

Use `--junit=path.xml` for CI systems that support JUnit report parsing (GitHub Actions, GitLab CI, Jenkins, CircleCI).

### JSON

Use `--json=path.json` for custom tooling or dashboards.
