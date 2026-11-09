<?php

namespace App\Services\Files\Preview;

use RuntimeException;

/**
 * Lecture d'un fichier composé OLE / CFB ([MS-CFB]), le conteneur des .msg
 * d'Outlook : un petit système de fichiers (stockages = dossiers, flux =
 * fichiers) découpé en secteurs chaînés par une table d'allocation.
 *
 * Lecture seule, en mémoire. Toute chaîne qui boucle ou sort du fichier lève
 * une RuntimeException plutôt que de tourner sans fin sur un fichier abîmé.
 */
class CompoundFile
{
    private const SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    /** Au-delà de cette valeur, un numéro de secteur est un marqueur (fin de chaîne, libre…). */
    private const MAX_REGULAR_SECTOR = 0xFFFFFFFA;

    private const NO_STREAM = 0xFFFFFFFF;

    private const TYPE_STORAGE = 1;
    private const TYPE_STREAM = 2;
    private const TYPE_ROOT = 5;

    private int $sectorSize;
    private int $miniSectorSize;
    private int $miniStreamCutoff;

    /** @var array<int, int> */
    private array $fat = [];

    /** @var array<int, int> */
    private array $miniFat = [];

    /** @var array<int, array{name: string, type: int, left: int, right: int, child: int, start: int, size: int}> */
    private array $entries = [];

    private string $miniStream = '';

    public function __construct(private readonly string $data)
    {
        if (strlen($data) < 512 || ! str_starts_with($data, self::SIGNATURE)) {
            throw new RuntimeException('Not a compound file.');
        }

        $sectorShift = $this->uint16(0x1E);
        $miniSectorShift = $this->uint16(0x20);

        if (! in_array($sectorShift, [9, 12], true) || $miniSectorShift !== 6) {
            throw new RuntimeException('Unsupported sector size.');
        }

        $this->sectorSize = 1 << $sectorShift;
        $this->miniSectorSize = 1 << $miniSectorShift;
        $this->miniStreamCutoff = $this->uint32(0x38);

        $this->readFat();
        $this->readDirectory($this->uint32(0x30));

        $firstMiniFat = $this->uint32(0x3C);
        if ($firstMiniFat < self::MAX_REGULAR_SECTOR) {
            $this->miniFat = array_values(unpack('V*', $this->chain($firstMiniFat)) ?: []);
        }

        $root = $this->entries[0] ?? null;
        if ($root === null || $root['type'] !== self::TYPE_ROOT) {
            throw new RuntimeException('Missing root entry.');
        }

        if ($root['start'] < self::MAX_REGULAR_SECTOR) {
            $this->miniStream = substr($this->chain($root['start']), 0, $root['size']);
        }
    }

    /**
     * Enfants directs d'un stockage, par nom.
     *
     * @return array<string, int> nom => n° d'entrée
     */
    public function children(int $storage = 0): array
    {
        $children = [];
        $stack = [$this->entries[$storage]['child'] ?? self::NO_STREAM];
        $seen = [];

        while ($stack !== []) {
            $id = array_pop($stack);

            if ($id === self::NO_STREAM || ! isset($this->entries[$id]) || isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $entry = $this->entries[$id];
            $children[$entry['name']] = $id;
            $stack[] = $entry['left'];
            $stack[] = $entry['right'];
        }

        return $children;
    }

    public function isStorage(int $id): bool
    {
        return ($this->entries[$id]['type'] ?? null) === self::TYPE_STORAGE;
    }

    public function isStream(int $id): bool
    {
        return ($this->entries[$id]['type'] ?? null) === self::TYPE_STREAM;
    }

    /**
     * Contenu d'un flux.
     */
    public function stream(int $id): string
    {
        $entry = $this->entries[$id] ?? null;

        if ($entry === null || $entry['type'] !== self::TYPE_STREAM || $entry['size'] === 0) {
            return '';
        }

        if ($entry['size'] < $this->miniStreamCutoff) {
            return substr($this->miniChain($entry['start']), 0, $entry['size']);
        }

        return substr($this->chain($entry['start']), 0, $entry['size']);
    }

    private function readFat(): void
    {
        $fatSectors = [];
        for ($i = 0; $i < 109; $i++) {
            $sector = $this->uint32(0x4C + $i * 4);
            if ($sector < self::MAX_REGULAR_SECTOR) {
                $fatSectors[] = $sector;
            }
        }

        // Au-delà de 109 secteurs de FAT, la liste continue dans des secteurs DIFAT chaînés.
        $difat = $this->uint32(0x44);
        $perSector = intdiv($this->sectorSize, 4) - 1;
        $guard = 0;
        while ($difat < self::MAX_REGULAR_SECTOR) {
            if (++$guard > 100000) {
                throw new RuntimeException('DIFAT loop.');
            }

            $values = array_values(unpack('V*', $this->sector($difat)) ?: []);
            foreach (array_slice($values, 0, $perSector) as $sector) {
                if ($sector < self::MAX_REGULAR_SECTOR) {
                    $fatSectors[] = $sector;
                }
            }
            $difat = $values[$perSector] ?? self::NO_STREAM;
        }

        $fat = '';
        foreach ($fatSectors as $sector) {
            $fat .= $this->sector($sector);
        }

        $this->fat = array_values(unpack('V*', $fat) ?: []);
    }

    private function readDirectory(int $first): void
    {
        $directory = $this->chain($first);

        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $nameLength = min(64, unpack('v', $entry, 0x40)[1]);
            $name = $nameLength > 2 ? mb_convert_encoding(substr($entry, 0, $nameLength - 2), 'UTF-8', 'UTF-16LE') : '';

            $this->entries[] = [
                'name' => $name,
                'type' => ord($entry[0x42]),
                'left' => unpack('V', $entry, 0x44)[1],
                'right' => unpack('V', $entry, 0x48)[1],
                'child' => unpack('V', $entry, 0x4C)[1],
                'start' => unpack('V', $entry, 0x74)[1],
                // Taille sur 64 bits ; les versions 3 n'en utilisent que la moitié basse.
                'size' => unpack('V', $entry, 0x78)[1],
            ];
        }
    }

    private function chain(int $start): string
    {
        $data = '';
        $sector = $start;
        $seen = [];

        while ($sector < self::MAX_REGULAR_SECTOR) {
            if (isset($seen[$sector])) {
                throw new RuntimeException('Sector chain loop.');
            }

            $seen[$sector] = true;
            $data .= $this->sector($sector);
            $sector = $this->fat[$sector] ?? throw new RuntimeException('Sector outside the FAT.');
        }

        return $data;
    }

    private function miniChain(int $start): string
    {
        $data = '';
        $sector = $start;
        $seen = [];

        while ($sector < self::MAX_REGULAR_SECTOR) {
            if (isset($seen[$sector])) {
                throw new RuntimeException('Mini sector chain loop.');
            }

            $seen[$sector] = true;
            $data .= substr($this->miniStream, $sector * $this->miniSectorSize, $this->miniSectorSize);
            $sector = $this->miniFat[$sector] ?? throw new RuntimeException('Sector outside the mini FAT.');
        }

        return $data;
    }

    private function sector(int $sector): string
    {
        $offset = ($sector + 1) * $this->sectorSize;

        if ($offset >= strlen($this->data)) {
            throw new RuntimeException('Sector outside the file.');
        }

        return substr($this->data, $offset, $this->sectorSize);
    }

    private function uint16(int $offset): int
    {
        return unpack('v', $this->data, $offset)[1];
    }

    private function uint32(int $offset): int
    {
        return unpack('V', $this->data, $offset)[1];
    }
}
