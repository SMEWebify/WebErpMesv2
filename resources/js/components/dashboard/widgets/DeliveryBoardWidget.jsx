import React, { useState, useEffect } from 'react';
import WidgetCard from '../WidgetCard.jsx';

// ─── Styles inline (reproduit le CSS du dashboard.blade.php) ─────────────────

const S = {
    board: {
        display: 'flex',
        flexDirection: 'column',
        gap: '0.75rem',
    },
    lane: {
        background: '#f8f9fb',
        border: '1px solid #e6e9ef',
        borderRadius: 12,
        padding: '0.75rem',
    },
    laneDanger: {
        borderColor: '#ffd4da',
        background: '#fff6f7',
    },
    laneContent: {
        display: 'flex',
        gap: '0.65rem',
        overflowX: 'auto',
        paddingBottom: '0.25rem',
    },
    badge: {
        minWidth: 150,
        borderRadius: 10,
        color: '#fff',
        padding: '0.9rem 0.75rem',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        fontWeight: 600,
        textAlign: 'center',
        textDecoration: 'none',
        flexShrink: 0,
        fontSize: '0.85rem',
        lineHeight: 1.3,
    },
    badgeWarning: { background: '#f4b000' },
    badgeDanger:  { background: '#dc3545' },
    card: {
        minWidth: 150,
        borderRadius: 10,
        color: '#fff',
        padding: '0.9rem 0.75rem',
        lineHeight: 1.4,
        fontSize: '0.95rem',
        flexShrink: 0,
        display: 'flex',
        flexDirection: 'column',
        gap: '0.25rem',
    },
    cardWarning: { background: '#f2bf33' },
    cardDanger:  { background: '#df3345' },
    cardLink: {
        color: '#fff',
        fontWeight: 600,
        textDecoration: 'none',
        display: 'flex',
        alignItems: 'center',
        gap: '0.35rem',
        fontSize: '0.88rem',
        whiteSpace: 'nowrap',
        overflow: 'hidden',
        textOverflow: 'ellipsis',
    },
    empty: {
        minWidth: 180,
        fontWeight: 600,
        color: '#6c757d',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '0.9rem',
        fontSize: '0.85rem',
    },
    more: {
        minWidth: 90,
        borderRadius: 10,
        color: '#fff',
        textDecoration: 'none',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        flexDirection: 'column',
        fontWeight: 700,
        padding: '0.9rem 0.6rem',
        flexShrink: 0,
        fontSize: '0.9rem',
        gap: '0.2rem',
    },
    moreWarning: { background: '#f4b000' },
    moreDanger:  { background: '#dc3545' },
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: '2-digit' }).format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

/**
 * Normalise les données brutes (Blade ou API) en { incoming, incomingMore, late, lateMore }.
 *
 * Format Blade : { incomingOrders, incomingOrdersCount, lateOrders, lateOrdersCount }
 *   - incomingOrders[n].orders_id, .delivery_date, .order.code  (Eloquent sérialisé)
 * Format API  : { incoming, incoming_more, late, late_more }
 */
function normalize(raw) {
    if (!raw) return { incoming: [], incomingMore: 0, late: [], lateMore: 0 };

    const isApi = 'incoming' in raw;
    return {
        incoming:     isApi ? raw.incoming     : (raw.incomingOrders ?? []),
        incomingMore: isApi ? raw.incoming_more : (raw.incomingOrdersCount ?? 0),
        late:         isApi ? raw.late          : (raw.lateOrders ?? []),
        lateMore:     isApi ? raw.late_more     : (raw.lateOrdersCount ?? 0),
    };
}

// ─── Sous-composants ──────────────────────────────────────────────────────────

function DeliveryCard({ item, variant, urlOrders, urlCalendar }) {
    const code = item.order?.code ?? `#${item.orders_id}`;
    const date = formatDate(item.delivery_date);
    const cardStyle = { ...S.card, ...(variant === 'danger' ? S.cardDanger : S.cardWarning) };

    return (
        <div style={cardStyle}>
            <a
                href={urlOrders ? `${urlOrders}/${item.orders_id}` : '#'}
                style={S.cardLink}
                title={code}
            >
                <i className="fas fa-calculator" style={{ flexShrink: 0 }} />
                <span style={{ overflow: 'hidden', textOverflow: 'ellipsis' }}>{code}</span>
            </a>
            <a href={urlCalendar ?? '#'} style={{ ...S.cardLink, fontWeight: 400 }}>
                <i className="fas fa-calendar-alt" style={{ flexShrink: 0 }} />
                {date}
            </a>
        </div>
    );
}

