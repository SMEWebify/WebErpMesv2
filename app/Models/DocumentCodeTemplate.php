<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCodeTemplate extends Model
{
    // Allow mass assignment for these columns
    protected $fillable = ['document_type', 'template', 'reset_period'];
    
    /**
     *Allows to retrieve a default template if none is found
     */
    public static function getTemplateForDocument(string $documentType): ?self
    {
        // Search the database for a template matching the document type
        return self::where('document_type', $documentType)->first();
    }
}
