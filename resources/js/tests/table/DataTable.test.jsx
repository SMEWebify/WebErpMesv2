import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import { DataTable, resolveOrder, toISODate } from '../../components/table';

const STORAGE = { order: 'test_table_col_order', hidden: 'test_table_hidden_cols' };
const money   = n => `${n.toFixed(2)} €`;

const COLUMNS = [
    { key: 'code',       label: 'Code',    sortable: true, filter: 'text', mobile: 'title' },
    { key: 'companie',   label: 'Client',  sortable: 'companie_label', filter: 'text',
      render: r => r.companie?.label ?? '—', filterValue: r => r.companie?.label, mobile: 'subtitle' },
    { key: 'statu',      label: 'Statut',  render: r => <span className="badge">S{r.statu}</span>, mobile: 'badge' },
    { key: 'created_at', label: 'Créé le', sortable: true, filter: 'date' },
    { key: 'total',      label: 'Total',   align: 'right', bold: true, render: r => money(r.amount),
      total: { value: r => r.amount, format: money }, mobile: 'amount' },
];

const ROWS = [
    { id: 1, code: 'CM-001', companie: { label: 'Acme' },   statu: 1, created_at: '05/01/2026', amount: 100, url: '/orders/1' },
    { id: 2, code: 'CM-002', companie: { label: 'Bolt' },   statu: 2, created_at: '20/02/2026', amount: 50.5, url: '/orders/2' },
    { id: 3, code: 'CM-003', companie: null,                statu: 1, created_at: '10/03/2026', amount: 0,   url: '/orders/3' },
];

const setup = (props = {}) => render(
    <DataTable rows={ROWS} columns={COLUMNS} storage={STORAGE} rowHref={r => r.url}
        sortField="code" sortAsc onSort={() => {}} trans={{ no_results: 'Aucun résultat' }} {...props} />
);
const headers = () => [...document.querySelectorAll('thead tr:first-child th')].map(th => th.textContent.replace('×', '').trim()).filter(Boolean);
const bodyCodes = () => [...document.querySelectorAll('tbody tr')].map(tr => tr.cells[0].textContent);

afterEach(() => vi.restoreAllMocks());

describe('toISODate / resolveOrder', () => {
    it('normalise les dates françaises et ISO', () => {
        expect(toISODate('5/1/2026')).toBe('2026-01-05');
        expect(toISODate('2026-03-10T08:00:00Z')).toBe('2026-03-10');
        expect(toISODate('')).toBe('');
        expect(toISODate('n/a')).toBe('');
    });

    it("'merge' ajoute les colonnes nouvelles, 'strict' garde l'historique", () => {
        expect(resolveOrder(['b', 'a'], ['a', 'b', 'c'])).toEqual(['b', 'a', 'c']);
        expect(resolveOrder(['b', 'x', 'a'], ['a', 'b', 'c'])).toEqual(['b', 'a', 'c']);
        expect(resolveOrder(['b', 'a'], ['a', 'b', 'c'], 'strict')).toEqual(['b', 'a']);
        expect(resolveOrder(['b', 'x'], ['a', 'b', 'c'], 'strict')).toEqual(['a', 'b', 'c']);
        expect(resolveOrder(null, ['a', 'b'])).toEqual(['a', 'b']);
    });
});

