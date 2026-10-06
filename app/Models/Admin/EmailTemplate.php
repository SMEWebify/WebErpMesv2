<?php

namespace App\Models\Admin;

use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['document_type', 'subject', 'content'];

    /**
     * Le contenu est réinjecté dans un éditeur riche : nettoyé à l'écriture,
     * et à la lecture pour les modèles enregistrés avant le correctif.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => SafeHtml::clean($value),
            set: fn (?string $value) => SafeHtml::clean($value),
        );
    }
}
