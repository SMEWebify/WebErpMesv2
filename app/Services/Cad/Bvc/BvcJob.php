<?php

namespace App\Services\Cad\Bvc;

/**
 * Bystronic BySoft cutting job (.bvc), read into parts and nests.
 *
 * The .bvc is a ZIP archive:
 *   JobInfo.xml   material, thickness, parts (name + quantity), sheets and,
 *                 per nest, the parts it holds under which SubProgram
 *   <nest>.ccp    CuttingControlProgram of one nest (XML)
 *                   <SubPrograms>     one SubProgram per part, in part coordinates
 *                   <ControlCommands> CallSubProgram X Y Rotation = one copy on the sheet
 *   <nest>.png    preview drawn by BySoft
 *
 * Geometry of a SubProgram:
 *   MoveLineTo / LineTo X Y
 *   ArcTo X Y SweepAngle           end point + swept angle, > 0 counter-clockwise
 *   BeginLeadIn .. EndLeadIn       lead-in, skipped: its end point starts the contour
 *   BeginCutOut Contour=".." .. EndCutOut   the contour itself, Exterior or Interior
 *   ExecMicrojoint before EndCutOut stops the contour short of its start: it is
 *   closed again here, the gap is a tab left by the machine, not part geometry.
 *
 * PartWidth / PartHeight of a SubProgram run from the SubProgram origin, which
 * the lead-in starts from outside the part: they overstate the part, so the
 * size is measured on the contours instead.
 *
 * Placement on the sheet: rotation about the part origin, then translation.
 */
class BvcJob
{
    /**
     * @param  array<string, mixed>  $job
     * @param  array<int, array<string, mixed>>  $parts  keyed by part id
     * @param  array<int, array<string, mixed>>  $nests
     */
    private function __construct(
        public readonly array $job,
        public readonly array $parts,
        public readonly array $nests,
    ) {}

    /**
     * @throws \RuntimeException when the file is not a readable .bvc
     */
    public static function read(string $path): self
    {
        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            throw new \RuntimeException('Fichier BVC illisible (archive attendue)');
        }

        try {
            $jobXml = $zip->getFromName('JobInfo.xml');

            if ($jobXml === false) {
                throw new \RuntimeException('Fichier BVC sans JobInfo.xml');
            }

            $programs = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);

