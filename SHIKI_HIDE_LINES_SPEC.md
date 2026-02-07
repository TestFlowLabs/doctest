# shiki-hide-lines — Specification

## Overview

A Shiki transformer that hides marked lines from rendered code blocks while keeping them in the source. Enables documentation authors to include boilerplate code (imports, setup, autoloading) that is necessary for execution but distracting for readers.

Works with any tool that uses Shiki: VitePress, Astro, Nuxt Content, Slidev, or direct Shiki usage.

## Problem

Documentation code examples need to be both **readable** and **runnable**. These goals conflict:

```php
<?php                                    // needed to run, distracting to read
declare(strict_types=1);                 // needed to run, distracting to read
require_once 'vendor/autoload.php';      // needed to run, distracting to read
use App\Models\User;                     // needed to run, distracting to read

$user = User::find(1);                   // ← this is the actual example
echo $user->name;                        // ← this is the actual example
```

## Solution

Mark lines to hide with `// [!code hide]` (or block markers):

```php
<?php // [!code hide]
declare(strict_types=1); // [!code hide]
require_once 'vendor/autoload.php'; // [!code hide]
use App\Models\User; // [!code hide]

$user = User::find(1);
echo $user->name;
```

**Rendered output** shows only the meaningful lines. Hidden lines are either fully removed or revealed on demand via a toggle.

## Syntax

### Single Line

```
<code> // [!code hide]
```

The comment marker adapts to the language: `//` for PHP/JS/TS/Go/Rust/Java/C, `#` for Python/Ruby/Bash/YAML.

### Block (Range)

```
// [!code hide:start]
<?php
declare(strict_types=1);
require_once 'vendor/autoload.php';
use App\Models\User;
// [!code hide:end]
```

All lines between start/end markers are hidden. The marker lines themselves are also hidden.

## Modes

### Mode 1: Fully Hidden (default)

Hidden lines are completely removed from the rendered output. No trace remains.

```ts
transformerHideLines()
// or
transformerHideLines({ reveal: false })
```

**Implementation:** Uses the `code` hook to walk HAST nodes, find comment markers, remove marker text from tokens, and remove entire hidden lines from the tree.

### Mode 2: Revealable

Hidden lines are rendered but visually collapsed. A clickable placeholder shows the count and expands on click.

```ts
transformerHideLines({ reveal: true })
```

**Collapsed state:**
```
┌──────────────────────────────────────┐
│ ··· 4 hidden lines                   │  ← clickable
│                                      │
│ $user = User::find(1);              │
│ echo $user->name;                   │
└──────────────────────────────────────┘
```

**Expanded state:**
```
┌──────────────────────────────────────┐
│ <?php                                │  ← dimmed
│ declare(strict_types=1);             │  ← dimmed
│ require_once 'vendor/autoload.php';  │  ← dimmed
│ use App\Models\User;                 │  ← dimmed
│                                      │
│ $user = User::find(1);              │
│ echo $user->name;                   │
└──────────────────────────────────────┘
```

**Implementation:** Uses the `code` hook to:
1. Find and strip marker comments from tokens
2. Add `class="hidden-line"` to hidden lines via `this.addClassToHast()`
3. Add `class="has-hidden-lines"` to the `<pre>` element
4. Insert a placeholder `<span class="hidden-lines-summary">` element

A small client-side script (~20 lines) handles the toggle interaction. CSS handles the visual states.

## Package Structure

```
testflowlabs/shiki-hide-lines/
├── src/
│   ├── index.ts              # Main export
│   ├── transformer.ts        # Transformer implementation
│   ├── types.ts              # Options interface
│   └── client.ts             # Client-side toggle script (reveal mode)
├── style.css                 # Optional CSS for reveal mode
├── package.json
├── tsconfig.json
├── README.md
├── LICENSE
└── test/
    └── transformer.test.ts
```

## API

```ts
import { transformerHideLines } from 'shiki-hide-lines'

// Fully hidden (default)
transformerHideLines()

// Revealable with toggle
transformerHideLines({ reveal: true })

// Custom marker (default: 'hide')
transformerHideLines({ marker: 'hidden' })  // uses // [!code hidden]
```

### Options

