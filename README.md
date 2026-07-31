# Heading Contents (`filter_headingtoc`)

A Moodle text filter that builds a nested table of contents from the headings in
your content, at display time. It also assigns the anchor ids the links point
to, so the in-page jumps keep working even though Moodle's HTML purifier strips
manually inserted ids.

Because the filter runs *after* purification, inside `format_text()`, its output
is the final HTML sent to the browser — the anchors it adds are never stripped.
There is no stored data, no web service, no database table and no JavaScript.

## Requirements

- Moodle 5.0 or later.

## Usage

By default the filter is **opt-in**: it only builds a table of contents where
you place the marker `[toc]` in the content. Write `[toc]` on its own line where
you want the list to appear, add some headings below it, and the filter replaces
the marker with a linked, nested table of contents.

Headings are matched by level (by default `h2`, `h3` and `h4`). A heading — or
any element wrapping it — with `class="no-toc"` is skipped. Headings that
already have an `id` keep it; the rest get a stable, accent-free slug.

If you prefer a table of contents to appear automatically at the top of any
content that has enough headings, turn on **Insert at top when no marker is
present** in the settings.

## Settings

Configure the filter at
**Site administration → Plugins → Filters → Heading Contents**
(`/admin/settings.php?section=filtersettingheadingtoc`):

- **Heading levels** (`filter_headingtoc/levels`) — which heading tags become
  entries. Comma-separated, default `h2,h3,h4`.
- **Contents title** (`filter_headingtoc/title`) — heading shown above the list.
- **Insert at top when no marker is present** (`filter_headingtoc/autotop`) —
  off by default.
- **Minimum headings** (`filter_headingtoc/minheadings`) — do not generate a
  table of contents below this count. Default `3`.
- **Numbered list** (`filter_headingtoc/numbered`) — render as an ordered list.

## Accessibility

The table of contents is a `<nav>` landmark with an `aria-label` set to the
contents title, and the links are plain keyboard-navigable HTML anchors.

## Enabling the filter

Filters install **disabled**. Enable it at
**Site administration → Plugins → Filters → Manage filters**
(`/admin/filters.php`) and choose the contexts where it should apply. The order
relative to other filters rarely matters for this one, since it only touches
headings and its own marker.

## Privacy

This plugin does not store any personal data.

## Installation

Install like any Moodle plugin: place it in `filter/headingtoc` and complete the
upgrade from *Site administration → Notifications*.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
