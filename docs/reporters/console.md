# Console Reporter

The console reporter outputs results directly to the terminal. It is enabled by default.

## Output Format

```
README.md
  :3 ✔ echo 'Hello, World!';        [1/5]  0.02s
  :7 ✔ echo 2 + 3;                  [2/5]  0.01s
  :11 ✔ echo date('Y');             [3/5]  0.01s
  :15 ⊘ $config = require...        [4/5]
  :19 ✖ echo $result;               [5/5]  0.02s

----------------------------------------
Blocks: 5  Passed: 3  Failed: 1  Skipped: 1  Duration: 0.12s
```

## Output Elements

Each line shows:

- **Line number** — where the code block starts in the markdown file
- **Status icon** — ✔ (passed), ✖ (failed), ⊘ (skipped)
- **Code preview** — first line of the code block (truncated at 60 characters)
- **Progress** — current block / total blocks
- **Duration** — execution time

## Verbosity Levels

### Default

Block-level pass/fail only.

### `-v` (Verbose)

Shows per-assertion details under each block:

```
  :3 ✔ echo 'Hello, World!';  [1/2]  0.02s
       ✔ output: Hello, World!
  :7 ✖ echo $result;          [2/2]  0.01s
       ✖ output: expected 42, got 43
```

### `-vv` (Very Verbose)

Also shows source code on failure:

```
  :7 ✖ echo $result;          [2/2]  0.01s
       ✖ output: expected 42, got 43
    Source:
      $result = 40 + 3;
      echo $result;
```

## Summary

After all blocks run, a summary line shows totals:

```
----------------------------------------
Blocks: 12  Passed: 10  Failed: 1  Skipped: 1  Duration: 1.23s
```

## Configuration

```php ignore
return [
    'reporters' => [
        'console' => true,  // or false to disable
    ],
];
```
