# Contributing to flint-auth

Thanks for your interest in contributing. Please read this before opening a PR.

---

## Philosophy

flint-auth is a scaffold package for the Flint framework. It should stay true to Flint's core principles:

- **No magic.** No facades, static proxies, or hidden state. Generated code should be easy to read and follow.
- **No bloat.** This package scaffolds session-based auth pages and frontend presets. Features that don't fit that scope belong in a separate package.
- **Fail loudly.** The install command should throw descriptive errors rather than silently skipping steps. Generated code should do the same.
- **PHP 8.1+ only.** Use typed properties, constructor promotion, match expressions, and readonly where they add clarity.

---

## What Belongs Here

This package has two concerns:

1. **The scaffold command** (`php flint ui bootstrap|vue|react [--auth]`) — copies stub files into the target application.
2. **The stubs themselves** — the controllers, models, migrations, views, and frontend components that get published.

Changes to stub quality, additional auth pages, new frontend presets, and install command improvements are all in scope.

Changes that add runtime dependencies, hook into the request lifecycle, or require Flint core changes should be discussed in an issue first.

---

## Reporting Bugs

Open a GitHub issue and include:

1. PHP version and OS
2. The `php flint ui` command you ran and any output
3. What you expected to happen vs. what actually happened
4. The contents of any generated files that look wrong

---

## Suggesting Features

Open a GitHub issue before writing code. Describe the new preset, page, or scaffold behaviour you want and why it fits the scope of this package.

---

## Submitting a Pull Request

1. Fork the repo and create a branch from `master`.
2. Keep PRs small and focused — one thing per PR.
3. Follow the code style of the surrounding files (`declare(strict_types=1)`, no explanatory comments, constructor promotion).
4. Run the test suite before submitting:
   ```bash
   composer install
   vendor/bin/phpunit
   ```
5. If you're adding or changing a stub, make sure the generated output is syntactically valid PHP (or valid JS/Vue/JSX).
6. Write a clear PR description explaining *why* the change is needed.

---

## Code Style

- `declare(strict_types=1)` at the top of every PHP file
- No inline comments explaining what the code does — name things well instead
- Prefer constructor property promotion over manual assignment
- Prefer `match` over `switch`
- No `else` after a `return` or `throw`

---

## License

By contributing you agree that your code will be released under the [MIT License](LICENSE).
