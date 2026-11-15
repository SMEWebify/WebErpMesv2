<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recopie de la GED vers SharePoint (Microsoft Graph), à sens unique.
 *
 * - `sharepoint_settings` : table singleton, comme `nest2bend_settings`. Application
 *   Entra ID (tenant, client, secret chiffré par le cast `encrypted`), site et
 *   bibliothèque cibles. `site_id` / `drive_id` sont résolus au test et gardés.
 * - `sharepoint_mirror_items` : une ligne par (lignée de fichier, entité). La GED reste
 *   la source de vérité ; cette table ne dit que ce qui a été recopié, où, et en quelle
 *   version. Une nouvelle version remplace le même élément SharePoint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sharepoint_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 100)->nullable();
            $table->string('client_id', 100)->nullable();
            // Cipher gonfle la taille en base64 → text plutôt que string.
            $table->text('client_secret')->nullable();
            $table->string('site_url')->nullable();
            // Nom de la bibliothèque de documents ; vide = bibliothèque par défaut du site.
            $table->string('library')->nullable();
            $table->string('root_folder', 100)->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('site_id')->nullable();
            $table->string('drive_id')->nullable();
            $table->string('drive_web_url', 500)->nullable();
            // none | ok | error
            $table->string('status', 16)->default('none');
            $table->timestamp('checked_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('sharepoint_mirror_items', function (Blueprint $table) {
            $table->id();
            // Lignée de la GED (files.version_group_id, sinon id du fichier).
            $table->unsignedBigInteger('lineage_id');
            // Dernière version à recopier.
            $table->unsignedBigInteger('file_id')->index();
            $table->string('fileable_type');
            $table->unsignedBigInteger('fileable_id');
            // pending | synced | failed | skipped
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedBigInteger('synced_file_id')->nullable();
            $table->unsignedInteger('synced_version')->nullable();
            $table->string('folder_item_id')->nullable();
            $table->string('folder_web_url', 1000)->nullable();
            $table->string('remote_item_id')->nullable();
            $table->string('remote_name')->nullable();
            $table->string('web_url', 1000)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['lineage_id', 'fileable_type', 'fileable_id'], 'sharepoint_mirror_items_lineage_entity_unique');
            $table->index(['fileable_type', 'fileable_id'], 'sharepoint_mirror_items_entity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sharepoint_mirror_items');
        Schema::dropIfExists('sharepoint_settings');
    }
};
