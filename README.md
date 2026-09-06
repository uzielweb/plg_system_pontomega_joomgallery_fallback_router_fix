# Joomla Plugin: System - Ponto Mega JoomGallery Fallback Router Fix

[![Joomla](https://img.shields.io/badge/Joomla-3.x%20%7C%204.x%20%7C%205.x%20%7C%206.x-blue.svg)](https://www.joomla.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-8892BF.svg)](https://php.net)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE.txt)
[![Release](https://img.shields.io/badge/Release-v1.0.0-green.svg)](https://github.com/uzielweb/plg_system_pontomega_joomgallery_fallback_router_fix/releases)

A lightweight, modern, and non-intrusive Joomla system plugin designed to fix the long-standing SEF router fallback flaw in **JoomGallery** without modifying any core files of the component.

---

## The Problem (O Problema)

In native JoomGallery installations, when a URL containing subsegments (such as an old, misspelled, or deleted category/image URL) fails to resolve against an existing item in the database, the component router (`components/com_joomgallery/router.php`) performs an ungraceful fallback at the end of route parsing:

```php
$vars['view'] = 'gallery';
return $vars;
```

### Consequences
1. **Harmful Soft 404 (SEO Damage)**: Inexistent or broken URLs return an **HTTP 200 OK** status code, rendering the generic gallery home page and generating duplicate content issues for search engines.
2. **Breaks Joomla's Native Redirect System**: Joomla's core Redirect plugin (`plg_system_redirect`) only triggers during 404 errors (`onAfterError`). Because JoomGallery returns 200 OK, published redirect rules in Joomla's **Redirects** manager (`#__redirect_links`) are never executed, and broken incoming URLs are never logged in the administrator panel.

---

## The Solution (A Solução)

**Ponto Mega JoomGallery Fallback Router Fix** hooks into the Joomla lifecycle at `onAfterRoute`:
1. **Targeted Detection**: Only inspects frontend `com_joomgallery` requests that resolved to the generic `view=gallery` despite containing additional URL path subsegments beyond the active menu item.
2. **Joomla Redirect Integration**: First queries Joomla's native redirect table (`#__redirect_links`) for published rules matching the requested URL (supporting absolute URLs, relative URLs, and normalized paths). If a published redirect is found, it performs an immediate **301 Moved Permanently** (or configured status code).
3. **Canonical 404**: If no redirect exists, it throws a canonical `\Exception(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404)`. This restores standard HTTP 404 behavior, prevents SEO penalties, and enables Joomla's core Redirect plugin to automatically capture the missing URL in the administrator panel for future management.

---

## Features (Recursos)

- **Zero Core Hacks**: Completely decoupled from JoomGallery; safe against JoomGallery component upgrades.
- **Modern Joomla Architecture**: Uses modern Joomla namespaces (`Joomla\CMS\Factory`, `Joomla\CMS\Uri\Uri`, `Joomla\CMS\Language\Text`, `Joomla\CMS\Plugin\CMSPlugin`) without legacy `J` classes.
- **Proxy & SSL Immune**: Normalizes candidate paths to match redirect rules accurately behind reverse proxies, CDNs, and load balancers.
- **Configurable HTTP Status**: Choose between 301 (Moved Permanently) and 302 (Moved Temporarily).
- **Multilingual Support**: Includes complete English (`en-GB`) and Portuguese (`pt-BR`) language strings.

---

## Installation (Instalação)

1. Download the latest `plg_system_pontomega_joomgallery_fallback_router_fix_v1.0.0.zip` from [Releases](https://github.com/uzielweb/plg_system_pontomega_joomgallery_fallback_router_fix/releases).
2. In your Joomla Administrator panel, navigate to **System > Install > Extensions**.
3. Upload the package file.
4. Navigate to **System > Manage > Plugins**, search for **System - Ponto Mega JoomGallery Fallback Router Fix** (`pontomega_joomgallery_fallback_router_fix`), and **Enable** the plugin.

---

## Configuration (Configuração)

In the plugin settings:
- **Check Redirects (Verificar Redirecionamentos)**: Enable or disable checking the `#__redirect_links` table before issuing a 404 (Default: `Yes`).
- **Default Redirect Code (Código de Redirecionamento Padrão)**: Select the HTTP redirect status code (`301` or `302`, Default: `301`).

---

## Requirements

- **Joomla**: 3.9+, 4.x, 5.x, or 6.x
- **PHP**: 7.4 or newer (fully compatible with PHP 8.1, 8.2, 8.3, 8.4)
- **Component**: JoomGallery 3.x / 4.x

---

## License

This project is licensed under the [GNU General Public License v2 or later](LICENSE.txt).

---

## Credits

Developed by **Ponto Mega**  
Website: [https://www.pontomega.com.br](https://www.pontomega.com.br)  
Contact: [contato@pontomega.com.br](mailto:contato@pontomega.com.br)