                if (mb_strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'ccp') {
                    $programs[pathinfo($name, PATHINFO_FILENAME)] = self::readProgram((string) $zip->getFromIndex($i));
                }
            }
        } finally {
            $zip->close();
        }

        return self::assemble(self::xml($jobXml), $programs);
    }

    /**
     * @param  array<string, array<string, mixed>>  $programs  keyed by nest name
     */
    private static function assemble(\SimpleXMLElement $jobXml, array $programs): self
    {
        $job = [
            'name' => (string) $jobXml['Name'],
            'material' => trim((string) $jobXml['Material']) ?: null,
            'thickness' => isset($jobXml['Thickness']) ? (float) $jobXml['Thickness'] : null,
            'gas' => trim((string) $jobXml['ProcessGas']) ?: null,
            'cutting_time' => (float) $jobXml->EstimatedCuttingTime,
        ];

        $parts = [];

        foreach ($jobXml->PartInfos->PartInfo ?? [] as $info) {
            $name = (string) $info['Name'];

            // "Dummy" stands for the residual sheet cuts, not a part.
            if ($name === 'Dummy') {
                continue;
            }

            // BySoft appends the ordered quantity to the name: "16-nb24".
            $reference = preg_replace('/-nb\d+$/i', '', $name) ?: $name;

            $parts[(int) $info['Id']] = [
                'id' => (int) $info['Id'],
                'name' => $name,
                'reference' => $reference,
                'quantity' => (int) $info['Quantity'],
                'contours' => null,
                'nests' => [],
            ];
        }

        $sheets = [];

        foreach ($jobXml->MaterialInfos->MaterialInfo ?? [] as $sheet) {
            $sheets[(string) $sheet['Id']] = [
                'width' => (float) $sheet['SizeX'],
                'height' => (float) $sheet['SizeY'],
            ];
        }

        $nests = [];

        foreach ($jobXml->PlanInfos->PlanInfo ?? [] as $plan) {
            $name = (string) $plan['Name'];
            $program = $programs[$name] ?? null;
            $multiplicity = max(1, (int) ($plan['Multiplicity'] ?? 1));

            // SubProgram names are local to a nest: Sub3 is not the same part
            // from one .ccp to the next.
            $subToPart = [];

            foreach ($plan->PlanPartInfos->PlanPartInfo ?? [] as $planPart) {
                $partId = (int) $planPart['PartId'];
                $subToPart[(string) $planPart['SubProgramName']] = $partId;

                if (isset($parts[$partId])) {
                    $parts[$partId]['nests'][] = [
                        'nest' => $name,
                        'quantity' => (int) $planPart['Quantity'] * $multiplicity,
                    ];
                }
            }

            $placements = [];
            $residualCuts = [];

            foreach ($program['calls'] ?? [] as $call) {
                $partId = $subToPart[$call['subprogram']] ?? null;
                $contours = $program['subprograms'][$call['subprogram']] ?? null;

                if ($contours === null) {
                    continue;
                }

                if ($partId !== null && isset($parts[$partId])) {
                    $parts[$partId]['contours'] ??= $contours;
                    $placements[] = ['part_id' => $partId] + $call;
                } else {
                    $residualCuts[] = ['contours' => $contours] + $call;
                }
            }

            $nests[] = [
                'name' => $name,
                'multiplicity' => $multiplicity,
                'sheet' => $sheets[(string) $plan['MaterialId']] ?? null,
                'used_width' => (float) $plan['SizeX'],
                'used_height' => (float) $plan['SizeY'],
                'cutting_time' => (float) $plan['EstimatedCuttingTime'],
                'waste' => (float) $plan['Waste'],
                'residual_sheets' => array_map(fn (\SimpleXMLElement $residual) => [
                    'x' => (float) $residual['Left'],
                    'y' => (float) $residual['Bottom'],
                    'width' => (float) $residual['SizeX'],
                    'height' => (float) $residual['SizeY'],
                ], iterator_to_array($plan->ResidualSheetInfos->ResidualSheetInfo ?? [], false)),
                'placements' => $placements,
                'residual_cuts' => $residualCuts,
            ];
        }

        foreach ($parts as $id => $part) {
            if ($part['contours'] !== null) {
                $parts[$id] += BvcGeometry::measure($part['contours']);
            }
        }

        return new self($job, $parts, $nests);
    }

    /**
     * Read the SubPrograms and the placement calls of one .ccp.
     *
     * @return array{subprograms: array<string, array<int, array<string, mixed>>>,
     *               calls: array<int, array<string, mixed>>}
     */
    private static function readProgram(string $content): array
    {
        $xml = self::xml($content);
        $subprograms = [];

        foreach ($xml->SubPrograms->SubProgram ?? [] as $subprogram) {
            $subprograms[(string) $subprogram['Name']] = self::contours($subprogram->ControlCommands);
        }

        $calls = [];

        foreach ($xml->ControlCommands->CallSubProgram ?? [] as $call) {
            $calls[] = [
                'subprogram' => (string) $call['SubProgramName'],
                'x' => (float) $call['X'],
                'y' => (float) $call['Y'],
                'rotation' => (float) $call['Rotation'],
            ];
        }

        return ['subprograms' => $subprograms, 'calls' => $calls];
    }

    /**
     * Walk the commands of a SubProgram into its contours, lead-ins left out.
     *
     * A contour is a start point and segments, each one a target point plus
     * the swept angle in degrees (0 for a straight line).
     *
     * @return array<int, array{interior: bool, start: array{0: float, 1: float},
     *                          segments: array<int, array{to: array{0: float, 1: float}, sweep: float}>,
     *                          closed: bool}>
     */
    private static function contours(?\SimpleXMLElement $commands): array
    {
        $contours = [];
        $position = [0.0, 0.0];
        $inLeadIn = false;
        $current = null;

        foreach ($commands?->children() ?? [] as $tag => $command) {
            switch ($tag) {
                case 'BeginLeadIn':
                    $inLeadIn = true;
                    break;

                case 'EndLeadIn':
                    $inLeadIn = false;
                    break;

                case 'BeginCutOut':
                    $current = [
                        'interior' => (string) $command['Contour'] === 'Interior',
                        'start' => $position,
                        'segments' => [],
                    ];
                    break;

                case 'EndCutOut':
                    if ($current !== null && $current['segments'] !== []) {
                        $contours[] = self::close($current);
                    }

                    $current = null;
                    break;

                case 'MoveLineTo':
                case 'LineTo':
                case 'ArcTo':
                    $target = [(float) $command['X'], (float) $command['Y']];

                    if ($current !== null && ! $inLeadIn && $tag !== 'MoveLineTo') {
                        $current['segments'][] = [
                            'to' => $target,
                            'sweep' => $tag === 'ArcTo' ? (float) $command['SweepAngle'] : 0.0,
                        ];
                    }

                    $position = $target;
                    break;
            }
        }

        return $contours;
    }

    /**
     * Close a contour cut short by a micro-joint, or flag an open cut.
     *
     * @param  array<string, mixed>  $contour
     * @return array<string, mixed>
     */
    private static function close(array $contour): array
    {
        $last = end($contour['segments'])['to'];
        $gap = hypot($last[0] - $contour['start'][0], $last[1] - $contour['start'][1]);

        // A micro-joint leaves a tab of a few tenths of a millimetre: a gap
        // that wide is still the part. Beyond, this is a separation cut.
        $contour['closed'] = $gap <= 2.0 && count($contour['segments']) > 1;

        if ($contour['closed'] && $gap > 1e-6) {
            $contour['segments'][] = ['to' => $contour['start'], 'sweep' => 0.0];
        }

        return $contour;
    }

    /**
     * @throws \RuntimeException on malformed XML
     */
    private static function xml(string $content): \SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($content, \SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($xml === false) {
            throw new \RuntimeException('Fichier BVC corrompu (XML illisible)');
        }

        return $xml;
    }
}
