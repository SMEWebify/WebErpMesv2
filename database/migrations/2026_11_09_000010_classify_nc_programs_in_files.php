<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Programmes ISO (.nc, .tap, .gcode…) : nouveau type « nc » de la GED, affiché
 * en parcours d'outil. Les fichiers déjà déposés étaient rangés en « other » ;
 * seul leur type change (ni chemin ni rôle). Retour arrière : « other ».
 */
return new class extends Migration
{
    private const EXTENSIONS = ['nc', 'tap', 'gcode', 'ngc', 'cnc'];

    public function up(): void
    {
        DB::table('files')
            ->where('kind', 'other')
            ->whereIn(DB::raw('LOWER(extension)'), self::EXTENSIONS)
            ->update(['kind' => 'nc']);
    }

    public function down(): void
    {
        DB::table('files')->where('kind', 'nc')->update(['kind' => 'other']);
    }
};
