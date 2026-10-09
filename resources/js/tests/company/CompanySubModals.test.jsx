import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import { CreateAddressSubModal, CreateContactSubModal } from '../../components/company/CompanySubModals';

const trans = {
    new_address: 'Nouvelle adresse', new_contact: 'Nouveau contact',
    ordre: 'Ordre', adress_label: 'Libellé', adress: 'Adresse', postal_code: 'CP', city: 'Ville',
    country: 'Pays', phone: 'Téléphone', email: 'Email', civility: 'Civilité', first_name: 'Prénom',
    name: 'Nom', function: 'Fonction', mobile: 'Mobile', cancel: 'Annuler', save: 'Enregistrer', saving: '…',
};

function stubFetch(status, body) {
    const spy = vi.fn(() => Promise.resolve(new Response(JSON.stringify(body), { status })));
    vi.stubGlobal('fetch', spy);
    return spy;
}

afterEach(() => vi.unstubAllGlobals());

const inputAfter = (label) => screen.getByText(label).parentElement.querySelector('input, select');

describe('CreateContactSubModal', () => {
    const props = (extra = {}) => ({
        show: true, companiesId: 12, storeUrl: '/contacts', trans,
        onCreated: vi.fn(), onClose: vi.fn(), ...extra,
    });

    it('ne rend rien fermé', () => {
        const { container } = render(<CreateContactSubModal {...props({ show: false })} />);
        expect(container.innerHTML).toBe('');
    });

    it('envoie les champs avec la société, ordre 1 par défaut, puis ferme', async () => {
        const spy = stubFetch(201, { id: 5, name: 'Jean Dupont' });
        const p = props();
        render(<CreateContactSubModal {...p} />);

        fireEvent.change(inputAfter('Civilité'), { target: { value: 'Mme' } });
        fireEvent.change(inputAfter('Prénom *'), { target: { value: 'Jeanne' } });
        fireEvent.change(inputAfter('Nom *'), { target: { value: 'Dupont' } });
        fireEvent.click(screen.getByText('Enregistrer'));

        await waitFor(() => expect(p.onCreated).toHaveBeenCalledWith({ id: 5, name: 'Jean Dupont' }));
        expect(p.onClose).toHaveBeenCalled();

        const [url, init] = spy.mock.calls[0];
        expect(url).toBe('/contacts');
        expect(init.method).toBe('POST');
        expect(init.headers['X-CSRF-TOKEN']).toBe('test-csrf-token');
        expect(JSON.parse(init.body)).toEqual({
            ordre: '1', civility: 'Mme', first_name: 'Jeanne', name: 'Dupont',
            function: '', number: '', mobile: '', mail: '', companies_id: 12,
        });
    });

    it('propose les civilités imprimées sur les documents', () => {
        render(<CreateContactSubModal {...props()} />);
        const options = [...inputAfter('Civilité').querySelectorAll('option')].map(o => o.value);
        expect(options).toEqual(['', 'M.', 'Mme', 'Dr']);
    });

    it('affiche les erreurs de validation sous chaque champ, email compris, sans fermer', async () => {
        stubFetch(422, { message: 'Invalide', errors: { name: ['Nom requis'], mail: ['Email invalide'] } });
        const p = props();
        render(<CreateContactSubModal {...p} />);
        fireEvent.click(screen.getByText('Enregistrer'));

        expect(await screen.findByText('Nom requis')).toBeInTheDocument();
        expect(screen.getByText('Email invalide')).toBeInTheDocument();
        expect(p.onCreated).not.toHaveBeenCalled();
        expect(p.onClose).not.toHaveBeenCalled();
    });

    it('Annuler ferme sans envoyer', () => {
        const spy = stubFetch(201, {});
        const p = props();
        render(<CreateContactSubModal {...p} />);
        fireEvent.click(screen.getByText('Annuler'));
        expect(p.onClose).toHaveBeenCalled();
        expect(spy).not.toHaveBeenCalled();
    });
});

describe('CreateAddressSubModal', () => {
    it('envoie l’adresse avec la société et renvoie l’adresse créée', async () => {
        const spy = stubFetch(201, { id: 9, label: 'Siège', adress: '1 rue X' });
        const onCreated = vi.fn();
        const onClose = vi.fn();
        render(<CreateAddressSubModal show companiesId={3} storeUrl="/addresses" trans={trans}
                                      onCreated={onCreated} onClose={onClose} />);

        fireEvent.change(inputAfter('Libellé *'), { target: { value: 'Siège' } });
        fireEvent.change(inputAfter('Adresse *'), { target: { value: '1 rue X' } });
        fireEvent.click(screen.getByText('Enregistrer'));

        await waitFor(() => expect(onCreated).toHaveBeenCalledWith({ id: 9, label: 'Siège', adress: '1 rue X' }));
        expect(onClose).toHaveBeenCalled();
        expect(JSON.parse(spy.mock.calls[0][1].body)).toMatchObject({ ordre: '1', label: 'Siège', adress: '1 rue X', companies_id: 3 });
    });

    it('libellés français par défaut si une traduction manque', () => {
        render(<CreateAddressSubModal show companiesId={3} storeUrl="/a" trans={{}}
                                      onCreated={() => {}} onClose={() => {}} />);
        expect(screen.getByText('Nouvelle adresse')).toBeInTheDocument();
        expect(screen.getByText('Enregistrer')).toBeInTheDocument();
    });
});
