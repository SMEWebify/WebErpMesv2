<?php

namespace Tests\Unit;

use App\Services\Files\SvgSanitizer;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    public function test_detects_svg_with_or_without_xml_declaration(): void
    {
        $this->assertTrue(SvgSanitizer::looksLikeSvg('<svg xmlns="http://www.w3.org/2000/svg"></svg>'));
        $this->assertTrue(SvgSanitizer::looksLikeSvg("\xEF\xBB\xBF<?xml version=\"1.0\"?>\n<svg width=\"10\"></svg>"));
        $this->assertFalse(SvgSanitizer::looksLikeSvg("\x89PNG\r\n"));
        $this->assertFalse(SvgSanitizer::looksLikeSvg('<html><svg></svg></html>'));
    }

    public function test_detects_svg_after_a_long_prolog(): void
    {
        // Au-delà de 1 024 octets de prologue, l'ancienne détection échouait et le SVG finissait en .bin
        $svg = '<?xml version="1.0"?>' . "\n"
            . '<?xml-stylesheet type="text/css" href="#s"?>' . "\n"
            . '<!-- ' . str_repeat('RADAN RadQuote ', 200) . '-->' . "\n"
            . '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L1 1"/></svg>';

        $this->assertTrue(SvgSanitizer::looksLikeSvg($svg));

        $clean = (new SvgSanitizer())->sanitize($svg);
        $this->assertNotNull($clean);
        $this->assertStringStartsWith('<svg', $clean);
        $this->assertStringContainsString('<path d="M0 0 L1 1"', $clean);
    }

    public function test_removes_external_urls_from_css_and_presentation_attributes(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<style>@import url(https://evil.test/a.css); .p{fill:url(https://evil.test/b)} .q{fill:url(#grad)}</style>'
            . '<rect style="fill:url(\'https://evil.test/c\')" filter="url(https://evil.test/d#f)" fill="url(#grad)"/>'
            . '<circle style="background:u\72l(https://evil.test/e)"/>'
            . '</svg>';

        $clean = (new SvgSanitizer())->sanitize($svg);

        $this->assertNotNull($clean);
        $this->assertStringNotContainsString('evil.test', $clean);
        $this->assertStringNotContainsString('@import', $clean);
        $this->assertStringContainsString('url(#grad)', $clean);
    }

    public function test_keeps_geometry(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50"><path d="M0 0 L100 50" stroke="#000"/><use href="#a"/></svg>';

        $clean = (new SvgSanitizer())->sanitize($svg);

        $this->assertStringContainsString('<path d="M0 0 L100 50"', $clean);
        $this->assertStringContainsString('viewBox="0 0 100 50"', $clean);
        $this->assertStringContainsString('href="#a"', $clean);
    }

    public function test_removes_scripts_events_and_external_links(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)">'
            . '<script>alert(2)</script>'
            . '<foreignObject><div>x</div></foreignObject>'
            . '<a xlink:href="javascript:alert(3)"><rect onclick="alert(4)" width="1" height="1"/></a>'
            . '<image href="https://evil.test/x.png"/>'
            . '</svg>';

        $clean = (new SvgSanitizer())->sanitize($svg);

        $this->assertNotNull($clean);
        $this->assertStringNotContainsStringIgnoringCase('alert', $clean);
        $this->assertStringNotContainsStringIgnoringCase('script', $clean);
        $this->assertStringNotContainsStringIgnoringCase('foreignObject', $clean);
        $this->assertStringNotContainsString('evil.test', $clean);
        $this->assertStringContainsString('<rect', $clean);
    }

    public function test_rejects_doctype_entities_and_invalid_xml(): void
    {
        $xxe = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>';
        $this->assertNull((new SvgSanitizer())->sanitize($xxe));

        $internal = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x "<script>alert(1)</script>">]><svg xmlns="http://www.w3.org/2000/svg"><g>&x;</g></svg>';
        $this->assertNull((new SvgSanitizer())->sanitize($internal));

        $this->assertNull((new SvgSanitizer())->sanitize('<svg><path></svg>'));
    }
}
