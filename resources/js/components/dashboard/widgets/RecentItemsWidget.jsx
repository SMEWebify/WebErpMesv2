import React, { useState, useEffect } from 'react';
import WidgetCard from '../WidgetCard.jsx';

// ─── Config statuts ───────────────────────────────────────────────────────────

const ORDER_STATUS = {
    1: { badge: 'badge-info',      labelKey: 'open'             },
    2: { badge: 'badge-warning',   labelKey: 'in_progress'      },
    3: { badge: 'badge-success',   labelKey: 'delivered'        },
    4: { badge: 'badge-danger',    labelKey: 'partly_delivered' },
    5: { badge: 'badge-danger',    labelKey: 'stopped'          },
    6: { badge: 'badge-warning',   labelKey: 'canceled'         },
};

const QUOTE_STATUS = {
    1: { badge: 'badge-info',      labelKey: 'open'     },
    2: { badge: 'badge-warning',   labelKey: 'send'     },
    3: { badge: 'badge-success',   labelKey: 'win'      },
    4: { badge: 'badge-danger',    labelKey: 'lost'     },
    5: { badge: 'badge-secondary', labelKey: 'closed'   },
    6: { badge: 'badge-secondary', labelKey: 'obsolete' },
};

const INVOICE_STATUS = {
    1: { badge: 'badge-info',      labelKey: 'open'      },
    2: { badge: 'badge-warning',   labelKey: 'send'      },
    3: { badge: 'badge-success',   labelKey: 'paid'      },
    4: { badge: 'badge-danger',    labelKey: 'canceled'  },
};

const DELIVERY_STATUS = {
    1: { badge: 'badge-info',      labelKey: 'open'      },
    2: { badge: 'badge-success',   labelKey: 'delivered' },
    3: { badge: 'badge-danger',    labelKey: 'canceled'  },
};

