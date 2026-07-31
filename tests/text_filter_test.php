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
 * Unit tests for the Heading Contents filter.
 *
 * @package    filter_headingtoc
 * @copyright  2026 Roxana Castillo (Rosseys)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_headingtoc\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    /**
     * Build a filter instance bound to the system context.
     *
     * @return text_filter
     */
    protected function get_filter(): text_filter {
        return new text_filter(\context_system::instance(), []);
    }

    /**
     * Content without headings and without a marker is returned untouched.
     */
    public function test_no_headings_bailout(): void {
        $this->resetAfterTest();
        $input = '<p>Just a paragraph with no headings.</p>';
        $this->assertSame($input, $this->get_filter()->filter($input));
    }

    /**
     * The marker is replaced by a nav landmark and heading anchors are assigned.
     */
    public function test_marker_replaced_with_toc(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '<p>[toc]</p><h2>Alpha</h2><h2>Beta</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('<nav class="filter_headingtoc"', $out);
        $this->assertStringContainsString('aria-label=', $out);
        $this->assertStringContainsString('href="#toc-alpha"', $out);
        $this->assertStringContainsString('href="#toc-beta"', $out);
        $this->assertStringContainsString('id="toc-alpha"', $out);
        $this->assertStringContainsString('id="toc-beta"', $out);
        $this->assertStringNotContainsString('[toc]', $out);
    }

    /**
     * Only the configured heading levels become entries.
     */
    public function test_levels_respected(): void {
        $this->resetAfterTest();
        set_config('levels', 'h2', 'filter_headingtoc');
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Included</h2><h3>Excluded</h3>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('href="#toc-included"', $out);
        $this->assertStringNotContainsString('toc-excluded', $out);
        // The excluded heading keeps no injected anchor.
        $this->assertStringNotContainsString('id="toc-excluded"', $out);
    }

    /**
     * Accents are stripped and duplicate texts get de-duplicated slugs.
     */
    public function test_slug_accents_and_dedupe(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Introducción</h2><h2>Introducción</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('id="toc-introduccion"', $out);
        $this->assertStringContainsString('id="toc-introduccion-2"', $out);
    }

    /**
     * A heading with an explicit id keeps it.
     */
    public function test_existing_id_respected(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2 id="custom">Alpha</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('href="#custom"', $out);
        $this->assertStringNotContainsString('toc-alpha', $out);
    }

    /**
     * A heading (or wrapper) marked no-toc is excluded.
     */
    public function test_no_toc_class_excluded(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Kept</h2><h2 class="no-toc">Skipped</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('href="#toc-kept"', $out);
        $this->assertStringNotContainsString('toc-skipped', $out);
    }

    /**
     * Without a marker and with autotop off, no table of contents is inserted.
     */
    public function test_no_marker_autotop_off(): void {
        $this->resetAfterTest();
        set_config('autotop', 0, 'filter_headingtoc');
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '<h2>Alpha</h2><h2>Beta</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringNotContainsString('<nav', $out);
        // Anchors are still assigned so manual links can target them.
        $this->assertStringContainsString('id="toc-alpha"', $out);
    }

    /**
     * With autotop on and the threshold met, the toc is prepended.
     */
    public function test_autotop_inserts_at_top(): void {
        $this->resetAfterTest();
        set_config('autotop', 1, 'filter_headingtoc');
        set_config('minheadings', 2, 'filter_headingtoc');
        $input = '<h2>Alpha</h2><h2>Beta</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringStartsWith('<nav class="filter_headingtoc"', $out);
    }

    /**
     * The minimum-headings threshold blocks generation and strips the marker.
     */
    public function test_min_headings_threshold(): void {
        $this->resetAfterTest();
        set_config('minheadings', 3, 'filter_headingtoc');
        $input = '<p>[toc]</p><h2>Alpha</h2><h2>Beta</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringNotContainsString('<nav', $out);
        $this->assertStringNotContainsString('[toc]', $out);
    }

    /**
     * Nested levels produce nested lists.
     */
    public function test_nested_hierarchy(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Parent</h2><h3>Child</h3><h2>Sibling</h2>';
        $out = $this->get_filter()->filter($input);

        // The child link must sit inside a nested list under the parent.
        $this->assertMatchesRegularExpression(
            '#toc-parent.*<ul><li><a href="#toc-child".*toc-sibling#s',
            $out
        );
    }

    /**
     * Numbered mode renders an ordered list.
     */
    public function test_numbered_uses_ordered_list(): void {
        $this->resetAfterTest();
        set_config('numbered', 1, 'filter_headingtoc');
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Alpha</h2><h2>Beta</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('<ol>', $out);
        $this->assertStringNotContainsString('<ul>', $out);
    }

    /**
     * UTF-8 heading text is preserved in the anchor text.
     */
    public function test_utf8_preserved(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Configuración</h2><h2>Résumé</h2>';
        $out = $this->get_filter()->filter($input);

        $this->assertStringContainsString('Configuración', $out);
        $this->assertStringContainsString('Résumé', $out);
    }

    /**
     * Heading text with markup-significant characters is escaped in the link.
     */
    public function test_special_characters_escaped(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Tom &amp; Jerry</h2><h2>Second</h2>';
        $out = $this->get_filter()->filter($input);

        // The link label is escaped, not rendered as a raw ampersand.
        $this->assertStringContainsString('Tom &amp; Jerry</a>', $out);
    }

    /**
     * Malformed HTML does not raise a fatal error.
     */
    public function test_malformed_html_does_not_fatal(): void {
        $this->resetAfterTest();
        set_config('minheadings', 1, 'filter_headingtoc');
        $input = '[toc]<h2>Open<h3>Unclosed<div><h2>Another';
        $out = $this->get_filter()->filter($input);

        $this->assertIsString($out);
        $this->assertStringContainsString('<nav', $out);
    }
}
