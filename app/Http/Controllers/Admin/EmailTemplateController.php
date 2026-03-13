<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Admin\EmailTemplate;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $emailTemplates = EmailTemplate::all();
        $availableDocumentTypes = $this->availableDocumentTypes();

        return view('admin/templates-index', compact('emailTemplates', 'availableDocumentTypes'));
    }

    public function store(Request $request)
    {
        $allowedDocumentTypes = array_keys($this->availableDocumentTypes());

        $request->validate([
            'document_type' => ['required', 'string', Rule::in($allowedDocumentTypes)],
            'subject' => 'required|string',
            'content' => 'required|string',
        ]);

        EmailTemplate::create($request->all());

        return redirect()->back()->with('success', 'Modèle de mail créé avec succès !');
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $request->validate([
            'subject' => 'required|string',
            'content' => 'required|string',
        ]);

        $emailTemplate->update($request->all());

        return redirect()->back()->with('success', 'Modèle de mail mis à jour !');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->back()->with('success', 'Modèle supprimé avec succès !');
    }

    private function availableDocumentTypes(): array
    {
        $documentTypes = [];

        if (auth()->user()->can('quotes-menu')) {
            $documentTypes['quote'] = 'general_content.quote_trans_key';
        }

        if (auth()->user()->can('orders-menu')) {
            $documentTypes['order'] = 'general_content.orders_trans_key';
        }

        if (auth()->user()->can('deliverys-menu')) {
            $documentTypes['delivery'] = 'general_content.delivery_notes_trans_key';
        }

        if (auth()->user()->can('invoices-menu')) {
            $documentTypes['invoice'] = 'general_content.invoice_trans_key';
            $documentTypes['creditnote'] = 'general_content.credit_note_trans_key';
        }

        if (auth()->user()->can('purchases-menu')) {
            $documentTypes['purchase-quotation'] = 'general_content.requests_for_quotation_list_trans_key';
            $documentTypes['purchase'] = 'general_content.purchase_order_trans_key';
        }

        return $documentTypes;
    }
}