const PURCHASE_STATUS = {
    1: { badge: 'badge-info',      labelKey: 'in_progress'    },
    2: { badge: 'badge-warning',   labelKey: 'ordered'        },
    3: { badge: 'badge-primary',   labelKey: 'partly_received'},
    4: { badge: 'badge-success',   labelKey: 'received'       },
    5: { badge: 'badge-dark',      labelKey: 'canceled'       },
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('T')[0].split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
            .format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

/**
 * Normalise les données brutes : accepte les deux formes
 *   - tableau brut sérialisé depuis Blade (LastOrders / LastQuotes)
 *   - tableau JSON retourné par l'endpoint /kpi/recent/orders|quotes
 *
 * Les deux ont la même shape car le controller sérialise les mêmes champs.
 */
function normalize(raw) {
    return Array.isArray(raw) ? raw : [];
}

// ─── StatusBadge ─────────────────────────────────────────────────────────────

function StatusBadge({ statu, statusConfig, trans }) {
    const cfg = statusConfig[statu];
    if (!cfg) return <span className="badge badge-secondary">{statu}</span>;
    return (
        <span className={`badge ${cfg.badge}`} style={{ fontSize: '0.72rem', whiteSpace: 'nowrap' }}>
            {trans[cfg.labelKey] ?? cfg.labelKey}
        </span>
    );
}

// ─── Table ────────────────────────────────────────────────────────────────────

function getStatusConfig(mode) {
    switch (mode) {
        case 'quotes':    return QUOTE_STATUS;
        case 'invoices':  return INVOICE_STATUS;
        case 'deliveries': return DELIVERY_STATUS;
        case 'purchases': return PURCHASE_STATUS;
        default:          return ORDER_STATUS;
    }
}

function ItemRow({ item, mode, trans, urlShow, urlCompany }) {
    const statusConfig = getStatusConfig(mode);
    const dateValue    = (mode === 'quotes' || mode === 'invoices' || mode === 'purchases')
        ? item.created_at_human
        : formatDate(item.validity_date);
    const isInternal   = mode === 'orders' && item.type !== 1;

    return (
        <tr>
            {/* Code */}
            <td style={{ whiteSpace: 'nowrap' }}>
                {urlShow ? (
                    <a href={`${urlShow}${item.id}`} className="font-weight-bold" style={{ fontSize: '0.85rem' }}>
                        {item.code}
                    </a>
                ) : (
                    <span className="font-weight-bold" style={{ fontSize: '0.85rem' }}>{item.code}</span>
                )}
            </td>

            {/* Société */}
            <td style={{ maxWidth: 140, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                {isInternal ? (
                    <span className="text-muted" style={{ fontSize: '0.82rem' }}>
                        {trans.internal_order ?? 'Interne'}
                    </span>
                ) : item.companie_label ? (
                    urlCompany ? (
                        <a href={`${urlCompany}${item.companies_id}`} style={{ fontSize: '0.82rem' }} title={item.companie_label}>
                            {item.companie_label}
                        </a>
                    ) : (
                        <span style={{ fontSize: '0.82rem' }} title={item.companie_label}>{item.companie_label}</span>
                    )
                ) : (
                    <span className="text-muted">—</span>
                )}
            </td>

            {/* Statut */}
            <td>
                <StatusBadge statu={item.statu} statusConfig={statusConfig} trans={trans} />
            </td>

            {/* Montant */}
            <td className="text-right" style={{ whiteSpace: 'nowrap', fontSize: '0.85rem' }}>
                {item.formatted_total_price ?? '—'}
            </td>

            {/* Date */}
            <td style={{ whiteSpace: 'nowrap', fontSize: '0.82rem', color: '#6c757d' }}>
                {dateValue ?? '—'}
            </td>
        </tr>
    );
}

// ─── RecentItemsWidget ────────────────────────────────────────────────────────

/**
 * RecentItemsWidget — tableau des N dernières commandes ou devis
 *
 * Deux modes :
 *
 *   Mode 1 — données pré-chargées (Blade → props, aucun fetch)
 *     <RecentItemsWidget mode="orders" data={LastOrders} trans={trans} urls={urls} />
 *     <RecentItemsWidget mode="quotes" data={LastQuotes} trans={trans} urls={urls} />
 *
 *   Mode 2 — fetch autonome (dashboard personnalisable)
 *     <RecentItemsWidget mode="orders" endpoint="/fr/kpi/recent/orders" limit={5} trans={trans} urls={urls} />
 *     <RecentItemsWidget mode="quotes" endpoint="/fr/kpi/recent/quotes" limit={5} trans={trans} urls={urls} />
 *
 * Props :
 *   mode      'orders'|'quotes'   — détermine statuts, couleur card, libellés
 *   data      array               — données pré-chargées
 *   endpoint  string              — URL API (si data absent)
 *   limit     number              — nb de lignes à afficher (défaut 5, max 20)
 *   trans     object              — traductions statuts + labels
 *   urls      object              — { show: base URL /id, company: base URL /id, all: lien "voir tout" }
 *   // WidgetCard props
 *   editMode  bool
 *   onRemove  func
 */
export default function RecentItemsWidget({
    mode = 'orders',
    data: initialData,
    endpoint,
    limit = 5,
    trans = {},
    urls = {},
    editMode = false,
    onRemove,
}) {
    const [items, setItems]     = useState(() => initialData ? normalize(initialData) : null);
    const [loading, setLoading] = useState(!initialData && !!endpoint);
    const [error, setError]     = useState(null);

    // Fetch autonome
    useEffect(() => {
        if (initialData || !endpoint) return;

        const params = new URLSearchParams({ limit });
        setLoading(true);
        setError(null);

        fetch(`${endpoint}?${params}`, {
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            credentials: 'same-origin',
        })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(json => { setItems(normalize(json)); setLoading(false); })
            .catch(err => { setError(err.message ?? 'Erreur'); setLoading(false); });
    }, [endpoint, limit]);

    // Ré-normaliser si les données Blade changent
    useEffect(() => {
        if (initialData) setItems(normalize(initialData));
    }, [initialData]);

    const isEmpty  = !loading && !error && (!items || items.length === 0);

    const MODE_CONFIG = {
        orders:     { title: trans.latest_orders    ?? 'Dernières commandes',       empty: trans.no_order    ?? 'Aucune commande',    icon: 'fa-shopping-cart', theme: 'info'    },
        quotes:     { title: trans.latest_quotes    ?? 'Derniers devis',            empty: trans.no_quote    ?? 'Aucun devis',        icon: 'fa-calculator',    theme: 'success' },
        invoices:   { title: trans.latest_invoices  ?? 'Dernières factures',        empty: trans.no_invoice  ?? 'Aucune facture',     icon: 'fa-file-invoice',  theme: 'warning' },
        deliveries: { title: trans.latest_deliveries ?? 'Derniers bons de livraison', empty: trans.no_delivery ?? 'Aucun BL',         icon: 'fa-truck',         theme: 'teal'    },
        purchases:  { title: trans.latest_purchases  ?? 'Commandes achat en attente', empty: trans.no_purchase ?? 'Aucune commande achat', icon: 'fa-box',      theme: 'primary' },
    };
    const cfg = MODE_CONFIG[mode] ?? MODE_CONFIG.orders;

    return (
        <WidgetCard
            title={cfg.title}
            icon={cfg.icon}
            theme={cfg.theme}
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={cfg.empty}
            editMode={editMode}
            onRemove={onRemove}
        >
            <div className="table-responsive" style={{ margin: '0 -0.75rem' }}>
                <table className="table table-sm m-0" style={{ fontSize: '0.83rem' }}>
                    <tbody>
                        {(items ?? []).map((item, i) => (
                            <ItemRow
                                key={item.id ?? i}
                                item={item}
                                mode={mode}
                                trans={trans}
                                urlShow={urls.show}
                                urlCompany={urls.company}
                            />
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Footer "Voir tout" */}
            {urls.all && (
                <div style={{ textAlign: 'right', padding: '0.5rem 0 0' }}>
                    <a href={urls.all} className="btn btn-sm btn-secondary">
                        {trans.view_all ?? 'Voir tout'}
                    </a>
                </div>
            )}
        </WidgetCard>
    );
}
