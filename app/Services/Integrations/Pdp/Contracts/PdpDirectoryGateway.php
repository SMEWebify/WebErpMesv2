<?php

namespace App\Services\Integrations\Pdp\Contracts;

/**
 * Contrat OPTIONNEL pour les PDP exposant l'annuaire de la facturation
 * électronique.
 *
 * Deux usages distincts, à ne pas confondre :
 *
 *  - **Nos lignes d'annuaire** (listEntries / openEntry / closeEntry) : les
 *    adresses auxquelles NOS factures fournisseurs nous seront remises. Sans
 *    au moins une ligne ouverte, aucune facture ne peut nous parvenir — c'est
 *    le prérequis de l'obligation de réception au 1er septembre 2026, et seule
 *    une Plateforme Agréée peut ouvrir cette ligne pour le compte d'une société.
 *
 *  - **L'annuaire public** (lookupEntries / searchCompanies) : les adresses de
 *    NOS CLIENTS, à renseigner sur leur fiche société pour savoir où leur
 *    envoyer une facture.
 */
interface PdpDirectoryGateway
{
    /**
     * Lignes d'annuaire ouvertes pour la société courante.
     *
     * @return array<int, array{id: string, identifier: string, directory: string, is_replyto: bool, effective_date: ?string}>
     */
    public function listEntries(): array;

    /**
     * Ouvre une ligne d'annuaire.
     *
     * @param string  $identifier    SIREN, SIREN_SUFFIXE, ou adresse Peppol complète
     * @param ?string $effectiveDate date de prise d'effet (AAAA-MM-JJ), annuaire français
     *
     * @return array{id: string, identifier: string, directory: string}
     */
    public function openEntry(string $identifier, ?string $effectiveDate = null): array;

    /** Ferme une ligne d'annuaire. */
    public function closeEntry(string $id): void;

    /**
     * Adresses électroniques de facturation d'une entreprise française.
     *
     * @return array<int, array{identifier: string, is_active: bool, name: ?string, city: ?string}>
     */
    public function lookupEntries(string $siren): array;

    /**
     * Recherche dans l'annuaire des entreprises éligibles à la facturation
     * électronique française.
     *
     * @param array{number?: string, name?: string, post_code?: string, limit?: int} $criteria
     *
     * @return array<int, array{number: string, formal_name: string, address: ?string, postcode: ?string, city: ?string}>
     */
    public function searchCompanies(array $criteria): array;
}
