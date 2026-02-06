---
layout: home

hero:
  name: DocTest
  text: Test Your Documentation
  tagline: Extract PHP code blocks from markdown. Execute them. Verify the output. Automatically.
  image:
    src: /doctest-logo.svg
    alt: DocTest
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/testflowlabs/doctest
---

<div class="feature-sections">

<div class="feature-section">
<div class="feature-text">

<div class="feature-badge"><span class="icon">&#x1F4DD;</span> Basics</div>

## Write It. Test It. Trust It.

Add an assertion after any PHP code block in your markdown. DocTest extracts the code, runs it, and verifies the output matches.

Your rendered documentation stays clean — assertions live in HTML comments, invisible to readers.

[Learn more &rarr;](/guide/getting-started)

</div>
<div class="feature-code">

````markdown
```php
echo 'Hello, World!';
```
<!-- doctest: Hello, World! -->
````

```bash
vendor/bin/doctest

  README.md
    :3 ✔ echo 'Hello, World!';  [1/1]  0.02s

  ----------------------------------------
  Blocks: 1  Passed: 1  Duration: 0.05s
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

<div class="feature-badge"><span class="icon">&#x2714;</span> Assertions</div>

## Seven Ways to Assert

From exact output matching to JSON comparison, DocTest gives you the right tool for every situation.

Use inline comments for quick checks, or HTML comments to keep assertions hidden from readers.

[See all assertions &rarr;](/assertions/)

</div>
<div class="feature-code">

```php
// Exact output
echo 'Hello';
// Output: Hello

// Partial match
echo 'The quick brown fox';
// OutputContains: brown fox

// Regex
echo date('Y');
// OutputMatches: /^\d{4}$/

// JSON comparison
echo json_encode(['status' => 'ok']);
// OutputJson: {"status": "ok"}

// Expression
$sum = array_sum([1, 2, 3]);
// Expect: $sum === 6

// Inline result
$x = 42; // => 42
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

<div class="feature-badge"><span class="icon">&#x2699;</span> Control</div>

## Control with Attributes

Skip blocks, expect exceptions, or check syntax only. Attributes on the code fence give you precise control over how each block is handled.

[Explore attributes &rarr;](/attributes/)

</div>
<div class="feature-code">

````markdown
```php ignore
// This block won't be executed
$config = require 'missing-file.php';
```

```php throws(InvalidArgumentException)
throw new InvalidArgumentException('Bad input');
```

```php no_run
// Syntax checked, not executed
$db->query('SELECT * FROM users');
```
````

</div>
</div>

<div class="feature-section">
<div class="feature-text">

<div class="feature-badge"><span class="icon">&#x1F3AF;</span> Flexible</div>

## Wildcards for Dynamic Output

When output contains timestamps, IDs, or other dynamic values, wildcards let you match the pattern without hardcoding the value.

[See all wildcards &rarr;](/wildcards/)

</div>
<div class="feature-code">

````markdown
```php
echo 'Request took 42ms at ' . date('Y-m-d');
```
<!-- doctest: Request took {{int}}ms at {{date}} -->

```php
echo json_encode([
    'id'   => '550e8400-e29b-41d4-a716-446655440000',
    'time' => '14:30:00',
    'cost' => 19.99,
]);
```
<!-- doctest: {"id":"{{uuid}}","time":"{{time}}","cost":{{float}}} -->
````

</div>
</div>

<div class="feature-section">
<div class="feature-text">

<div class="feature-badge"><span class="icon">&#x1F517;</span> State</div>

## Shared State with Groups

Group related code blocks to share variables across examples. Add setup and teardown blocks for database connections or other resources.

[Learn about groups &rarr;](/attributes/group)

</div>
<div class="feature-code">

````markdown
```php setup group="database"
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT
)');
```

```php group="database"
$pdo->exec("INSERT INTO users (name) VALUES ('Alice')");
$count = $pdo->query('SELECT COUNT(*) FROM users')
    ->fetchColumn();
echo $count;
```
<!-- doctest: 1 -->

```php teardown group="database"
$pdo->exec('DROP TABLE users');
```
````

</div>
</div>

<div class="feature-section">
<div class="feature-text">

<div class="feature-badge"><span class="icon">&#x1F680;</span> CI/CD</div>

## CI-Ready from Day One

Add one line to your CI pipeline. DocTest returns proper exit codes and generates JUnit XML reports for your CI dashboard.

[Set up CI &rarr;](/ci-cd/)

</div>
<div class="feature-code">

```yaml
# .github/workflows/docs.yml
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
      - run: composer install
      - run: vendor/bin/doctest
```

</div>
</div>

</div>