```ts
interface HideLinesOptions {
  /**
   * Enable reveal mode with a clickable toggle.
   * @default false
   */
  reveal?: boolean

  /**
   * The marker keyword used in comments.
   * @default 'hide'
   */
  marker?: string
}
```

## CSS Classes

| Class | Applied to | When |
|-------|-----------|------|
| `has-hidden-lines` | `<pre>` | Code block has hidden lines |
| `hidden-line` | `<span class="line">` | Line is hidden (reveal mode) |
| `hidden-lines-summary` | `<span>` | Placeholder element (reveal mode) |
| `hidden-lines-revealed` | `<pre>` | Toggle is expanded (reveal mode) |

### Example CSS (reveal mode)

```css
/* Hide lines by default */
.has-hidden-lines .hidden-line {
  display: none;
}

/* Show lines when revealed */
.hidden-lines-revealed .hidden-line {
  display: block;
  opacity: 0.5;
}

/* Summary placeholder */
.hidden-lines-summary {
  display: block;
  padding: 2px 0;
  color: var(--vp-code-tab-text-color, #888);
  cursor: pointer;
  user-select: none;
  font-style: italic;
}

/* Hide summary when revealed */
.hidden-lines-revealed .hidden-lines-summary {
  display: none;
}
```

## Integration Examples

### VitePress

```ts
// .vitepress/config.ts
import { transformerHideLines } from 'shiki-hide-lines'

export default defineConfig({
  markdown: {
    codeTransformers: [
      transformerHideLines({ reveal: true })
    ]
  }
})
```

### Astro

```ts
// astro.config.mjs
import { transformerHideLines } from 'shiki-hide-lines'

export default defineConfig({
  markdown: {
    shikiConfig: {
      transformers: [transformerHideLines()]
    }
  }
})
```

### Nuxt Content

```ts
// nuxt.config.ts
import { transformerHideLines } from 'shiki-hide-lines'

export default defineNuxtConfig({
  content: {
    highlight: {
      transformers: [transformerHideLines()]
    }
  }
})
```

### Direct Shiki

```ts
import { codeToHtml } from 'shiki'
import { transformerHideLines } from 'shiki-hide-lines'

const html = await codeToHtml(code, {
  lang: 'php',
  theme: 'github-dark',
  transformers: [transformerHideLines()]
})
```

## Build & Publish

- **Build tool:** unbuild (consistent with Shiki ecosystem)
- **Output:** ESM-only (`.mjs` + `.d.mts`)
- **Package type:** `"type": "module"`
- **Peer dependency:** `shiki >= 1.0.0`
- **Keywords:** `["shiki", "shiki-transformer", "code-blocks", "hide-lines", "documentation"]`
- **npm name:** `shiki-hide-lines`

---

# DocTest Changes

## What Needs to Change

DocTest already handles Shiki markers (`// [!code --]`, `// [!code ++]`) in `ShikiFilter.php`. The `// [!code hide]` marker needs the **opposite** behavior of `// [!code --]`:

| Marker | Rendered docs | DocTest execution |
|--------|--------------|-------------------|
| `// [!code --]` | Shows line (with diff styling) | Removes line |
| `// [!code hide]` | Hides line | Keeps line (strips marker) |

### Files to Modify

#### 1. `src/Parser/ShikiFilter.php`

Add `// [!code hide]` marker handling. Strip the marker comment but **keep the line** for execution. Also handle `// [!code hide:start]` / `// [!code hide:end]` block markers.

Current Shiki markers handled:
- `// [!code --]` → remove entire line
- `// [!code ++]` → strip marker, keep line
- `{1,4-6}` → strip from info string

New behavior:
- `// [!code hide]` → strip marker, keep line (same as `++`)
- `// [!code hide:start]` → remove marker line entirely
- `// [!code hide:end]` → remove marker line entirely

#### 2. `src/Parser/ShikiFilterResult.php`

Optionally track which lines were hidden (for verbose reporting).

#### 3. Tests

Add tests for:
- Single line `// [!code hide]` — marker stripped, line kept
- Block `// [!code hide:start]` / `// [!code hide:end]` — marker lines removed, inner lines kept
- Mixed with existing markers (`// [!code --]` + `// [!code hide]`)
- Multiple hide blocks in one code block
- Hide marker with other content on same line (`<?php // [!code hide]`)

