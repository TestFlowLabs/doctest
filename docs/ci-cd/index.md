# CI/CD Integration

DocTest is designed to run in CI pipelines. It returns proper exit codes and supports JUnit XML output for test dashboards.

## GitHub Actions

### Basic Setup

```yaml
name: Documentation Tests
on: [push, pull_request]

jobs:
  doctest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - run: composer install --no-interaction

      - name: Run DocTest
        run: vendor/bin/doctest
```

### With JUnit Reporting

```yaml
name: Documentation Tests
on: [push, pull_request]

jobs:
  doctest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - run: composer install --no-interaction

      - name: Run DocTest
        run: vendor/bin/doctest

      - name: Publish Test Results
        uses: dorny/test-reporter@v1
        if: always()
        with:
          name: DocTest Results
          path: build/doctest.xml
          reporter: java-junit
```

For this to work, configure the JUnit reporter in `doctest.php`:

```php ignore
return [
    'reporters' => [
        'console' => true,
        'junit'   => 'build/doctest.xml',
    ],
];
```

## GitLab CI

```yaml
doctest:
  image: php:8.4-cli
  before_script:
    - composer install --no-interaction
  script:
    - vendor/bin/doctest
  artifacts:
    when: always
    reports:
      junit: build/doctest.xml
```

## Bitbucket Pipelines

```yaml
pipelines:
  default:
    - step:
        name: Documentation Tests
        image: php:8.4-cli
        script:
          - composer install --no-interaction
          - vendor/bin/doctest
```

## CircleCI

```yaml
jobs:
  doctest:
    docker:
      - image: cimg/php:8.4
    steps:
      - checkout
      - run: composer install --no-interaction
      - run: vendor/bin/doctest
      - store_test_results:
          path: build/doctest.xml
```

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | All blocks passed |
| `1` | One or more blocks failed |

CI systems treat exit code `1` as a failure, which fails the build when documentation tests break.

## Tips

- Run DocTest as a **separate job** so it doesn't block your main test suite
- Use `--stop-on-failure` to fail fast in CI
- Generate JUnit XML for test result dashboards
- Use the JSON reporter for custom analysis or trend tracking
- Cache `vendor/` to speed up CI runs
