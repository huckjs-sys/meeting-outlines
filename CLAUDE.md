# Development Conventions

## GitHub workflow for every fix or improvement

For every change (bug, security, refactoring, feature…):

1. **Issue** — Open a GitHub issue describing the problem:
   - context and file(s) involved
   - impact
   - expected fix or behaviour

2. **Branch** — Develop the fix on a dedicated branch.

3. **PR** — Open a Pull Request that:
   - references the issue with `Closes #N`
   - summarises the changes made
   - includes a test plan for validation before merge

Never push a fix directly to `main` without going through this flow.

## Language

- Conversation with the user: **French**
- Everything pushed to GitHub (issues, pull requests, commit messages, code comments): **English**
