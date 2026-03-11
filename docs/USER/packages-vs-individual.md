---
description: "Understand the difference between the Health Checker complete package and individual extension ZIPs. Learn which to download and when individual installs make sense."
---

# Package vs Individual Installs

When you visit the [releases page](https://github.com/mySites-guru/HealthCheckerForJoomla/releases), you'll see several ZIP files. This page explains what each one is and which one you should download.

## The Complete Package (recommended)

**`pkg_healthchecker-x.x.x.zip`** is the file most people should download.

This is a Joomla [package](https://docs.joomla.org/Extension_types_(general_definitions)) — a single ZIP that bundles everything together:

| Extension | Type | Purpose |
|-----------|------|---------|
| `com_healthchecker` | Component | The main application — admin UI, report page, event system |
| `plg_healthchecker_core` | Plugin | All 130+ built-in health checks |
| `mod_healthchecker` | Module | Dashboard widget showing health summary |
| `plg_healthchecker_akeebabackup` | Plugin | Akeeba Backup integration checks (optional) |
| `plg_healthchecker_akeebaadmintools` | Plugin | Akeeba Admin Tools integration checks (optional) |
| `plg_healthchecker_mysitesguru` | Plugin | mySites.guru API integration (optional) |
| `plg_healthchecker_example` | Plugin | Example plugin for developers (optional) |

One install, everything works. The package installs all pieces in the right order and handles dependencies for you.

### Updating with the package

When you update the package ZIP, Joomla updates **all** the extensions inside it at once. If you see individual plugin updates available in Joomla Update or tools like Akeeba Panopticon, you only need to update the package — it covers everything.

## The Individual ZIPs

The remaining files on the releases page are the individual extensions that make up the package:

- `com_healthchecker-x.x.x.zip` — Component only
- `plg_healthchecker_core-x.x.x.zip` — Core checks plugin only
- `mod_healthchecker-x.x.x.zip` — Dashboard module only
- `plg_healthchecker_akeebabackup-x.x.x.zip` — Akeeba Backup plugin only
- `plg_healthchecker_akeebaadmintools-x.x.x.zip` — Admin Tools plugin only
- `plg_healthchecker_mysitesguru-x.x.x.zip` — mySites.guru plugin only
- `plg_healthchecker_example-x.x.x.zip` — Example developer plugin only

### Why individual ZIPs exist

Individual ZIPs are provided for situations where the complete package isn't ideal:

- **Selective installation** — You only want specific plugins and don't want the optional ones installed at all
- **Independent updates** — A fix is released for just one plugin and you want to update only that piece
- **Third-party distribution** — Developers building their own check plugins can ship them as standalone ZIPs

### Dependencies matter

The individual pieces depend on each other. The plugins and module all require the component (`com_healthchecker`) to be installed first. If you install a plugin ZIP without the component, Joomla will throw errors because the plugin tries to use component classes that don't exist.

**Minimum required for Health Checker to work:**

1. `com_healthchecker` (component) — must be installed first
2. `plg_healthchecker_core` (core plugin) — must be installed and **enabled**

Without both of these, Health Checker won't function.

## Which should I use?

| Scenario | Recommendation |
|----------|---------------|
| First time installing | Complete package (`pkg_healthchecker`) |
| Updating an existing install | Complete package (`pkg_healthchecker`) |
| You don't use Akeeba extensions and don't want those plugins present | Individual ZIPs (component + core + module) |
| You manage updates via [mySites.guru](https://mysites.guru) and want full control over which plugins are enabled | Individual ZIPs — see note below |
| You're a developer building a custom check plugin | Your plugin ZIP only (assumes component is already installed) |

::: tip Managing plugin state with mySites.guru or other automation tools
When installed via the complete package, all bundled plugins are enabled on first install. If you disable optional plugins (like the example or mySites.guru plugins) and later update via the package, the plugins stay in their current enabled/disabled state — the package respects your choices on update.

If you still prefer to control exactly which plugins exist on your site, install the individual ZIPs for just the parts you want.
:::

## Switching from package to individual installs

If you originally installed the complete package but want to switch to individual management:

1. **Uninstall the package** — Go to **System > Manage > Extensions**, find `Health Checker for Joomla` (type: Package), and uninstall it. This removes everything.
2. **Install the component** — `com_healthchecker-x.x.x.zip`
3. **Install the core plugin** — `plg_healthchecker_core-x.x.x.zip` and enable it
4. **Install the dashboard module** — `mod_healthchecker-x.x.x.zip`
5. **Install any optional plugins you want** — only the ones you need

From this point on, each extension updates independently and you won't have unwanted plugins on your site.

## Switching from individual installs to the package

If you installed the pieces individually and want to switch to the package for simpler updates:

1. Install `pkg_healthchecker-x.x.x.zip` through **System > Install > Extensions**
2. Joomla will detect the existing extensions and upgrade them in place
3. Any plugins you didn't previously have will be installed and enabled

## Common mistakes

### Installing a plugin without the component

**Symptom**: Fatal error or white screen after installing a plugin ZIP.

**Cause**: The plugin needs classes from `com_healthchecker` which isn't installed.

**Fix**: Install the complete package, or install `com_healthchecker` first, then the plugin.

### Installing the component without the core plugin

**Symptom**: Health Checker opens but shows no checks when you run it.

**Cause**: The component provides the framework, but the actual health checks live in the core plugin.

**Fix**: Install and enable `plg_healthchecker_core`.

### Downloading the wrong ZIP from the releases page

**Symptom**: After installation, Health Checker doesn't work or is missing pieces.

**Cause**: You downloaded one of the individual ZIPs instead of the complete package.

**Fix**: Go back to the [releases page](https://github.com/mySites-guru/HealthCheckerForJoomla/releases) and download `pkg_healthchecker-x.x.x.zip` — the file with `pkg_` at the start of the name.

## How Joomla extensions work

For context, Joomla has several extension types:

- **Components** are the main applications. They provide the admin pages, menus, and core logic. Health Checker's component handles the report page, the event system, and the UI.
- **Plugins** are event handlers. They respond to events fired by components. Health Checker's plugins provide the actual health checks — the core plugin registers 130+ checks when the component fires its `CollectChecksEvent`.
- **Modules** are small widgets that display information. Health Checker's module shows a health summary on the admin dashboard.
- **Packages** are bundles that install multiple extensions together. They don't add any functionality themselves — they're just a convenient way to distribute related extensions as a single download.

This modular design is why Health Checker is split into multiple pieces rather than being one monolithic extension. It allows third-party developers to [create their own check plugins](/developers/) without modifying the core.