### Implementation Scope

**Low complexity.** The existing `ShikiFilter` already has the regex pattern and line-processing loop. Adding hide support is ~15-20 lines of code.

---

# Skill Changes (testflowlabs/skills)

## New Skill: `make-runnable`

A skill that analyzes documentation code blocks and makes them runnable by adding the necessary boilerplate with `// [!code hide]` markers.

### Skill Modes

| Mode | Trigger | Action |
|------|---------|--------|
| **ANALYZE** | "analyze docs for runnability" | Scan blocks, report which need boilerplate |
| **MAKE-RUNNABLE** | "make docs runnable", "add hidden setup" | Add hidden boilerplate to make blocks executable |
| **STRIP** | "strip hidden lines", "remove boilerplate" | Remove all `// [!code hide]` lines |

### MAKE-RUNNABLE Workflow

1. **Scan** — Find PHP blocks without assertions or with `ignore`/`no_run` attributes
2. **Classify** — For each non-runnable block, determine what's missing:
   - Missing `<?php` declaration
   - Missing `use` imports
   - Missing variable definitions from earlier context
   - Missing autoloader/bootstrap
   - Missing class/function definitions
3. **Add boilerplate** — Insert necessary code with `// [!code hide]` markers:
   ```php
   <?php // [!code hide]
   require_once 'vendor/autoload.php'; // [!code hide]
   use App\Models\User; // [!code hide]

   $user = User::find(1);
   echo $user->name;
   ```
4. **Convert attribute** — Change `ignore` or `no_run` to a proper assertion
5. **Verify** — Run `vendor/bin/doctest {file} -v` to confirm block passes
6. **Report** — Summary of changes made

### Skill Placement

```
testflowlabs/skills/
├── doctest/                    # Existing doctest skill
└── make-runnable/              # New skill
    ├── .claude-plugin/
    │   └── plugin.json
    └── skills/
        └── make-runnable/
            └── SKILL.md
```

Alternatively, this could be a new mode added to the existing doctest skill rather than a separate skill. This depends on whether it makes sense as a standalone action or as part of the doctest workflow.

### Recommendation

**Add as a new mode to the existing doctest skill** rather than a separate skill. Reasons:
- It's tightly coupled with DocTest (uses doctest to verify)
- Users already know the doctest skill
- Avoids skill proliferation
- Natural extension: "apply doctest" → "make runnable" → "verify"

Updated modes table for the doctest skill:

| Mode | Trigger | Action |
|------|---------|--------|
| **APPLY** | "apply doctest" | Full workflow: install → analyze → convert → verify |
| **VERIFY** | "run doctest" | Run doctest and report |
| **FIX** | "fix doctest failures" | Analyze failures, fix, re-verify |
| **REVIEW** | "review docs" | Review against best practices |
| **MAKE-RUNNABLE** | "make docs runnable", "add hidden setup" | Add hidden boilerplate to non-runnable blocks |

---

# Implementation Order

1. **DocTest: `// [!code hide]` support** — Add to ShikiFilter.php (small, foundational)
2. **shiki-hide-lines: npm package** — Create and publish the Shiki transformer
3. **DocTest docs: wire transformer** — Use shiki-hide-lines in doctest's own VitePress site
4. **DocTest docs: add documentation page** — Document the hide feature
5. **Skill: MAKE-RUNNABLE mode** — Add to doctest skill

Steps 1-2 can be done in parallel. Step 3-4 depend on both. Step 5 depends on all.

---

# Open Questions

1. **Package scope:** `shiki-hide-lines` (unscoped) or `@testflowlabs/shiki-hide-lines` (scoped)?
   - Recommendation: unscoped — more discoverable, community-oriented
2. **Reveal mode JS:** Ship as inline script string or as a separate importable module?
   - Recommendation: both — inline for simplicity, module for customization
3. **Language-specific comment markers:** Support `#` for Python/Ruby/Bash in addition to `//`?
   - Recommendation: yes — auto-detect from Shiki's language info
4. **Skill as separate or mode?** Separate plugin or new mode in doctest skill?
   - Recommendation: new mode in doctest skill
