# Claude Code Integration

DocTest has an official [Claude Code](https://docs.anthropic.com/en/docs/claude-code) skill that teaches Claude how to apply DocTest to any markdown documentation — fully autonomous, from installation to block conversion to verification.

## What It Does

The skill gives Claude three modes:

| Mode | Trigger | Action |
|------|---------|--------|
| **Apply** | "apply doctest to docs" | Full workflow: install → analyze → classify blocks → add assertions → verify |
| **Verify** | "run doctest" | Execute `vendor/bin/doctest` and report results |
| **Fix** | "fix doctest failures" | Analyze failures, fix assertions, re-verify |

When you ask Claude to apply DocTest, it will:

1. **Check your environment** — PHP version, Composer, existing config
2. **Install DocTest** if not already present
3. **Scan your markdown files** for PHP code blocks
4. **Classify each block** using a decision tree:
   - Static output → exact assertion (`<!-- doctest: -->`)
   - JSON output → JSON assertion (`<!-- doctest-json: -->`)
   - Dynamic values → wildcard assertion (`{{date}}`, `{{uuid}}`, etc.)
   - External dependencies → `no_run` attribute
   - Config snippets → `ignore` attribute
   - Exception demos → `throws` attribute
   - Related sequences → `group` attribute with `setup`/`teardown`
5. **Run DocTest** after each file and fix any failures
6. **Report** the final results

## Installation

### From GitHub

Install the skill at the project level:

```bash
mkdir -p .claude/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .claude/skills/doctest \
  skills-master/doctest/skills/doctest
```

Or at the user level (available in all projects):

```bash
mkdir -p ~/.claude/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.claude/skills/doctest \
  skills-master/doctest/skills/doctest
```

### Local Testing

Test the skill directly from the cloned repo:

```bash
claude --plugin-dir /path/to/skills/doctest
```

## Usage

Once installed, use it in Claude Code:

```
> apply doctest to my documentation
> run doctest
> fix doctest failures
```

Or target specific files:

```
> apply doctest to docs/getting-started.md
> run doctest on README.md
```

## Example Session

```
You: apply doctest to docs/

Claude: Let me check your environment first...
  ✔ PHP 8.4.1
  ✔ Composer found
  ✔ DocTest installed
  ✔ Config found (doctest.php)
  Found 12 markdown files with 47 PHP code blocks.

  Processing docs/getting-started.md...
    :3  echo 'Hello, World!'          → added <!-- doctest: Hello, World! -->
    :8  echo json_encode([...])       → added <!-- doctest-json: {...} -->
    :15 $db->query(...)               → added no_run attribute
    :22 $sum = array_sum([...])       → added // => 15

  Running: vendor/bin/doctest docs/getting-started.md -v
    ✔ All 3 testable blocks passed

  Processing docs/api.md...
  ...

  Summary:
    Files: 12
    Blocks: 47 (32 tested, 8 no_run, 5 ignored, 2 skipped)
    All passing ✔
```

## What's Included

The skill ships with comprehensive reference material that Claude uses for accurate classification:

| File | Content |
|------|---------|
| `SKILL.md` | Main skill prompt with 3 modes and full workflow |
| `reference/assertions.md` | All 6 assertion types with exact syntax |
| `reference/attributes.md` | All 7 attributes with processing priority |
| `reference/wildcards.md` | All 8 wildcard patterns with regex details |
| `reference/groups.md` | Group lifecycle, setup/teardown, SQLite pattern |
| `reference/decision-tree.md` | Block classification flowchart |
| `scripts/check-doctest.sh` | Environment check script |

## Requirements

- [Claude Code](https://docs.anthropic.com/en/docs/claude-code) CLI
- PHP 8.4+ (for DocTest execution)
- Composer (for DocTest installation)