function DeliveryLane({ items, more, variant, label, urlOrders, urlCalendar, urlAll, emptyText }) {
    const iDanger  = variant === 'danger';
    const badgeStyle = { ...S.badge, ...(iDanger ? S.badgeDanger : S.badgeWarning) };
    const moreStyle  = { ...S.more,  ...(iDanger ? S.moreDanger  : S.moreWarning) };
    const laneStyle  = { ...S.lane,  ...(iDanger ? S.laneDanger  : {}) };

    return (
        <div style={laneStyle}>
            <div style={S.laneContent}>
                {/* Badge titre */}
                <a href={urlAll ?? '#'} style={badgeStyle}>
                    {label}
                    <i className="fas fa-ban" style={{ marginTop: '0.4rem' }} />
                </a>

                {/* Cartes commandes */}
                {items.length === 0 ? (
                    <div style={S.empty}>{emptyText}</div>
                ) : (
                    items.map((item, i) => (
                        <DeliveryCard
                            key={item.orders_id ?? i}
                            item={item}
                            variant={variant}
                            urlOrders={urlOrders}
                            urlCalendar={urlCalendar}
                        />
                    ))
                )}

                {/* "+N" overflow */}
                {more > 0 && (
                    <a href={urlAll ?? '#'} style={moreStyle}>
                        +{more}
                        <i className="fas fa-arrow-circle-right" style={{ marginTop: '0.2rem' }} />
                    </a>
                )}
            </div>
        </div>
    );
}

// ─── DeliveryBoardWidget ──────────────────────────────────────────────────────

/**
 * DeliveryBoardWidget — kanban incoming / late orders
 *
 * Deux modes :
 *
 *   Mode 1 — données pré-chargées (Blade → props, aucun fetch)
 *     <DeliveryBoardWidget
 *       data={{ incomingOrders, incomingOrdersCount, lateOrders, lateOrdersCount }}
 *       urls={{ orders: '/fr/orders', ordersAll: '/fr/orders', calendar: '/fr/production/calendar/orders' }}
 *       trans={trans}
 *     />
 *
 *   Mode 2 — fetch autonome (dashboard personnalisable)
 *     <DeliveryBoardWidget
 *       endpoint="/fr/kpi/delivery/board"
 *       urls={{ orders: '/fr/orders', ordersAll: '/fr/orders', calendar: '/fr/production/calendar/orders' }}
 *       trans={trans}
 *     />
 *
 * Props :
 *   data      object   — données pré-chargées (format Blade)
 *   endpoint  string   — URL de l'API (si data absent)
 *   urls      object   — { orders: base URL pour /id, ordersAll, calendar }
 *   trans     object   — { incoming_orders, late_orders, no_coming_orders,
 *                          no_late_orders, order_to_be_delivered }
 *   // WidgetCard props
 *   editMode   bool
 *   onRemove   func
 */
export default function DeliveryBoardWidget({
    data: initialData,
    endpoint,
    urls = {},
    trans = {},
    editMode = false,
    onRemove,
}) {
    const [board, setBoard]     = useState(() => initialData ? normalize(initialData) : null);
    const [loading, setLoading] = useState(!initialData && !!endpoint);
    const [error, setError]     = useState(null);

    // Fetch autonome
    useEffect(() => {
        if (initialData || !endpoint) return;

        setLoading(true);
        setError(null);

        fetch(endpoint, {
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
            .then(json => {
                setBoard(normalize(json));
                setLoading(false);
            })
            .catch(err => {
                setError(err.message ?? 'Erreur de chargement');
                setLoading(false);
            });
    }, [endpoint]);

    // Ré-normaliser si les données Blade changent
    useEffect(() => {
        if (initialData) setBoard(normalize(initialData));
    }, [initialData]);

    const isEmpty = !loading && !error && (
        !board || (board.incoming.length === 0 && board.late.length === 0)
    );

    return (
        <WidgetCard
            title={trans.order_to_be_delivered ?? 'Commandes à livrer'}
            icon="fa-shopping-cart"
            theme="orange"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_orders_to_deliver ?? 'Aucune commande à livrer'}
            editMode={editMode}
            onRemove={onRemove}
        >
            <div style={S.board}>
                <DeliveryLane
                    items={board?.incoming ?? []}
                    more={board?.incomingMore ?? 0}
                    variant="warning"
                    label={trans.incoming_orders ?? 'À livrer (< 2j)'}
                    urlOrders={urls.orders}
                    urlCalendar={urls.calendar}
                    urlAll={urls.ordersAll}
                    emptyText={trans.no_coming_orders ?? 'Aucune commande imminente'}
                />
                <DeliveryLane
                    items={board?.late ?? []}
                    more={board?.lateMore ?? 0}
                    variant="danger"
                    label={trans.late_orders ?? 'En retard'}
                    urlOrders={urls.orders}
                    urlCalendar={urls.calendar}
                    urlAll={urls.ordersAll}
                    emptyText={trans.no_late_orders ?? 'Aucun retard'}
                />
            </div>
        </WidgetCard>
    );
}
