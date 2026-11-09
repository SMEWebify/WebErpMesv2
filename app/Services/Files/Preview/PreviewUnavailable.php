<?php

namespace App\Services\Files\Preview;

use RuntimeException;

/**
 * L'aperçu ne peut pas être produit ; le fichier reste téléchargeable.
 * reason : unsupported | missing | too_large | unreadable.
 */
class PreviewUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("File preview unavailable: {$reason}");
    }
}
