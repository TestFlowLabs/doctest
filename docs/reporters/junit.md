# JUnit XML Reporter

The JUnit reporter generates XML output compatible with CI tools like GitHub Actions, GitLab CI, Jenkins, and CircleCI.

## Configuration

```php ignore
return [
    'reporters' => [
        'junit' => 'build/doctest.xml',
    ],
];
```

## Output Format

The reporter generates standard JUnit XML:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites tests="5" failures="1" time="0.12">
  <testsuite name="README.md" tests="3" failures="1" skipped="0" time="0.08">
    <testcase name="Line 3" classname="README.md" time="0.02"/>
    <testcase name="Line 7" classname="README.md" time="0.01"/>
    <testcase name="Line 11" classname="README.md" time="0.03">
      <failure type="AssertionError">
        Output does not match expected value
        --- Expected
        +++ Actual
        @@ @@
        -Hello, World!
        +Hello, World
      </failure>
    </testcase>
  </testsuite>
  <testsuite name="docs/api.md" tests="2" failures="0" skipped="1" time="0.04">
    <testcase name="Line 5" classname="docs/api.md" time="0.02"/>
    <testcase name="Line 12" classname="docs/api.md" time="0.0">
      <skipped/>
    </testcase>
  </testsuite>
</testsuites>
```

## Structure

- **`<testsuites>`** — Root element with total counts
- **`<testsuite>`** — One per markdown file
- **`<testcase>`** — One per code block, named by line number
- **`<failure>`** — Present on failed blocks with error details and diff
- **`<skipped/>`** — Present on ignored blocks

## CI Integration

### GitHub Actions

```yaml
- run: vendor/bin/doctest
- uses: dorny/test-reporter@v1
  if: always()
  with:
    name: DocTest
    path: build/doctest.xml
    reporter: java-junit
```

### GitLab CI

```yaml
doctest:
  script:
    - vendor/bin/doctest
  artifacts:
    reports:
      junit: build/doctest.xml
```
