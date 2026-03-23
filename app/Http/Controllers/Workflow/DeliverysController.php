<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Packaging;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\DeliveryKPIService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Workflow\StorePackagingRequest;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;
use App\Http\Requests\Workflow\UpdatePackagingRequest;

class DeliverysController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    protected $deliveryKPIService;
    protected $customFieldService;

    public function __construct(SelectDataService $SelectDataService,
                                DeliveryKPIService $deliveryKPIService,
                                CustomFieldService $customFieldService
                    ){
        $this->SelectDataService = $SelectDataService;
        $this->deliveryKPIService = $deliveryKPIService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        $CurentYear = Carbon::now()->format('Y');
        $data['deliverysDataRate'] = $this->deliveryKPIService->getDeliveriesDataRate();
        $data['deliveryMonthlyRecap'] = $this->deliveryKPIService->getDeliveryMonthlyRecap( $CurentYear);

        return view('workflow/deliverys-index')->with('data',$data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function request()
    {    
        return view('workflow/deliverys-request');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Deliverys $id)
    {
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Deliverys(), $id->id);
        
        
        $PruchasesSelect = $this->SelectDataService->getPurchases();
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('delivery', $id->id);
        $allDelivered = $id->DeliveryLines->every(function($line) {
            return $line->invoice_status == 4;
        });

        return view('workflow/deliverys-show', [
            'Delivery' => $id,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
            'allDelivered' => $allDelivered,
            'PruchasesSelect' => $PruchasesSelect,
        ]);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateDeliveryRequest $request)
    {
        $Delivery = Deliverys::find($request->id);
        $Delivery->label=$request->label;
        $Delivery->statu=$request->statu;
        $Delivery->purchases_id=$request->purchases_id;
        $Delivery->tracking_number=$request->tracking_number;
        $Delivery->comment=$request->comment;
        $Delivery->save();

        return redirect()->route('deliverys.show', ['id' =>  $Delivery->id])->with('success', 'Successfully updated Delivery');
    }

    /**
     * Store a newly created packaging in storage.
     *
     * @param StorePackagingRequest $request
     * @param Deliverys $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function packagingsStore(StorePackagingRequest $request, Deliverys $id)
    {
        // Création et sauvegarde du packaging
        Packaging::create(
            $request->only([
                'deliverys_id',
                'code',
                'type',
                'status',
                'gross_weight',
                'net_weight',
                'length',
                'width',
                'height',
                'comment',
                'packing_date',
                'loaded_date',
                'load_comment',
            ]) + [
                'user_id' => Auth::id(),
            ]
        );

        // Redirection avec un message de succès
        return redirect()->route('deliverys.show', ['id' => $id->id])->with('success', 'Successfully add packaging');
    }

    // -------------------------------------------------------------------------
    // JSON endpoints for the React DeliverysIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search          = $request->get('search', '');
        $statuses        = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $invoiceStatuses = array_filter(array_map('intval', (array) $request->get('invoice_statuses', [])));
        $sortField       = $request->get('sort', 'created_at');
        $sortAsc         = $request->boolean('asc', false);
        $companyId       = $request->get('company_id');

        $allowed = ['code', 'label', 'created_at', 'statu', 'companies_id', 'delivery_lines_count'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = Deliverys::withCount('DeliveryLines')
            ->with(['companie:id,label', 'UserManagement:id,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses))
            ->when($invoiceStatuses, fn ($q) => $q->whereIn('invoice_status', $invoiceStatuses))
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'delivery_lines_count' => $query->orderBy('delivery_lines_count', $dir),
            'companies_id'         => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = deliverys.companies_id) {$dir}"),
            default                => $query->orderBy($sortField, $dir),
        };

        $deliverys = $query->paginate(15);

        return response()->json([
            'data' => $deliverys->map(fn ($d) => [
                'id'             => $d->id,
                'code'           => $d->code,
                'label'          => $d->label,
                'statu'          => $d->statu,
                'invoice_status' => $d->invoice_status,
                'lines_count'    => $d->delivery_lines_count,
                'companie'       => $d->companie ? ['id' => $d->companie->id, 'label' => $d->companie->label] : null,
                'user'           => $d->UserManagement ? ['name' => $d->UserManagement->name] : null,
                'created_at'     => $d->created_at?->format('d/m/Y'),
                'url'            => route('deliverys.show', ['id' => $d->id]),
                'pdf_url'        => route('pdf.delivery', ['Document' => $d->id]),
            ]),
            'meta' => [
                'total'        => $deliverys->total(),
                'per_page'     => $deliverys->perPage(),
                'current_page' => $deliverys->currentPage(),
                'last_page'    => $deliverys->lastPage(),
            ],
        ]);
    }

    public function packagingsUpdate(UpdatePackagingRequest $request, Deliverys $id)
    {
        $packaging = Packaging::findOrFail($request->id);
        $packaging->update([
            'code' => $request->code,
            'type' => $request->type,
            'status' => $request->status,
            'gross_weight' => $request->gross_weight,
            'net_weight' => $request->net_weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
            'comment' => $request->comment,
            'packing_date' => $request->packing_date,
            'loaded_date' => $request->loaded_date,
        ]);

        return redirect()->route('deliverys.show', ['id' =>  $packaging->deliverys_id])->with('success', 'Successfully update packaging');
    }

}
