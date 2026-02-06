# AI Agent Integration

DocTest has an official skill that teaches AI coding agents how to apply DocTest to any markdown documentation — fully autonomous, from installation to block conversion to verification.

## The Problem

An agent can run `vendor/bin/doctest` — but it doesn't know _how_ to add DocTest to existing documentation. It needs to understand:

- 6 assertion types and when to use each
- 7 attributes and their processing priority
- 8 wildcard patterns for dynamic output
- Group lifecycle with setup/teardown
- Block classification — which blocks get which assertions

The DocTest skill teaches all of this.

## What the Skill Does

The skill gives the agent three modes:

| Mode | Trigger | Action |
|------|---------|--------|
| **Apply** | "apply doctest to docs" | Install → analyze → classify blocks → add assertions → verify |
| **Verify** | "run doctest" | Execute `vendor/bin/doctest` and report results |
| **Fix** | "fix doctest failures" | Analyze failures, fix assertions, re-verify |

In Apply mode, the agent will:

1. **Check environment** — PHP version, Composer, existing config
2. **Install DocTest** if not present
3. **Scan markdown files** for PHP code blocks
4. **Classify each block** using a decision tree:
   - Static output → exact assertion (`<!-- doctest: -->`)
   - JSON output → JSON assertion (`<!-- doctest-json: -->`)
   - Dynamic values → wildcard assertion (`{{date}}`, `{{uuid}}`, etc.)
   - External dependencies → `no_run` attribute
   - Config snippets → `ignore` attribute
   - Exception demos → `throws` attribute
   - Related sequences → `group` with `setup`/`teardown`
5. **Run DocTest** after each file and fix any failures
6. **Report** the results

## What's Included

| File | Content |
|------|---------|
| `SKILL.md` | Main skill prompt with 3 modes and full workflow |
| `reference/assertions.md` | All 6 assertion types with exact syntax |
| `reference/attributes.md` | All 7 attributes with processing priority |
| `reference/wildcards.md` | All 8 wildcard patterns with regex details |
| `reference/groups.md` | Group lifecycle, setup/teardown, SQLite pattern |
| `reference/decision-tree.md` | Block classification flowchart |
| `scripts/check-doctest.sh` | Environment check script |

## Install

Choose your agent below.

### Claude Code

**1. Project-level** (tracked in git, team-wide)

```bash
mkdir -p .claude/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .claude/skills/doctest \
  skills-master/doctest/skills/doctest
```

**2. User-level** (all your projects)

```bash
mkdir -p ~/.claude/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.claude/skills/doctest \
  skills-master/doctest/skills/doctest
```

Then add to `CLAUDE.md`:

```
Use the doctest skill when working with documentation.
```

### Codex

**1. Project-level**

```bash
mkdir -p .codex/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .codex/skills/doctest \
  skills-master/doctest/skills/doctest
```

**2. User-level**

```bash
mkdir -p ~/.codex/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.codex/skills/doctest \
  skills-master/doctest/skills/doctest
```

Add to `AGENTS.md` for automatic invocation.

### Cursor

**1. Project-level**

```bash
mkdir -p .cursor/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .cursor/skills/doctest \
  skills-master/doctest/skills/doctest
```

**2. User-level**

```bash
mkdir -p ~/.cursor/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.cursor/skills/doctest \
  skills-master/doctest/skills/doctest
```

Also supports `.claude/skills/`.

### Amp

**1. Workspace-level**

```bash
mkdir -p .agents/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .agents/skills/doctest \
  skills-master/doctest/skills/doctest
```

**2. User-level**

```bash
mkdir -p ~/.config/agents/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.config/agents/skills/doctest \
  skills-master/doctest/skills/doctest
```

Add guidance to `AGENTS.md`.

### Antigravity

**1. Workspace-level**

```bash
mkdir -p .agent/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .agent/skills/doctest \
  skills-master/doctest/skills/doctest
```

**2. Global**

```bash
mkdir -p ~/.gemini/antigravity/skills/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.gemini/antigravity/skills/doctest \
  skills-master/doctest/skills/doctest
```

### OpenCode

**1. Project-level**

```bash
mkdir -p .opencode/skill/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C .opencode/skill/doctest \
  skills-master/doctest/skills/doctest
```

**2. User-level**

```bash
mkdir -p ~/.config/opencode/skill/doctest
curl -sL https://github.com/testflowlabs/skills/archive/master.tar.gz | \
  tar -xz --strip-components=3 -C ~/.config/opencode/skill/doctest \
  skills-master/doctest/skills/doctest
```

Also supports `.claude/skills/`.

### Other Agents / Manual

Add the contents of `SKILL.md` to your agent's system prompt or config file (`CLAUDE.md`, `AGENTS.md`, `.cursorrules`, etc.):

```
When asked to apply DocTest, follow these steps:
1. Install: composer require --dev testflowlabs/doctest
2. Find PHP code blocks in markdown files
3. Classify each block and add appropriate assertions
4. Run vendor/bin/doctest to verify
```

For full classification accuracy, include the reference files.

## Summary

| Agent | Project Path | User Path | Config |
|-------|-------------|-----------|--------|
| Claude Code | `.claude/skills/` | `~/.claude/skills/` | `CLAUDE.md` |
| Codex | `.codex/skills/` | `~/.codex/skills/` | `AGENTS.md` |
| Cursor | `.cursor/skills/` | `~/.cursor/skills/` | `.cursorrules` |
| Amp | `.agents/skills/` | `~/.config/agents/skills/` | `AGENTS.md` |
| Antigravity | `.agent/skills/` | `~/.gemini/antigravity/skills/` | — |
| OpenCode | `.opencode/skill/` | `~/.config/opencode/skill/` | — |

## Requirements

- PHP 8.4+ (for DocTest execution)
- Composer (for DocTest installation)
