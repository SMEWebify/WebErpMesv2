<?php

namespace Tests\Support;

/**
 * Écrit un fichier composé OLE / CFB minimal (version 3, secteurs de 512
 * octets, mini-flux sous 4096 octets) pour fabriquer des .msg de test sans
 * Outlook. Arbre : nom => contenu (flux) ou tableau (stockage).
 */
class CompoundFileWriter
{
    private const SECTOR = 512;
    private const MINI_SECTOR = 64;
    private const CUTOFF = 4096;
    private const END_OF_CHAIN = 0xFFFFFFFE;
    private const FAT_SECTOR = 0xFFFFFFFD;
    private const FREE = 0xFFFFFFFF;

    /** @var array<int, array{name: string, type: int, left: int, right: int, child: int, start: int, size: int, data: ?string}> */
    private array $entries = [];

    public static function build(array $tree): string
    {
        return (new self())->write($tree);
    }

    private function write(array $tree): string
    {
        $this->entries[] = ['name' => 'Root Entry', 'type' => 5, 'left' => self::FREE, 'right' => self::FREE, 'child' => self::FREE, 'start' => self::END_OF_CHAIN, 'size' => 0, 'data' => null];
        $this->entries[0]['child'] = $this->addChildren($tree);

        // Mini-flux : les petits flux bout à bout, par blocs de 64 octets.
        $miniStream = '';
        $miniFat = [];
        foreach ($this->entries as $id => $entry) {
            if ($entry['type'] !== 2 || $entry['size'] === 0 || $entry['size'] >= self::CUTOFF) {
                continue;
            }

            $first = intdiv(strlen($miniStream), self::MINI_SECTOR);
            $count = (int) ceil($entry['size'] / self::MINI_SECTOR);
            $miniStream .= str_pad($entry['data'], $count * self::MINI_SECTOR, "\0");
            for ($i = 0; $i < $count; $i++) {
                $miniFat[] = $i === $count - 1 ? self::END_OF_CHAIN : $first + $i + 1;
            }
            $this->entries[$id]['start'] = $first;
        }

        $miniFatData = $miniFat === [] ? '' : pack('V*', ...$miniFat);
        $directorySectors = (int) ceil(count($this->entries) * 128 / self::SECTOR);

        $chunks = [];
        $chunks['minifat'] = $miniFatData;
        $chunks['ministream'] = $miniStream;
        foreach ($this->entries as $id => $entry) {
            if ($entry['type'] === 2 && $entry['size'] >= self::CUTOFF) {
                $chunks["stream{$id}"] = $entry['data'];
            }
        }

        $dataSectors = $directorySectors;
        foreach ($chunks as $chunk) {
            $dataSectors += (int) ceil(strlen($chunk) / self::SECTOR);
        }

        $fatSectors = 1;
        while ($fatSectors * (self::SECTOR / 4) < $dataSectors + $fatSectors) {
            $fatSectors++;
        }

        $fat = array_fill(0, $fatSectors, self::FAT_SECTOR);
        $body = '';
        $next = $fatSectors;
        $starts = [];

        $allocate = function (string $data) use (&$fat, &$body, &$next): int {
            if ($data === '') {
                return self::END_OF_CHAIN;
            }

            $count = (int) ceil(strlen($data) / self::SECTOR);
            $start = $next;
            for ($i = 0; $i < $count; $i++) {
                $fat[$next + $i] = $i === $count - 1 ? self::END_OF_CHAIN : $next + $i + 1;
            }
            $body .= str_pad($data, $count * self::SECTOR, "\0");
            $next += $count;

            return $start;
        };

        foreach ($chunks as $key => $chunk) {
            $starts[$key] = $allocate($chunk);
        }

        $this->entries[0]['start'] = $starts['ministream'];
        $this->entries[0]['size'] = strlen($miniStream);
        foreach ($this->entries as $id => $entry) {
            if (isset($starts["stream{$id}"])) {
                $this->entries[$id]['start'] = $starts["stream{$id}"];
            }
        }

        $directory = '';
        foreach ($this->entries as $entry) {
            $directory .= $this->directoryEntry($entry);
        }
        $directory = str_pad($directory, $directorySectors * self::SECTOR, $this->emptyEntry());
        $directoryStart = $allocate($directory);

        $fat = array_pad($fat, $fatSectors * (self::SECTOR / 4), self::FREE);

        $difat = [];
        for ($i = 0; $i < 109; $i++) {
            $difat[] = $i < $fatSectors ? $i : self::FREE;
        }

        $header = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 16)
            . pack('vvvvv', 0x3E, 3, 0xFFFE, 9, 6)
            . str_repeat("\0", 6)
            . pack('VVVVVVVVV', 0, $fatSectors, $directoryStart, 0, self::CUTOFF, $starts['minifat'], (int) ceil(strlen($miniFatData) / self::SECTOR), self::END_OF_CHAIN, 0)
            . pack('V*', ...$difat);

        return $header . pack('V*', ...$fat) . $body;
    }

    private function addChildren(array $items): int
    {
        $previous = null;
        $first = self::FREE;

        foreach ($items as $name => $value) {
            $id = count($this->entries);
            $this->entries[] = is_array($value)
                ? ['name' => (string) $name, 'type' => 1, 'left' => self::FREE, 'right' => self::FREE, 'child' => self::FREE, 'start' => 0, 'size' => 0, 'data' => null]
                : ['name' => (string) $name, 'type' => 2, 'left' => self::FREE, 'right' => self::FREE, 'child' => self::FREE, 'start' => self::END_OF_CHAIN, 'size' => strlen($value), 'data' => $value];

            if (is_array($value)) {
                $this->entries[$id]['child'] = $this->addChildren($value);
            }

            // Arbre dégénéré (chaîne de frères droits) : valide pour la lecture.
            if ($previous === null) {
                $first = $id;
            } else {
                $this->entries[$previous]['right'] = $id;
            }
            $previous = $id;
        }

        return $first;
    }

    private function directoryEntry(array $entry): string
    {
        $name = mb_convert_encoding($entry['name'], 'UTF-16LE', 'UTF-8') . "\0\0";

        return str_pad($name, 64, "\0")
            . pack('v', strlen($name))
            . chr($entry['type']) . chr(1)
            . pack('VVV', $entry['left'], $entry['right'], $entry['child'])
            . str_repeat("\0", 16 + 4 + 16)
            . pack('VVV', $entry['start'], $entry['size'], 0);
    }

    private function emptyEntry(): string
    {
        return str_repeat("\0", 64) . pack('v', 0) . chr(0) . chr(0)
            . pack('VVV', self::FREE, self::FREE, self::FREE)
            . str_repeat("\0", 16 + 4 + 16) . pack('VVV', 0, 0, 0);
    }
}
