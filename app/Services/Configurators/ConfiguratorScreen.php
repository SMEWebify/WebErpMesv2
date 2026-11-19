<?php

namespace App\Services\Configurators;

use App\Models\Integrations\ExternalConfigurator;

/**
 * Données du bouton « Configurer » pour l'écran des lignes d'un devis ou d'une
 * commande. Sans configurateur actif : aucun endpoint, donc aucun bouton.
 */
class ConfiguratorScreen
{
    /** @return array<string, string> */
    public static function endpoints(string $type, int $documentId): array
    {
        if (ExternalConfigurator::availableFor($type)->isEmpty()) {
            return [];
        }

        return [
            'configuratorOpen'   => route('configurators.open', ['type' => $type, 'documentId' => $documentId]),
            'configuratorResult' => route('configurators.result', ['session' => '__SESSION__']),
            'configuratorApply'  => route('configurators.apply', ['session' => '__SESSION__']),
            'configuratorCancel' => route('configurators.cancel', ['session' => '__SESSION__']),
        ];
    }

    /** @return array{ui: array, list: array<int, array{id: int, label: string, display: string}>} */
    public static function trans(string $type): array
    {
        return [
            'ui'   => __('configurators.ui'),
            'list' => ExternalConfigurator::availableFor($type)
                ->map(fn (ExternalConfigurator $c) => ['id' => $c->id, 'label' => $c->label, 'display' => $c->display])
                ->values()
                ->all(),
        ];
    }
}