describe('DataTable — PC', () => {
    it('rend les colonnes, les cellules et le lien de ligne', () => {
        setup();
        expect(headers()).toEqual(['Code', 'Client', 'Statut', 'Créé le', 'Total']);
        expect(bodyCodes()).toEqual(['CM-001', 'CM-002', 'CM-003']);
        expect(screen.getAllByRole('link')[0]).toHaveAttribute('href', '/orders/1');
        expect(screen.getAllByText('—')).toHaveLength(1);
    });

    it('trie sur le champ serveur de la colonne, pas sur une colonne non triable', () => {
        const onSort = vi.fn();
        setup({ onSort });
        fireEvent.click(screen.getByText('Client'));
        fireEvent.click(screen.getByText('Statut'));
        fireEvent.click(screen.getAllByText('Code')[0]);
        expect(onSort.mock.calls).toEqual([['companie_label'], ['code']]);
    });

    it("masque une colonne, l'enregistre, et la réaffiche par sa puce", () => {
        setup();
        const th = screen.getByText('Client').closest('th');
        fireEvent.click(within(th).getByLabelText('Masquer la colonne'));
        expect(headers()).not.toContain('Client');
        expect(JSON.parse(localStorage.getItem(STORAGE.hidden))).toEqual(['companie']);
        fireEvent.click(screen.getByText('+ Client'));
        expect(headers()).toContain('Client');
        expect(JSON.parse(localStorage.getItem(STORAGE.hidden))).toEqual([]);
    });

    it("réordonne par glisser-déposer et enregistre l'ordre", () => {
        setup();
        const th = label => screen.getAllByText(label)[0].closest('th');
        fireEvent.dragStart(th('Total'));
        fireEvent.dragOver(th('Code'));
        fireEvent.drop(th('Code'));
        expect(headers()[0]).toBe('Total');
        expect(JSON.parse(localStorage.getItem(STORAGE.order))).toEqual(['total', 'code', 'companie', 'statu', 'created_at']);
    });

    it('reprend les préférences enregistrées (clés existantes)', () => {
        localStorage.setItem(STORAGE.order, JSON.stringify(['statu', 'code', 'companie', 'total']));
        localStorage.setItem(STORAGE.hidden, JSON.stringify(['companie']));
        setup();
        // created_at, absente de l'ordre enregistré, est ajoutée à la fin (mode merge)
        expect(headers()).toEqual(['Statut', 'Code', 'Total', 'Créé le']);
    });

    it('filtre par texte (sur filterValue) et par plage de dates', () => {
        setup();
        fireEvent.change(screen.getByLabelText('Client'), { target: { value: 'bol' } });
        expect(bodyCodes()).toEqual(['CM-002']);
        fireEvent.change(screen.getByLabelText('Client'), { target: { value: '' } });
        const [from, to] = document.querySelectorAll('input[type="date"]');
        fireEvent.change(from, { target: { value: '2026-02-01' } });
        fireEvent.change(to,   { target: { value: '2026-02-28' } });
        expect(bodyCodes()).toEqual(['CM-002']);
    });

    it('les filtres contrôlés remontent à l’écran', () => {
        const onColFiltersChange = vi.fn();
        setup({ colFilters: { code: '003' }, onColFiltersChange });
        expect(bodyCodes()).toEqual(['CM-003']);
        fireEvent.change(screen.getByLabelText('Code'), { target: { value: '00' } });
        expect(onColFiltersChange).toHaveBeenCalledWith({ code: '00' });
    });

    it('total de la page filtrée, libellé dans la colonne précédente', () => {
        setup({ trans: { total: 'Total HT' } });
        const cells = [...document.querySelectorAll('tfoot td')].map(td => td.textContent);
        expect(cells).toEqual(['', '', '', 'Total HT', '150.50 €', '']);
        fireEvent.change(screen.getByLabelText('Code'), { target: { value: '001' } });
        expect(document.querySelector('tfoot').textContent).toContain('100.00 €');
    });

    it('chargement et liste vide', () => {
        const { rerender } = setup({ loading: true });
        expect(document.querySelector('tbody .fa-spinner')).toBeInTheDocument();
        rerender(<DataTable rows={[]} columns={COLUMNS} storage={STORAGE} trans={{ no_results: 'Aucun résultat' }} />);
        expect(screen.getByText('Aucun résultat')).toBeInTheDocument();
        expect(document.querySelector('tfoot')).toBeNull();
    });

    it('rowActions remplace le bouton par défaut', () => {
        setup({ rowActions: r => <button type="button">PDF {r.id}</button> });
        expect(screen.getByText('PDF 2')).toBeInTheDocument();
        expect(document.querySelector('tbody .fa-eye')).toBeNull();
    });

    it("unsortableIcon={false} n'affiche l'icône de tri que sur les colonnes triables", () => {
        setup({ unsortableIcon: false });
        const th = label => screen.getAllByText(label)[0].closest('th');
        expect(th('Statut').querySelector('.fa-sort')).toBeNull();
        expect(th('Client').querySelector('.fa-sort')).not.toBeNull();
    });

    it('unsortableCursor et nowrap reproduisent les variantes des écrans', () => {
        setup({ unsortableCursor: 'default', columns: [...COLUMNS.slice(0, 4), { ...COLUMNS[4], nowrap: false }] });
        const th = label => screen.getAllByText(label)[0].closest('th');
        expect(th('Statut').style.cursor).toBe('default');
        expect(th('Code').style.cursor).toBe('pointer');
        expect(screen.getByText('100.00 €').closest('td').style.whiteSpace).toBe('');
    });

    it('actionsHeader et tableClassName', () => {
        setup({ actionsHeader: 'Action', tableClassName: 'table table-sm mb-0' });
        const ths = document.querySelectorAll('thead tr:first-child th');
        expect(ths[ths.length - 1].textContent).toBe('Action');
        expect(document.querySelector('table').className).toBe('table table-sm mb-0');
    });

    it("actionsColumn={false} et colonne non masquable (actions déplaçables)", () => {
        setup({ actionsColumn: false, columns: [...COLUMNS, { key: 'actions', label: 'Actions', hideable: false, render: () => 'go' }] });
        const ths = [...document.querySelectorAll('thead tr:first-child th')];
        expect(ths).toHaveLength(6);
        expect(ths[5].textContent).toContain('Actions');
        expect(ths[5].querySelector('[aria-label="Masquer la colonne"]')).toBeNull();
        expect(document.querySelector('tbody tr').cells).toHaveLength(6);
    });

    it('renderExpanded : ligne dépliée sur toute la largeur, sous la carte en mobile', () => {
        const renderExpanded = r => r.id === 2 && <form>Diagnostic {r.code}</form>;
        const { unmount } = setup({ renderExpanded });
        const cell = screen.getByText('Diagnostic CM-002').closest('td');
        expect(cell.colSpan).toBe(6);
        expect(cell.closest('tr').previousElementSibling.cells[0].textContent).toBe('CM-002');
        unmount();
        setup({ renderExpanded, forceLayout: 'cards' });
        expect(screen.getByText('Diagnostic CM-002').closest('.list-group-item')).toHaveTextContent('CM-002');
    });

    it('hideable et reorderable désactivables', () => {
        setup({ hideable: false, reorderable: false });
        expect(screen.queryByLabelText('Masquer la colonne')).toBeNull();
        expect(document.querySelector('th[draggable]')).toBeNull();
    });
});

