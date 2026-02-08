# Beads Task Management

Use `bd` (beads) for all task tracking in this project. Beads is the single source of truth for work items.

## Core Workflow

```bash
# Find ready work
bd ready --json

# Claim a task before starting
bd update <id> --claim --json

# Create sub-tasks discovered during work
bd create "Sub-task title" -p <priority> --deps discovered-from:<parent-id> --json

# Complete a task
bd close <id> --reason "What was done" --json

# Sync at end of work
bd sync
```

## Rules

### Always
- Use `--json` flag for all `bd` commands (machine-readable output)
- Claim tasks with `bd update <id> --claim` before starting work
- Close tasks with `bd close <id> --reason "..."` when done
- Create child tasks for work discovered during implementation
- Run `bd sync` at session end
- **Atomic tasks**: Every task must be as atomic as possible — one clear responsibility
- **Track bug fixes**: Create a beads task (`-t bug`) for every bug fix, no matter how small
- **Intermediate tasks**: When needed, create new tasks or bugfix tasks mid-implementation and insert them into the dependency chain
- **All content in English**: Task titles, descriptions, and close reasons must be in English

### Never
- Never use `bd edit` (interactive editor — agents can't use it)
- Never push automatically (user handles pushes per commits.md rule)
- Never skip `bd sync` — changes sit in 30s debounce window without it

### Push Exception
The beads AGENT_INSTRUCTIONS.md says to always push. Our project rule (commits.md) says never push automatically. **Our project rule wins** — never push, user handles all pushes.

## Priority Levels

| Priority | Use For |
|----------|---------|
| P0 | Blocking / critical bugs |
| P1 | Current phase work items |
| P2 | Next phase or nice-to-have |
| P3 | Future / low priority |
| P4 | Ideas / maybe later |

## Task Types

Use `-t` flag: `task` (default), `bug`, `feature`, `epic`

## Dependencies

```bash
# Task B blocks task A (A can't start until B is done)
bd dep add <A-id> <B-id>

# Task discovered during work on parent
bd create "..." --deps discovered-from:<parent-id>
```

## Session Start

1. `bd ready --json` — find available work
2. Pick highest priority unblocked task
3. `bd update <id> --claim --json` — claim it
4. Work on it
5. When done: `bd close <id> --reason "..." --json`
6. `bd sync`
