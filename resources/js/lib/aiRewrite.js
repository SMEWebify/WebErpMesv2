// « Réécrire sans fautes » : appel au serveur (proposition, rien n'est
// enregistré) et branchement des boutons posés par <x-FormTextareaComment rewrite>.

import { apiFetch } from './http';

export async function rewriteText(url, text) {
    const data = await apiFetch(url, { method: 'POST', body: JSON.stringify({ text }) });
    return String(data.text ?? '');
}

// État de la proposition : identique au texte saisi, trop longue pour le champ, ou à reprendre.
export function proposalState(original, proposal, maxLength) {
    if (proposal.trim() === String(original ?? '').trim()) return 'unchanged';
    if (maxLength && proposal.length > Number(maxLength)) return 'too_long';
    return 'ok';
}

export function fillMax(text, max) {
    return String(text ?? '').replace(':max', String(max ?? ''));
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Formulaires Blade : le bouton est rendu par le composant seulement si une IA est configurée.
export function bindBladeRewriteButtons(root = document) {
    root.addEventListener('click', async (event) => {
        const button = event.target.closest?.('[data-ai-rewrite-button]');
        if (!button) return;
        const box = button.closest('[data-ai-rewrite]');
        const field = box?.closest('[data-ai-rewrite-field]')?.querySelector('textarea');
        if (!box || !field) return;

        let t = {};
        try { t = JSON.parse(box.dataset.trans ?? '{}'); } catch { /* textes par défaut */ }
        const result = box.querySelector('[data-ai-rewrite-result]');
        const original = field.value;
        if (!original.trim()) return;

        button.disabled = true;
        result.innerHTML = `<div class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1"></span>${escapeHtml(t.rewriting ?? '…')}</div>`;

        try {
            const proposal = await rewriteText(box.dataset.url, original);
            const state = proposalState(original, proposal, box.dataset.max);
            if (state === 'unchanged') {
                result.innerHTML = `<div class="small text-success mt-1"><i class="fas fa-check me-1"></i>${escapeHtml(t.unchanged ?? '')}</div>`;
                return;
            }
            result.innerHTML = `
                <div class="border rounded p-2 mt-1 bg-body-tertiary">
                    <div class="small fw-bold mb-1">${escapeHtml(t.proposal ?? '')}</div>
                    <div class="small mb-2" style="white-space: pre-wrap">${escapeHtml(proposal)}</div>
                    ${state === 'too_long' ? `<div class="small text-danger mb-2">${escapeHtml(fillMax(t.too_long, box.dataset.max))}</div>` : ''}
                    <button type="button" class="btn btn-sm btn-primary me-1" data-ai-accept ${state === 'too_long' ? 'disabled' : ''}>${escapeHtml(t.replace ?? 'OK')}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-ai-ignore>${escapeHtml(t.ignore ?? '×')}</button>
                </div>`;
            result.querySelector('[data-ai-accept]')?.addEventListener('click', () => {
                field.value = proposal;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                result.innerHTML = '';
            });
            result.querySelector('[data-ai-ignore]')?.addEventListener('click', () => { result.innerHTML = ''; });
        } catch (e) {
            result.innerHTML = `<div class="small text-danger mt-1">${escapeHtml(e?.message ?? t.failed ?? '')}</div>`;
        } finally {
            button.disabled = false;
        }
    });
}
