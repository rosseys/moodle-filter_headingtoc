<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace filter_headingtoc;

/**
 * Heading Contents filter.
 *
 * Builds a nested table of contents from the headings found in the text, and
 * assigns anchor ids to those headings at render time. Because the filter runs
 * inside format_text() after the HTML has been purified, the ids it adds are
 * part of the final output and survive the purifier that would otherwise strip
 * them.
 *
 * @package    filter_headingtoc
 * @copyright  2026 Roxana Castillo (Rosseys)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /**
     * Apply the filter to the given text.
     *
     * @param string $text The text to filter.
     * @param array $options The filter options.
     * @return string The filtered text.
     */
    #[\Override]
    public function filter($text, array $options = []) {
        if (!is_string($text) || $text === '') {
            return $text;
        }

        // Cheap bailout: nothing to do if there is neither a marker nor a heading.
        // stripos is cheap; DOMDocument is not, and this filter runs on every
        // fragment of text on the page.
        $hasmarker = (stripos($text, '[toc]') !== false);
        if (!$hasmarker && stripos($text, '<h') === false) {
            return $text;
        }

        $levels = $this->get_levels();
        $minheadings = max(1, (int)get_config('filter_headingtoc', 'minheadings'));
        $autotop = (bool)get_config('filter_headingtoc', 'autotop');
        $numbered = (bool)get_config('filter_headingtoc', 'numbered');
        $title = get_config('filter_headingtoc', 'title');
        if ($title === false || $title === '') {
            $title = get_string('toctitle', 'filter_headingtoc');
        }

        // Parse, collect the headings and assign missing ids on the DOM.
        $dom = $this->load_dom($text);
        if ($dom === null) {
            return $text;
        }
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return $text;
        }

        $modified = false;
        $headings = $this->collect_headings($dom, $levels, $modified);
        $generate = (count($headings) >= $minheadings);

        // If there is a marker we must serialise to place or strip it.
        if ($hasmarker) {
            $html = $this->serialize_body($dom, $body);
            $toc = $generate ? $this->build_toc($headings, $title, $numbered) : '';
            return $this->place_at_marker($html, $toc);
        }

        // No marker: insert at the top only when enabled and the threshold is met.
        if ($autotop && $generate) {
            $html = $this->serialize_body($dom, $body);
            return $this->build_toc($headings, $title, $numbered) . $html;
        }

        // No table of contents was placed. Return the serialised body only when
        // we changed something (assigned an anchor id); otherwise leave the
        // original text untouched to avoid needless DOM round-trip changes.
        if ($modified) {
            return $this->serialize_body($dom, $body);
        }

        return $text;
    }

    /**
     * Read and sanitise the configured heading levels.
     *
     * @return string[] Ascending unique subset of h1..h6, defaulting to h2,h3,h4.
     */
    protected function get_levels(): array {
        $raw = get_config('filter_headingtoc', 'levels');
        if ($raw === false || trim($raw) === '') {
            $raw = 'h2,h3,h4';
        }
        $levels = [];
        foreach (explode(',', strtolower($raw)) as $part) {
            $part = trim($part);
            if (preg_match('/^h[1-6]$/', $part)) {
                $levels[$part] = true;
            }
        }
        if (empty($levels)) {
            $levels = ['h2' => true, 'h3' => true, 'h4' => true];
        }
        $levels = array_keys($levels);
        sort($levels);
        return $levels;
    }

    /**
     * Load the text into a DOMDocument in a UTF-8 safe way.
     *
     * @param string $text The HTML fragment.
     * @return \DOMDocument|null The document, or null if it could not be parsed.
     */
    protected function load_dom(string $text): ?\DOMDocument {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8"?><body>' . $text . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $loaded ? $dom : null;
    }

    /**
     * Serialise the inner HTML of the body element only.
     *
     * @param \DOMDocument $dom The document.
     * @param \DOMElement $body The body element.
     * @return string The inner HTML.
     */
    protected function serialize_body(\DOMDocument $dom, \DOMElement $body): string {
        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        return $html;
    }

    /**
     * Collect the qualifying headings in document order, assigning anchor ids
     * to those that do not already have one.
     *
     * @param \DOMDocument $dom The document.
     * @param string[] $levels The configured heading tags (e.g. h2,h3,h4).
     * @param bool $modified Set to true (by reference) if an id was assigned.
     * @return array[] List of ['level' => int, 'id' => string, 'text' => string].
     */
    protected function collect_headings(\DOMDocument $dom, array $levels, bool &$modified): array {
        $conditions = [];
        foreach ($levels as $level) {
            $conditions[] = 'self::' . $level;
        }
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[' . implode(' or ', $conditions) . ']');
        if ($nodes === false) {
            return [];
        }

        // First pass: keep the eligible nodes and reserve their existing ids so a
        // generated slug never collides with an id that appears later.
        $used = [];
        $keep = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            if ($this->has_notoc($node)) {
                continue;
            }
            if (trim($node->textContent) === '') {
                continue;
            }
            $keep[] = $node;
            $existing = $node->getAttribute('id');
            if ($existing !== '') {
                $used[$existing] = true;
            }
        }

        // Second pass: assign ids where missing and build the list.
        $headings = [];
        foreach ($keep as $node) {
            $id = $node->getAttribute('id');
            if ($id === '') {
                $id = $this->make_slug($node->textContent, $used);
                $node->setAttribute('id', $id);
                $modified = true;
            }
            // Tag managed headings so the stylesheet can briefly highlight the
            // one the reader jumps to, without affecting other headings.
            if (strpos(' ' . $node->getAttribute('class') . ' ', ' filter_headingtoc-target ') === false) {
                $node->setAttribute('class', trim($node->getAttribute('class') . ' filter_headingtoc-target'));
                $modified = true;
            }
            $headings[] = [
                'level' => (int)substr($node->nodeName, 1),
                'id' => $id,
                'text' => trim($node->textContent),
            ];
        }
        return $headings;
    }

    /**
     * Whether the node, or any of its ancestors, carries the no-toc class.
     *
     * @param \DOMElement $node The heading element.
     * @return bool True when the heading should be excluded.
     */
    protected function has_notoc(\DOMElement $node): bool {
        $current = $node;
        while ($current instanceof \DOMElement) {
            $class = $current->getAttribute('class');
            if ($class !== '' && preg_match('/\bno-toc\b/', $class)) {
                return true;
            }
            $current = $current->parentNode;
        }
        return false;
    }

    /**
     * Build a stable, accent-free, de-duplicated slug for an anchor id.
     *
     * @param string $text The heading text.
     * @param array $used Reference to the set of ids already in use.
     * @return string The unique slug.
     */
    protected function make_slug(string $text, array &$used): string {
        $slug = \core_text::specialtoascii($text);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = 'toc-' . ($slug === '' ? 'section' : $slug);
        $base = $slug;
        $i = 2;
        while (isset($used[$slug])) {
            $slug = $base . '-' . $i++;
        }
        $used[$slug] = true;
        return $slug;
    }

    /**
     * Build the nested table of contents markup.
     *
     * @param array[] $headings List of ['level' => int, 'id' => string, 'text' => string].
     * @param string $title The visible/aria title of the table of contents.
     * @param bool $numbered Whether to render an ordered list.
     * @return string The <nav> markup.
     */
    protected function build_toc(array $headings, string $title, bool $numbered): string {
        $listtag = $numbered ? 'ol' : 'ul';

        // Map the heading levels present to contiguous depths (h2->1, h3->2, ...)
        // so gaps in the configured levels do not create empty nesting.
        $present = [];
        foreach ($headings as $heading) {
            $present[$heading['level']] = true;
        }
        ksort($present);
        $rank = [];
        $depthindex = 1;
        foreach (array_keys($present) as $level) {
            $rank[$level] = $depthindex++;
        }

        $items = '';
        $prevdepth = 0;
        foreach ($headings as $heading) {
            $depth = $rank[$heading['level']];
            // Never jump more than one level deeper than the previous entry.
            if ($depth > $prevdepth + 1) {
                $depth = $prevdepth + 1;
            }
            if ($depth > $prevdepth) {
                $items .= '<' . $listtag . '>';
            } else if ($depth < $prevdepth) {
                $items .= '</li>';
                for ($d = $depth; $d < $prevdepth; $d++) {
                    $items .= '</' . $listtag . '></li>';
                }
            } else {
                $items .= '</li>';
            }
            $items .= '<li><a href="#' . s($heading['id']) . '">' . s($heading['text']) . '</a>';
            $prevdepth = $depth;
        }
        if ($prevdepth > 0) {
            $items .= '</li>';
            for ($d = 0; $d < $prevdepth - 1; $d++) {
                $items .= '</' . $listtag . '></li>';
            }
            $items .= '</' . $listtag . '>';
        }

        return '<nav class="filter_headingtoc" aria-label="' . s($title) . '">'
            . '<div class="filter_headingtoc-title">' . s($title) . '</div>'
            . $items
            . '</nav>';
    }

    /**
     * Replace the first [toc] marker with the table of contents and strip any
     * remaining markers. The marker may stand alone or be wrapped in its own
     * paragraph by the editor.
     *
     * @param string $html The serialised body HTML.
     * @param string $toc The table of contents markup (empty to just strip).
     * @return string The result.
     */
    protected function place_at_marker(string $html, string $toc): string {
        $count = 0;
        // Prefer a marker that occupies its own paragraph.
        $result = preg_replace('/<p[^>]*>\s*\[toc\]\s*<\/p>/i', $toc, $html, 1, $count);
        if ($count === 0) {
            $result = preg_replace('/\[toc\]/i', $toc, $html, 1);
        }
        // Remove any leftover markers so only a single table of contents appears.
        $result = preg_replace('/<p[^>]*>\s*\[toc\]\s*<\/p>/i', '', $result);
        $result = str_ireplace('[toc]', '', $result);
        return $result;
    }
}
