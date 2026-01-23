---
url: /docs/README.md
---
# Health Checker for Joomla - User Documentation

This directory contains the user-facing documentation for Health Checker for Joomla, built with [VitePress](https://vitepress.dev/) v2.0.

## Documentation Structure

* **Getting Started** - Installation and quick start guides
* **User Guide** - How to use Health Checker features
* **Health Checks Reference** - Detailed explanation of all health checks
* **For Developers** - API and integration guide for third-party developers
* **Optional Integrations** - Akeeba Backup and Admin Tools plugins

## Local Development

### Prerequisites

* Node.js 18+ (recommended: use latest LTS)
* npm or yarn

### VitePress 2.0

This project uses **VitePress 2.0.0-alpha.15**, which includes:

* **Vite 7** - Faster builds and hot module replacement
* **Stricter Link Validation** - Dead links fail the build by default (helps catch broken internal links)
* **Improved Performance** - Better build times and smaller bundle sizes
* **Enhanced TypeScript Support** - Better type checking for configurations

#### Breaking Changes from VitePress 1.x

* **Dead Link Checking**: Now enabled by default and will fail builds. Fix broken links or add `ignoreDeadLinks: true` to config
* **Config File**: Uses `.mjs` extension for ES modules (was `.js` in older versions)
* **Vite 7**: May affect custom Vite plugins if you have any

### Setup

```bash
# Navigate to USER docs
cd docs/USER

# Install dependencies
npm install

# Start development server
npm run docs:dev
```

The documentation site will be available at `http://localhost:5173/docs/`

### Build for Production

```bash
# Build static site (basic)
npm run docs:build

# Build and update website search index (recommended for production)
npm run docs:build:production

# Preview built site
npm run docs:preview
```

Built files are output to `../../website/public/docs/` (configured via `outDir` in `.vitepress/config.mjs`)

> **Note:** VitePress 2.0 has stricter dead link checking by default. If the build fails due to broken internal links, fix them or configure `ignoreDeadLinks` in `.vitepress/config.mjs`.

#### Website Search Integration

The main website (`website/public/index.html`) includes a search widget that uses the VitePress search index.

**The search hash is automatically updated during build** via a VitePress `buildEnd` hook in `.vitepress/config.mjs`. No manual steps required!

## Deployment

The documentation can be deployed to:

* **GitHub Pages**
* **Netlify**
* **Vercel**
* **Any static hosting**

### GitHub Pages Deployment

```bash
# Build the site
npm run docs:build

# Deploy to GitHub Pages
# (requires gh-pages package or GitHub Actions)
```

Example GitHub Actions workflow:

```yaml
name: Deploy Docs

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 18
      - run: cd docs/USER && npm ci
      - run: cd docs/USER && npm run docs:build
      - uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: docs/USER/.vitepress/dist
```

## Writing Documentation

### Adding New Pages

1. Create a new `.md` file in the appropriate directory
2. Add frontmatter if needed:

```md
---
title: Your Page Title
description: Page description for SEO
---

# Your Page Title

Content here...
```

3. Update `.vitepress/config.mjs` sidebar configuration:

```js
sidebar: [
  {
    text: 'Your Section',
    items: [
      { text: 'Your Page', link: '/your-page' }
    ]
  }
]
```

### Markdown Extensions

VitePress supports:

* **Tables**
* **Code blocks with syntax highlighting**
* **Custom containers** (tip, warning, danger)
* **Emoji** :tada:
* **Table of contents**
* **Code group tabs**

See [VitePress Markdown Extensions](https://vitepress.dev/guide/markdown) for details.

### Custom Containers

```md
::: tip
This is a tip
:::

::: warning
This is a warning
:::

::: danger
This is a danger message
:::

::: details Click to expand
This is a details block
:::
```

### Code Blocks

````md
```php
// PHP code with syntax highlighting
final class ExampleCheck extends AbstractHealthCheck
{
    // ...
}
```

```bash
# Bash commands
npm install
npm run docs:dev
```
````

## File Organization

```
docs/USER/
├── .vitepress/
│   ├── config.mjs             # VitePress configuration
│   └── theme/
│       ├── index.js           # Custom theme
│       └── custom.css         # Custom styles
├── index.md                   # Home page
├── introduction.md            # Introduction
├── installation.md            # Installation guide
├── getting-started.md         # Quick start
├── understanding-checks.md    # Understanding health checks
├── running-checks.md          # How to run checks
├── reading-results.md         # Interpreting results
├── exporting-reports.md       # Export functionality
├── dashboard-widget.md        # Dashboard widget
├── checks/                    # Health checks reference
│   ├── system.md
│   ├── database.md
│   ├── security.md
│   ├── users.md
│   ├── extensions.md
│   ├── performance.md
│   ├── seo.md
│   └── content.md
├── developers/                # Developer guide
│   ├── index.md
│   ├── quick-start.md
│   ├── creating-checks.md
│   ├── custom-categories.md
│   ├── provider-metadata.md
│   ├── api-reference.md
│   ├── best-practices.md
│   └── examples.md
└── integrations/              # Optional integrations
    ├── akeeba-backup.md
    └── akeeba-admin-tools.md
```

## Updating Documentation

### When Adding New Features

1. Update relevant user guide pages
2. Update API reference if needed
3. Add examples to developer guide
4. Update home page if it's a major feature

### When Adding New Checks

1. Update the appropriate check reference page in `checks/`
2. Follow the existing format:

```md
## Check Name

**Status**: Critical | Warning | Good

**Category**: System & Hosting

**What It Checks**: Brief description

**Why It Matters**: Explanation of importance

**How to Fix**: Steps to resolve warnings/critical issues

**Technical Details**: Additional context for advanced users
```

### When Changing API

1. Update `developers/api-reference.md`
2. Update code examples in `developers/quick-start.md`
3. Update `developers/examples.md` if needed
4. Consider adding a migration guide if it's a breaking change

## Style Guidelines

### Tone

* Clear and concise
* Helpful and instructive
* Assumes user is competent but may be new to Health Checker
* Use "you" to address the reader
* Use active voice

### Formatting

* Use sentence case for headings
* Use **bold** for UI elements (e.g., **System → Global Configuration**)
* Use `code` for file paths, class names, method names
* Use code blocks for multi-line code
* Use tables for comparing options or listing items

### Examples

Always include practical examples:

```md
### Example: Creating a Custom Check

Here's how to create a simple health check:

[code example]

This check does [explanation].
```

## Contributing

To contribute to the documentation:

1. Fork the repository
2. Create a branch for your changes
3. Make your edits
4. Test locally with `npm run docs:dev`
5. Build to verify: `npm run docs:build`
6. Submit a pull request

## Questions?

* Check [VitePress documentation](https://vitepress.dev/)
* Open an issue on GitHub
* Ask in discussions

## License

Documentation is released under GPL v2+ license, same as Health Checker for Joomla.
