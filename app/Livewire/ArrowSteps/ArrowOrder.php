<?php

namespace App\Livewire\ArrowSteps;

use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;

class ArrowOrder extends Component
{
    public $OrderId;
    public $OrderType;
    public $OrderStatu;

    public function mount($OrderId, $OrderType, $OrderStatu) 
    {
        $this->OrderId = $OrderId;
        $this->OrderType = $OrderType;
        $this->OrderStatu = $OrderStatu;
    }

    public function render()
    {
        return view('livewire.arrow-steps.arrow-order');
    }

    public function changeStatu($statuNumber){
        try {
            // Changer le statut de la commande
            $order = Orders::findOrFail($this->OrderId);
            $order->statu = $statuNumber;
            $order->save();

            // Récupérer les tâches associées à cette commande
            $tasks = Task::whereHas('OrderLines', function ($query) {
                $query->where('orders_id', $this->OrderId);
            })->get();

            $statusStarted = Status::where('title', 'Started')->first();
            $statusInProgress = Status::where('title', 'In progress')->first();
            $statusSuspended = Status::where('title', 'Suspended')->first();
            $statusFinished = Status::where('title', 'Finished')->first();
    
            foreach ($tasks as $task) {
                // Statut "In Progress" pour la commande
                if ($statuNumber == 2) {
                    // Utiliser 'Started' ou 'In progress' si 'Started' n'existe pas
                    $status = $statusStarted ?? $statusInProgress;
                    if ($status) {
                        $task->update(['status_id' => $status->id]);
                    }
                }
    
                // Statut "Stopped" pour la commande
                elseif ($statuNumber == 5) {
                    // Utiliser 'Suspended' ou 'Finished' si 'Suspended' n'existe pas
                    $status = $statusSuspended ?? $statusFinished;
                    if ($status) {
                        $task->update(['status_id' => $status->id]);
                    }
                }
    
                // Statut "Canceled" pour la commande
                elseif ($statuNumber == 6) {
                    // Utiliser 'Finished'
                    if ($statusFinished) {
                        $task->update(['status_id' => $statusFinished->id]);
                    }
                }
            }
    
            return redirect()->route('orders.show', ['id' => $this->OrderId])->with('success', 'Successfully updated status and tasks');
        } catch (\Exception $e) {
            session()->flash('error', "Something went wrong while updating the status or tasks");
        }
    }
}
