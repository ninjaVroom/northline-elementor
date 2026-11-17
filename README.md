# Northline Operations Advisory

Samuel Vroom

A WordPress and Elementor portfolio site for an operations advisory business. A child theme handles the shared page structure; a domain plugin supplies records, fields, custom widgets and first-party forms.

## Case study

The site connects service pages to case studies, people and resources. Those relationships live in WordPress records rather than being repeated in each page. The plugin provides fifteen Elementor section widgets that read the same records. The child theme handles single pages, archives, search and shared navigation.

The build uses the free Elementor stack. Secure Custom Fields supplies the field API, while the plugin renders the sections that would otherwise need paid dynamic widgets. Editors can change the content without editing the theme templates.

The enquiry form has three steps: interest and email, company context, then review and consent. The server validates the submission and stores it as a private enquiry before attempting notification. Tokens identify repeat submissions. A honeypot and nonce checks handle common invalid requests.

The booking page offers thirty-minute slots over the next ten business days in the site's time zone. It rejects unavailable slots and records accepted bookings as private enquiries. Notifications include a calendar invitation. This is one shared schedule; calendar-provider synchronisation and per-partner availability are not connected.

A procedural Blender gearbox supplies the scroll sequence. Rendered frames are included with the theme, so running the website does not require Blender. The content importer matches stable source keys when it updates the demo records.

This is a portfolio demonstration. The included cases, quotations, prices and biographies are seed content, not independently verified client evidence. CRM delivery, external calendar integrations, analytics and production deployment are not included.

## Install on a disposable WordPress site

Use PHP 8.1 or later, the Hello Elementor parent theme, Elementor, Secure Custom Fields and Yoast SEO. WordPress core and those third-party packages are not bundled here.

Copy `wp/plugins/northline-site/` into the installation's plugins directory, and `wp/themes/northline-child/` into its themes directory. Activate the required packages and this project's plugin and child theme.

With WP-CLI pointed at that installation, run:

```sh
wp plugin activate northline-site
wp theme activate northline-child
wp northline seed --root=/absolute/path/to/this/repository/wp/seed
wp rewrite flush
```

The seed command updates site settings, records, media, pages and menus. Use a test installation. The optional `tools/wp.sh` wrapper requires `WP_PATH` to point to that installation; it contains no developer-specific Local paths or credentials.

## Source and checks

`wp/plugins/northline-site/` contains the field contract, record queries, widgets, render parts, seed importer and forms. `wp/themes/northline-child/` contains page templates and frontend assets. `wp/seed/` holds the demonstration content, media and guide. Font license notices are included beside the fonts.

GitHub Actions checks every shipped PHP and browser JavaScript file for syntax errors. Existing runtime smoke scripts are available for a disposable site:

```sh
bash tools/test_intake.sh http://your-test-site.local
bash tools/test_booking.sh http://your-test-site.local
python tools/a11y_check.py http://your-test-site.local
```

The accessibility script needs Beautiful Soup. The form scripts create test records and can trigger WordPress email delivery; use a local mail sink and a fresh test database. They are not run against the original Local site during publication.

`tools/blender/gearbox.py` rebuilds the animation using Blender; its command-line options are documented at the top of the file. The saved Blender scene and local development credentials are excluded from this repository.

## Prototype

Figma prototype link pending.

Live portfolio: https://northline.samuelrolandvroom.com
