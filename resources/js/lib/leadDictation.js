// Fiche de piste dictée (LeadDictationModal) → corps envoyé à leads.json.dictation.store.
// Société ou contact existants choisis : les champs « nouveau » ne partent pas,
// pour que le serveur ne crée rien en plus.

const blank = (value) => {
    const v = typeof value === 'string' ? value.trim() : value;
    return v === '' || v === undefined ? null : v;
};

export function leadPayload(draft) {
    const companyId = blank(draft.companies_id);
    const contactId = companyId ? blank(draft.companies_contacts_id) : null;

    return {
        companies_id: companyId ? Number(companyId) : null,
        company_label: companyId ? null : blank(draft.company_label),
        companies_contacts_id: contactId ? Number(contactId) : null,
        ...(contactId ? {} : {
            contact_first_name: blank(draft.contact_first_name),
            contact_name: blank(draft.contact_name),
            contact_function: blank(draft.contact_function),
            contact_mail: blank(draft.contact_mail),
            contact_number: blank(draft.contact_number),
        }),
        subject: blank(draft.subject),
        comment: blank(draft.comment),
        source: blank(draft.source),
        priority: Number(draft.priority) || 3,
    };
}
