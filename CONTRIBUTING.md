# Contributing

Contributions are **welcome** and will be fully **credited**.

Please read and understand this guide before creating an issue or pull request.

## Reporting Issues

- Check the [issue tracker](https://github.com/daikazu/laratone/issues) to see if your problem has already been reported.
- Include as much detail as possible: PHP version, Laravel version, package version, and steps to reproduce.
- For security vulnerabilities, please follow the [security policy](https://github.com/daikazu/laratone/security/policy) instead of opening a public issue.

## Pull Requests

- **One feature or fix per pull request.** Smaller, focused PRs are easier to review and merge.
- **Add tests.** Bug fixes should include a regression test; new features need coverage for the happy path and edge cases.
- **Document changes in behavior.** Update the README (and UPGRADE.md for breaking changes) when relevant.
- **Follow the existing code style.** Strict types, final classes, and descriptive names throughout.

## Development Workflow

Clone the repository and install dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

Run static analysis (PHPStan at max level):

```bash
composer analyse
```

Format your code before committing:

```bash
composer format
```

Preview automated refactoring suggestions:

```bash
composer rector-dry
```

All four should pass cleanly before you open a pull request - CI runs the test suite across PHP 8.3/8.4 and Laravel 12/13 on Linux and Windows.

## Adding Color Books

Color book JSON files live in `colorbooks/`. Each file has the shape:

```json
{
    "name": "My Color Book",
    "data": [
        { "name": "Color Name", "hex": "FF0000" }
    ]
}
```

Hex values must normalize to exactly 6 hexadecimal characters. The `lab`, `rgb`, and `cmyk` fields are optional - values are auto-calculated from hex when omitted. There is a test that validates every shipped color book, so `composer test` will catch malformed data.

Thank you for contributing!
