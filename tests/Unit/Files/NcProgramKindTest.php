<?php

namespace Tests\Unit\Files;

use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use PHPUnit\Framework\TestCase;

/**
 * Programmes ISO : type « nc », affichés en parcours d'outil, rôle FAO par défaut.
 */
class NcProgramKindTest extends TestCase
{
    public function test_iso_programs_get_their_own_viewable_kind(): void
    {
        foreach (['PROG.NC', 'op10.tap', 'piece.gcode', 'x.ngc', 'y.cnc'] as $name) {
            $this->assertSame(FileKindResolver::KIND_NC, FileKindResolver::fromFilename($name), $name);
        }

        $this->assertTrue(FileKindResolver::isViewable(FileKindResolver::KIND_NC));
        $this->assertSame(FileRole::CAM, FileRole::guessFromKind(FileKindResolver::KIND_NC));
        $this->assertContains('nc', FileKindResolver::allowedExtensions());
    }

    public function test_an_iso_program_is_never_served_inline(): void
    {
        $this->assertNull(FileKindResolver::inlineMimeType('nc'));
        $this->assertSame(FileKindResolver::KIND_OTHER, FileKindResolver::fromFilename('notes.txt'));
    }
}
