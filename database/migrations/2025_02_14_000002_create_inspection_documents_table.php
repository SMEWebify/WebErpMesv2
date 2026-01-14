<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_project_id');
            $table->string('type')->default('plan');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime');
            $table->integer('page_count')->nullable();
            $table->string('version_label')->nullable();
            $table->timestamps();

            $table->index('inspection_project_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspection_documents');
    }
}
