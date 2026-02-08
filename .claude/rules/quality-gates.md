# Quality Gates

After completing each task, run quality gates in two steps:

```bash
# Step 1: Auto-fix (Rector + Pint)
composer rector && composer pint

# Step 2: Verify all gates pass
composer test
```

`composer test` runs in order: Rector (dry-run) → Pint (test) → PHPStan → Pest → Type Coverage → DocTest.

## Rules

- Run both steps after every task, no exceptions
- Fix any issues before closing the task
- If Rector or Pint make changes in Step 1, stage them in the same commit
- All checks in `composer test` must pass
- **Auto-commit**: When all gates pass, commit immediately using `agentic-commits` skill
- Commits must follow the rules in `commits.md` (no AI attribution, never push)

## TDD Workflow

Every task follows Test-Driven Development:

1. **Red** — Write failing test(s) first
2. **Green** — Write minimal code to make tests pass
3. **Refactor** — Clean up while keeping tests green
4. Run quality gates (`composer rector && composer pint`, then `composer test`)
5. Commit if all gates pass

## Task Content Language

All beads task titles, descriptions, and reasons must be written in **English**.
