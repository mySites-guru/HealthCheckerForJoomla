---
description: "Community-built plugins for Health Checker for Joomla. Discover third-party integrations or submit your own."
---

# Community Plugins

Health Checker's event-driven architecture makes it easy for anyone to create plugins that add custom health checks. This page lists third-party plugins built by the Joomla community.

## What Are Community Plugins?

Community plugins are Health Checker extensions created by developers outside the core project. They use the same plugin API as the bundled integrations (Akeeba Backup, Akeeba Admin Tools) but are maintained independently by their authors.

Community plugins can:
- Add health checks for other Joomla extensions
- Monitor custom application-specific metrics
- Integrate with external services or APIs
- Define entirely new check categories

## Available Plugins

There are no community plugins listed yet — be the first to create one!

::: tip Be the First!
If you've built a Health Checker plugin, we'd love to list it here. See [Submitting Your Plugin](#submitting-your-plugin) below.
:::

## Submitting Your Plugin

To get your plugin listed on this page:

1. **Open a PR** — Add your plugin to the [COMMUNITY_PLUGINS.md](https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/COMMUNITY_PLUGINS.md) file in the repository
2. **Open an issue** — Use the [GitHub issue tracker](https://github.com/mySites-guru/HealthCheckerForJoomla/issues) to request a listing

Please include:
- Plugin name
- Short description of what it checks
- Author name or organisation
- Link to the plugin repository or download

::: warning Disclaimer
Community plugins are not reviewed, endorsed, or supported by the Health Checker project. Install third-party plugins at your own discretion and always review the source code before installing.
:::

## Creating Your Own Plugin

Ready to build a Health Checker plugin? The Developer Guide walks you through everything:

- [Developer Overview](../developers/index.md) — Architecture and concepts
- [Quick Start](../developers/quick-start.md) — Get a plugin running in minutes
- [Creating Health Checks](../developers/creating-checks.md) — Write your first check
- [Custom Categories](../developers/custom-categories.md) — Define new categories
- [Best Practices](../developers/best-practices.md) — Tips for quality plugins

You can also use the included example plugin at `healthchecker/plugins/example/` as a starting point.

## Next Steps

- [Akeeba Backup Integration](./akeeba-backup.md) — Bundled backup monitoring
- [Akeeba Admin Tools Integration](./akeeba-admin-tools.md) — Bundled security monitoring
- [Understanding Health Checks](../understanding-checks.md) — Learn about check categories
