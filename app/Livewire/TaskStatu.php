<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Planning\Task;
use App\Services\TaskService;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\Planning\TaskActivities;
use App\Services\QualityNonConformityService;
use App\Models\Products\StockLocationProducts;
use App\Services\SerialNumberComponentService;
use App\Models\Products\SerialNumbers;

class TaskStatu extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $Task;
    public $nextTask;
    public $previousTask;
    public $taskStockMoves;
    public $taskActivities;
    public $lastTaskActivities;

    public $search = '';
    public $user_id ;

    public $timelineData = [];


    public $addGoodQt = 0;
    public $addBadQt = 0;
    public $not_recalculate = true;
    private $RecalculateBooleanValue = 0;

    public $userforced_ressource = false;

    public $end_date;

    public $tasksOpen, $tasksInProgress, $tasksPending, $tasksOngoing, $tasksCompleted, $averageProcessingTime, $userProductivity, $totalResourcesAllocated, $resourceHours, $totalProducedHours, $averageTRS;  

    public $StockLocationsProducts = null; 

    public $userSelect;
    public $selectedRessource;
    protected $notificationService;
    protected $qualityNonConformityService;
    protected $taskService;
    protected $serialNumberComponentService;

    public $consumedSerials = [];
    
    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->notificationService = App::make(NotificationService::class);
        $this->qualityNonConformityService = App::make(QualityNonConformityService::class);
        $this->taskService = App::make(TaskService::class);
        $this->serialNumberComponentService = App::make(SerialNumberComponentService::class);
    }
    
    // Validation Rules
    protected $rules = [
        'addGoodQt' =>'required|numeric|min:0',
        'addBadQt' =>'required|numeric|min:0',
    ];

    public function mount($TaskId) 
    {
        $this->user_id = Auth::id();
        $this->search = $TaskId;
        $this->taskStockMoves = StockMove::where('task_id', $this->search)->get();
        $this->lastTaskActivities = TaskActivities::where('task_id', $this->search)->latest()->first();
        $this->taskActivities = TaskActivities::where('task_id', $this->search)->get();
        $this->Task = Task::with('OrderLines.order')->find($this->search);
        $this->userSelect = User::select('id', 'name')->get();
       // $this->end_date = $this->Task->end_date;
        if($this->Task){
            if($this->Task->component_id){
                $this->StockLocationsProducts = StockLocationProducts::where('products_id', $this->Task->component_id)->get(); 
            }
        }
        // Organiser les données pour la timeline
        $this->timelineData = [];

        foreach ($this->taskStockMoves as $taskStockMove){
            if($taskStockMove->typ_move == 3){
                $this->timelineData[] = [
                    'date' => $taskStockMove->created_at->format('d M Y'),
                    'icon' => 'fas fa-list  bg-primary',
                    'content' => __('general_content.new_entry_stock_trans_key') .' x'. $taskStockMove->qty .'- '. __('general_content.purchase_order_reception_trans_key'),
                    'details' => $taskStockMove->GetPrettyCreatedAttribute(),
                ];
            }
            elseif($taskStockMove->typ_move == 2){
                $this->timelineData[] = [
                    'date' => $taskStockMove->created_at->format('d M Y'),
                    'icon' => 'fas fa-list  bg-warning',
                    'content' => __('general_content.new_sorting_stock_trans_key') .' x'. $taskStockMove->qty .'- '. __('general_content.task_allocation_trans_key'),
                    'details' => $taskStockMove->GetPrettyCreatedAttribute(),
                ];
            }
            elseif($taskStockMove->typ_move == 6){
                $this->timelineData[] = [
                    'date' => $taskStockMove->created_at->format('d M Y'),
                    'icon' => 'fas fa-list  bg-warning',
                    'content' => __('general_content.new_sorting_stock_trans_key') .' x'. $taskStockMove->qty .'- '. __('general_content.task_allocation_trans_key'),
                    'details' => $taskStockMove->GetPrettyCreatedAttribute(),
                ];
            }
        }

        if($this->Task){
            // Récupérer la tâche précédente
            $this->previousTask = Task::where('order_lines_id', $this->Task->order_lines_id)
                                            ->where('ordre', '<', $this->Task->ordre)
                                            ->orderBy('ordre', 'desc')
                                            ->first();

            // Récupérer la tâche suivante
            $this->nextTask = Task::where('order_lines_id', $this->Task->order_lines_id)
                                        ->where('ordre', '>', $this->Task->ordre)
                                        ->orderBy('ordre', 'asc')
                                        ->first();
        }

        foreach ($this->taskActivities as $taskActivitie){

            if ($taskActivitie->user && $taskActivitie->user->name) { $userName = $taskActivitie->user->name; }
            else{$userName = 'System'; }

            if($taskActivitie->type == TaskActivities::TYPE_START){
                $icon = 'fas fa-play-circle bg-primary';
                $content =  $userName .' - '. __('general_content.set_to_start_trans_key');
            }
            elseif ($taskActivitie->type == TaskActivities::TYPE_END){
                $icon = 'fas fa-stop-circle bg-warning';
                $content =  $userName .' - '. __('general_content.set_to_end_trans_key');
            }
            elseif ($taskActivitie->type == TaskActivities::TYPE_FINISH){
                $icon = 'fas fa-check-circle bg-info';
                $content =  $userName .' - '. __('general_content.set_to_finish_trans_key');
            }
            elseif ($taskActivitie->type == TaskActivities::TYPE_DECLARE_GOOD){
                $icon = 'fas fa-thumbs-up bg-success';
                $content =  $userName .' - '. __('general_content.declare_finish_trans_key') .' '. $taskActivitie->good_qt .' '.  __('general_content.part_trans_key');
            }
            elseif ($taskActivitie->type == TaskActivities::TYPE_DECLARE_BAD){
                $icon = 'fas fa-thumbs-down bg-danger';
                $content =  $userName .' - '. __('general_content.declare_rejected_trans_key') .' '. $taskActivitie->bad_qt .' '. __('general_content.part_trans_key');
            }
            elseif ($taskActivitie->type == TaskActivities::TYPE_COMMENT){
                $icon = 'fas fa-comment-dots bg-secondary';
                $content = $userName .' - '. $taskActivitie->comment;
            }

            $this->timelineData[] = [
                'date' => $taskActivitie->created_at->format('d M Y'),
                'icon' => $icon,
                'content' => $content,
                'details' => $taskActivitie->GetPrettyCreatedAttribute(),
            ];
        }


        // Ajouter une commande s'il y en a
        if(!is_null($this->Task)){
            foreach ($this->Task->purchaseLines as $purchaseLine) {

                $this->timelineData[] = [
                    'date' => $purchaseLine->created_at->format('d M Y'),
                    'icon' => 'fas fa-calendar-alt  bg-success',
                    'content' => __('general_content.purchase_trans_key') ." ". $purchaseLine->purchase->code ." - ".  __('general_content.qty_reciept_trans_key') .":". $purchaseLine->receipt_qty ."/". $purchaseLine->qty ,
                    'details' => $purchaseLine->GetPrettyCreatedAttribute(),
                ];

                if (!is_null($purchaseLine->purchaseReceiptLines)) {
                    foreach ($purchaseLine->purchaseReceiptLines as $receipt) {
                        $this->timelineData[] = [
                            'date' => $receipt->created_at->format('d M Y'),
                            'icon' => 'fas fa-calendar-alt  bg-warning',
                            'content' => __('general_content.po_receipt_trans_key') ." " . $receipt->label ." - ".  __('general_content.qty_reciept_trans_key') .":". $receipt->receipt_qty,
                            'details' => $receipt->GetPrettyCreatedAttribute(),
                        ];
                    }
                }
            }

            // Ajouter la task initiale
            $this->timelineData[] = [
                'date' => $this->Task->created_at->format('d M Y'),
                'icon' => 'fa fa-tags bg-primary',
                'content' => "Task créée",
                'details' => $this->Task->GetPrettyCreatedAttribute(),
            ];
            
        }

        // Trier le tableau par date
        usort($this->timelineData, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    }

    public function render()
    {
        // Initialisation de la variable $ressources pour éviter les erreurs
        $ressources = [];

        if(!empty($this->search)){
            $this->lastTaskActivities = TaskActivities::where('task_id', $this->search)->latest()->first();
            $this->taskActivities = TaskActivities::where('task_id', $this->search)->get();
            $this->Task = Task::with('OrderLines.order', 'resources')->find($this->search);
            if($this->Task){
                // Récupérer la tâche précédente
                $this->previousTask = Task::where('order_lines_id', $this->Task->order_lines_id)
                        ->where('ordre', '<', $this->Task->ordre)
                        ->orderBy('ordre', 'desc')
                        ->first();

                // Récupérer la tâche suivante
                $this->nextTask = Task::where('order_lines_id', $this->Task->order_lines_id)
                    ->where('ordre', '>', $this->Task->ordre)
                    ->orderBy('ordre', 'asc')
                    ->first();
            }

            // Récupérer le service lié à la tâche
            $service = $this->Task->service;
            
            // Récupérer les moyens de production associés à ce service
            $ressources = $service ? $service->ressources()->pluck('label', 'id') : [];

            // Vérifier si une ressource est déjà affectée et initialiser `selectedRessource` et `userforced_ressource`
            if ($this->Task->resources()->exists()) {
                $resource = $this->Task->resources()->first();
                $this->selectedRessource = $resource->id;
                 // Pivot table field
                if($resource->pivot->userforced_ressource == 1){
                    $this->userforced_ressource = true;
                }
            }
        }

        if($this->Task){
            if($this->Task->component_id){
                $this->StockLocationsProducts = StockLocationProducts::where('products_id', $this->Task->component_id)->get(); 
            }
        }
        
        return view('livewire.task-statu', [
            'Task' => $this->Task,
            'taskActivities' => [$this->taskActivities],
            'lastTaskActivities' => $this->lastTaskActivities,
            'StockLocationsProducts' => $this->StockLocationsProducts,
            'ressources' => $ressources,
        ]);
    }

    public function StartTimeTask($taskId)
    {
        //create entry qty int task
        $this->taskService->recordTaskActivity( $taskId, 1, 0, 0);

        $StatusUpdate = Status::select('id')->where('title', 'In progress')->first();

        /* // update task statu on Kanban*/
        if($StatusUpdate->id){
            $Task = Task::where('id',$taskId)->update(['status_id'=>$StatusUpdate->id]);
            event(new TaskChangeStatu($taskId));
        }

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function EndTimeTask($taskId)
    {
        //create entry qty int task
        $this->taskService->recordTaskActivity( $taskId, 2, 0, 0);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }
    
    public function EndTask($taskId)
    {
        //create entry qty int task
        $this->taskService->recordTaskActivity( $taskId, 3, 0, 0);

        $StatusUpdate = Status::select('id')->where('title', 'Finished')->first();

        /* // update task statu on Kanban*/
        if($StatusUpdate->id){
            $Task = Task::where('id',$taskId)->update(['status_id'=>$StatusUpdate->id]);
            event(new TaskChangeStatu($taskId));
        }

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function addGoodQtFromUser()
    {
        $this->validate();
        //create entry qty int task
        $this->taskService->recordTaskActivity( $this->search, 4, $this->addGoodQt, 0);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function FastaddGoodQt($qty)
    {
        $this->addGoodQt += $qty;
        //create entry qty int task
        $this->taskService->recordTaskActivity( $this->search, 4, $qty, 0);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function addGoodQtFromStock($composantId, $taskId)
    {
        
        $quantityRemaining = $this->addGoodQt;

        $StockLocationProduct = StockLocationProducts::where('products_id', $composantId)->get();
        foreach ($StockLocationProduct as $stock) {
            $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
            if ($quantityToWithdraw != 0) {
                $data = [
                    'user_id' => Auth::id(),
                    'qty' => $quantityToWithdraw,
                    'stock_location_products_id' => $stock->id,
                    'task_id' => $taskId,
                    'typ_move' => 2,
                ];
                StockMove::create($data);
            }
            $quantityRemaining -= $quantityToWithdraw;
            if ($quantityRemaining <= 0) {
                break;
            }
        }
        
        $this->validate();
        
        //create entry qty int task
        $this->taskService->recordTaskActivity( $this->search, 4, $this->addGoodQt, 0);

        if (!empty($this->consumedSerials)) {
            $parentSerial = SerialNumbers::where('task_id', $taskId)->first();
            if ($parentSerial) {
                foreach ($this->consumedSerials as $componentSerialId) {
                    $this->serialNumberComponentService->linkComponent($parentSerial->id, $componentSerialId, $taskId);
                }
            }
        }

        $this->render();

        // If the quantity requested is greater than the total quantity available
        if ($quantityRemaining < 0) {
            session()->flash('success','Outing stock successfully with negative stock');
        }
        else{
            session()->flash('success','Sorting stock successfully');
        }
    }

    public function addRejectedQt()
    {
        $this->validate();
        //create entry qty int task
        $this->taskService->recordTaskActivity( $this->search, 5, 0, $this->addBadQt);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function FastaddBadQt($qty)
    {
        $this->addGoodQt -= $qty;
        //create entry qty int task
        $this->taskService->recordTaskActivity( $this->search, 5, 0, $qty);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function updateDateTask(){
        if($this->not_recalculate) $this->RecalculateBooleanValue = 1;
        Task::find($this->search)->fill([
            'not_recalculate'=>$this->RecalculateBooleanValue,
            'end_date'=>$this->end_date,
        ])->save();

        session()->flash('success','Date Updated Successfully');
    }

    public function createNC($id, $companie_id, $id_service){

        $this->qualityNonConformityService->createNC($id, $companie_id, $id_service, 'task');

        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully created non conformitie.');
    }

    public function goToTask($taskId) 
    {
        // Rédirection ou mise à jour de la vue avec la nouvelle tâche
        $this->mount($taskId);
    }

    public function updateRessource()
    {
        // Valider que la ressource a bien été sélectionnée
        $this->validate([
            'selectedRessource' => 'required|exists:methods_ressources,id',
        ]);

        // Mettre à jour la tâche avec la ressource sélectionnée
        $this->Task->resources()->sync([$this->selectedRessource => [
            'autoselected_ressource' => 0,
            'userforced_ressource' => 1,  // Indiquer que l'utilisateur a forcé la ressource
        ]]);

        $this->userforced_ressource =true; 

        // Optionnel : Ajouter un message de succès ou rediriger l'utilisateur
        session()->flash('message', 'Le moyen de production a été mis à jour avec succès.');
    }
}
