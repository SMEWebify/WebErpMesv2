<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Workflow\OrderLines;
use App\Services\DocumentCodeGenerator;
use App\Models\Quality\QualityDerogation;
use App\Models\Companies\CompaniesContacts;
use App\Services\Quality\DerogationApproval;
use Spatie\Activitylog\Models\Activity;
use App\Services\Integrations\Signature\DerogationSignatureService;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use App\Http\Requests\Quality\StoreQualityDerogationRequest;
use App\Http\Requests\Quality\UpdateQualityDerogationRequest;

class QualityDerogationController extends Controller
{
    /** Champs saisis à l'écran (index et fiche). */
    private const FIELDS = ['label', 'statu', 'user_id', 'pb_descp', 'proposal', 'reply',
        'customer_reply_date', 'customer_signatory', 'quality_non_conformitie_id',
        'companies_id', 'companies_contacts_id', 'order_lines_id', 'decision'];

    protected $SelectDataService;
    protected $documentCodeGenerator;

    public function __construct(SelectDataService $SelectDataService,
                                DocumentCodeGenerator $documentCodeGenerator,
                                private readonly DerogationApproval $approval,
                                private readonly DerogationSignatureService $signatures)
    {
        $this->SelectDataService = $SelectDataService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {

        $userSelect = $this->SelectDataService->getUsers();
        $NonConformitysSelect = $this->SelectDataService->getQualityNonConformity();
        $QualityDerogations = QualityDerogation::with(['UserManagement', 'QualityNonConformity', 'companie'])->orderBy('id')->paginate(10);
        $LastDerogation = QualityDerogation::orderBy('id', 'desc')->first();
        $codeDerogation = $LastDerogation ? $LastDerogation->id : 0;
        $codeDerogation = $this->documentCodeGenerator->generateDocumentCode('derogation', $codeDerogation);

        return view('quality/quality-derogations', [
            'codeDerogation' =>  $codeDerogation,
            'QualityDerogations' => $QualityDerogations,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'userSelect' => $userSelect,
        ]);
    }

    /**
     * Fiche d'une dérogation : client, réponse, preuves (GED), signature, validation, historique.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function show(int $id)
    {
        $derogation = QualityDerogation::with([
            'UserManagement', 'validator', 'QualityNonConformity', 'companie', 'contact',
            'orderLine.order', 'signatures.sender',
        ])->findOrFail($id);

        $history = Activity::forSubject($derogation)->with('causer')->latest('id')->limit(50)->get();

        return view('quality/quality-derogation-show', [
            'Derogation'           => $derogation,
            'userSelect'           => $this->SelectDataService->getUsers(),
            'NonConformitysSelect' => $this->SelectDataService->getQualityNonConformity(),
            'CompanieSelect'       => $this->SelectDataService->getCompanies(),
            'ContactSelect'        => $derogation->companies_id ? $this->SelectDataService->getContact($derogation->companies_id) : collect(),
            'OrderLineSelect'      => $this->orderLinesFor($derogation->companies_id, $derogation->order_lines_id),
            'missing'              => $this->approval->missingForValidation($derogation),
            'canValidate'          => $this->approval->canValidate(auth()->user()),
            'signatureBlocked'     => $this->signatures->blockingReason($derogation),
            'signatureAvailable'   => $this->signatures->isAvailable(),
            'history'              => $history,
        ]);
    }

    /**
     * Contacts et lignes de commande ouvertes d'une société (sélecteurs de la fiche).
     */
    public function companyData(Request $request)
    {
        $companyId = (int) $request->query('company_id');

        return response()->json([
            'contacts' => CompaniesContacts::where('companies_id', $companyId)
                ->get(['id', 'first_name', 'name', 'mail'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => trim($c->first_name . ' ' . $c->name) . ($c->mail ? ' <' . $c->mail . '>' : '')]),
            'order_lines' => $this->orderLinesFor($companyId, null)
                ->map(fn ($l) => ['id' => $l->id, 'label' => $this->orderLineLabel($l)]),
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityDerogationRequest $request)
    {
        $Derogation = new QualityDerogation();
        $Derogation->code = $request->input('code');
        $this->fill($Derogation, $request);
        $Derogation->type = $request->type ? QualityDerogation::TYPE_INTERNAL : QualityDerogation::TYPE_EXTERNAL;

        if ($refusal = $this->refuseValidation($Derogation, $request)) {
            return back()->withInput()->withErrors(['statu' => $refusal]);
        }

        $this->approval->stamp($Derogation, $request->user());
        $Derogation->save();

        return redirect()->route('quality.derogation.show', ['id' => $Derogation->id])->with('success', __('derogations.created'));
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityDerogationRequest $request, int $id)
    {
        $QualityDerogation = QualityDerogation::findOrFail($id);

        // Une dérogation validée est figée, sauf pour qui peut valider.
        if ($QualityDerogation->isValidated() && ! $this->approval->canValidate($request->user())) {
            return back()->withErrors(['statu' => __('derogations.locked')]);
        }

        $this->fill($QualityDerogation, $request);

        // Interrupteur coché = interne ; décoché, il n'est pas envoyé.
        $QualityDerogation->type = $request->type_update ? QualityDerogation::TYPE_INTERNAL : QualityDerogation::TYPE_EXTERNAL;

        if ($refusal = $this->refuseValidation($QualityDerogation, $request)) {
            return back()->withInput()->withErrors(['statu' => $refusal]);
        }

        $this->approval->stamp($QualityDerogation, $request->user());
        $QualityDerogation->save();

        $redirect = back()->with('success', __('derogations.saved'));

        if ($this->signatures->voidIfOutdated($QualityDerogation)) {
            $redirect->with('warning', __('derogations.voided_after_change'));
        }

        return $redirect;
    }

    /**
     * Envoie la demande de dérogation au contact du client (DocuSign, par e-mail).
     */
    public function sendSignature(Request $request, int $id)
    {
        $derogation = QualityDerogation::with('contact')->findOrFail($id);

        try {
            $signature = $this->signatures->send($derogation, $request->user());
        } catch (SignatureException $e) {
            return back()->withErrors(['signature' => __('derogations.sign_error', ['error' => $e->getMessage()])]);
        }

        return back()->with('success', __('derogations.sign_sent', ['email' => $signature->signer_email]));
    }

    /**
     * Relit l'état de la dernière enveloppe chez DocuSign.
     */
    public function syncSignature(int $id)
    {
        $derogation = QualityDerogation::findOrFail($id);
        $signature = $derogation->signatures()->latest('id')->first();

        if ($signature) {
            try {
                $this->signatures->sync($signature);
            } catch (SignatureException $e) {
                return back()->withErrors(['signature' => __('derogations.sign_error', ['error' => $e->getMessage()])]);
            }
        }

        return back()->with('success', __('derogations.sync_done'));
    }

    /**
     * Aperçu du document envoyé au client.
     */
    public function preview(int $id)
    {
        $derogation = QualityDerogation::findOrFail($id);

        return response($this->signatures->requestPdf($derogation), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . str_replace(['/', '\\', '"'], '-', $derogation->code) . '.pdf"',
        ]);
    }

    /**
     * Recopie les champs envoyés (seulement ceux présents : le formulaire de
     * l'index ne porte pas le client), et reprend client et ligne de commande
     * de la non-conformité quand ils ne sont pas saisis.
     */
    private function fill(QualityDerogation $derogation, Request $request): void
    {
        foreach (self::FIELDS as $field) {
            if (! $request->exists($field)) {
                continue;
            }

            $value = $request->input($field);
            // Les sélecteurs envoient « null » ou une chaîne vide pour « aucun ».
            $derogation->{$field} = ($value === '' || $value === 'null') ? null : $value;
        }

        $nc = $derogation->quality_non_conformitie_id ? $derogation->QualityNonConformity()->first() : null;
        if ($nc) {
            $derogation->companies_id ??= $nc->companie_id;
            $derogation->order_lines_id ??= $nc->order_lines_id;
        }
    }

    /**
     * Refus de passer en « Validé » : permission, puis éléments manquants.
     */
    private function refuseValidation(QualityDerogation $derogation, Request $request): ?string
    {
        if (! $derogation->isValidated() || ! $derogation->isDirty('statu')) {
            return null;
        }

        if (! $this->approval->canValidate($request->user())) {
            return __('derogations.no_permission');
        }

        $missing = $this->approval->missingForValidation($derogation);

        return $missing === [] ? null : __('derogations.cannot_validate', ['missing' => implode(', ', $missing)]);
    }

    /**
     * Lignes article des commandes de la société, restant à livrer, plus la ligne
     * déjà choisie (même livrée).
     */
    private function orderLinesFor(?int $companyId, ?int $selectedId)
    {
        if (! $companyId) {
            return $selectedId ? OrderLines::with('order:id,code')->whereKey($selectedId)->get() : collect();
        }

        return OrderLines::with('order:id,code')
            ->articles()
            ->whereHas('order', fn ($q) => $q->where('companies_id', $companyId))
            ->where(fn ($q) => $q->where('delivered_remaining_qty', '>', 0)
                ->when($selectedId, fn ($q) => $q->orWhere('id', $selectedId)))
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'orders_id', 'code', 'label', 'qty']);
    }

    private function orderLineLabel(OrderLines $line): string
    {
        return trim(($line->order?->code ?? '') . ' — ' . ($line->code ? $line->code . ' ' : '') . $line->label . ' (' . $line->qty . ')');
    }
}