describe('DataTable — cartes (mobile)', () => {
    it('une carte par ligne : titre cliquable, sous-titre, badge, montant', () => {
        setup({ forceLayout: 'cards' });
        expect(document.querySelector('table')).toBeNull();
        const link = screen.getByText('CM-002').closest('a');
        expect(link).toHaveAttribute('href', '/orders/2');
        expect(link).toHaveClass('stretched-link');
        const card = link.closest('.list-group-item');
        expect(card).toHaveStyle({ minHeight: '44px' });
        expect(within(card).getByText('Bolt')).toBeInTheDocument();
        expect(within(card).getByText('S2')).toBeInTheDocument();
        expect(within(card).getByText('50.50 €')).toBeInTheDocument();
    });

    it("n'affiche pas les valeurs vides et ajoute le total", () => {
        setup({ forceLayout: 'cards', trans: { total: 'Total' } });
        const card = screen.getByText('CM-003').closest('.list-group-item');
        expect(within(card).queryByText('—')).toBeNull();
        expect(screen.getByText('150.50 €')).toBeInTheDocument();
    });

    it('mobileOrder réordonne le sous-titre sans toucher aux colonnes', () => {
        const columns = [
            ...COLUMNS.slice(0, 1),
            { key: 'label', label: 'Libellé', mobile: 'subtitle' },
            { ...COLUMNS[1], mobileOrder: 1 },
        ];
        render(<DataTable rows={[{ ...ROWS[0], label: 'Capots' }]} columns={columns} forceLayout="cards" />);
        expect(document.querySelector('.small.text-muted').textContent).toBe('Acme · Capots');
    });

    it("mobileActions : boutons sous la carte, rien quand la ligne n'en a pas", () => {
        setup({ forceLayout: 'cards', mobileActions: r => r.statu === 2 && <a href={`/pdf/${r.id}`}>PDF</a> });
        expect(screen.getAllByText('PDF')).toHaveLength(1);
        const card = screen.getByText('PDF').closest('.list-group-item');
        expect(within(card).getByText('CM-002')).toBeInTheDocument();
        expect(screen.getByText('CM-001').closest('.list-group-item').querySelector('.mt-2')).toBeNull();
    });

    it('tri par liste déroulante et bouton de sens', () => {
        const onSort = vi.fn();
        setup({ forceLayout: 'cards', onSort });
        fireEvent.change(screen.getByLabelText('Trier par'), { target: { value: 'created_at' } });
        fireEvent.click(screen.getByLabelText('Tri décroissant'));
        expect(onSort.mock.calls).toEqual([['created_at'], ['code']]);
    });

    it('bascule en cartes quand la media query md correspond', () => {
        // jsdom ne fournit pas matchMedia : sans lui, le tableau PC reste affiché
        window.matchMedia = q => ({
            matches: q === '(max-width: 767.98px)', media: q,
            addEventListener: () => {}, removeEventListener: () => {},
        });
        try {
            setup();
            expect(document.querySelector('table')).toBeNull();
            expect(document.querySelectorAll('.list-group-item')).toHaveLength(4);
        } finally {
            delete window.matchMedia;
        }
    });
});
