# Contributing to AssetConnect

Thank you for considering contributing to AssetConnect! This document outlines the guidelines for contributing to this project.

## Code of Conduct

By participating in this project, you agree to abide by its [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting Started

### Prerequisites

- PHP 8.3 or higher
- Composer
- Git

### Setup

1. Fork the repository
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR-USERNAME/asset-connect.git
   cd asset-connect
   ```
3. Install dependencies:
   ```bash
   composer install
   ```
4. Create a branch for your changes:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Workflow

### Running Tests

Before submitting your changes, make sure all tests pass:

```bash
composer test
```

### Code Style

This project follows the CodeIgniter coding standards. You can check your code with:

```bash
composer cs
```

And automatically fix style issues with:

```bash
composer cs-fix
```

### Static Analysis

Run static analysis tools to catch potential issues:

```bash
composer analyze
```

## Pull Request Process

1. Update the README.md and documentation with details of changes if applicable
2. Run all tests and ensure they pass
3. Update the CHANGELOG.md with details of changes
4. Submit a pull request to the `develop` branch

### Pull Request Guidelines

- Fill in the required pull request template
- Include tests for new features or bug fixes
- Keep pull requests focused on a single topic
- Document any new public methods or classes
- Follow the project's code style

## Reporting Issues

When reporting issues, please include:

1. A clear and descriptive title
2. Steps to reproduce the issue
3. Expected behavior
4. Actual behavior
5. Your environment details (PHP version, CodeIgniter version, etc.)
6. Any relevant logs or screenshots

## Feature Requests

Feature requests are welcome. Please provide:

1. A clear description of the feature
2. The motivation behind the feature
3. How it would benefit the project
4. Any implementation ideas you have

## Security Vulnerabilities

If you discover a security vulnerability, please send an email to [maniaba@outlook.com](mailto:maniaba@outlook.com) instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

## Coding Guidelines

### PHP

- Follow PSR-12 coding standards
- Write clear, readable, and well-documented code
- Use type hints and return type declarations
- Write unit tests for new functionality

### Documentation

- Keep documentation up-to-date with code changes
- Use clear and concise language
- Include examples where appropriate

## Release Process

1. The maintainers will decide when to release a new version
2. Versions follow [Semantic Versioning](https://semver.org/)
3. Each release will be tagged and available on GitHub and Packagist

## License

By contributing to AssetConnect, you agree that your contributions will be licensed under the project's [MIT License](LICENSE.md).

## Questions?

If you have any questions, feel free to open an issue or contact the maintainers.

Thank you for contributing to AssetConnect!
