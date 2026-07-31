<?php

use App\Services\Files\FileKindResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Functional family of the file, resolved from its extension.
            $table->string('kind', 20)->default(FileKindResolver::KIND_OTHER)->after('type');
            // Lowercase extension, kept denormalized so the viewer can dispatch without parsing.
            $table->string('extension', 16)->nullable()->after('kind');
            // Storage disk and relative path. Legacy rows keep them null and are
            // resolved from public_path() by FileStorageService.
            $table->string('disk', 32)->nullable()->after('extension');
            $table->string('path')->nullable()->after('disk');

            // Files imported from the legacy product columns have no uploader.
            $table->integer('user_id')->nullable()->change();

            $table->index('kind');
        });

        // Backfill kind/extension for the files uploaded before this migration.
        DB::table('files')->orderBy('id')->chunkById(500, function ($files) {
            foreach ($files as $file) {
                $source = $file->original_file_name ?: $file->name;
                $extension = FileKindResolver::extensionOf($source);

                DB::table('files')->where('id', $file->id)->update([
                    'kind' => FileKindResolver::fromExtension($extension),
                    'extension' => $extension,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'extension', 'disk', 'path']);
            $table->integer('user_id')->nullable(false)->change();
        });
    }
};
