<?php

namespace App\Http\Controllers\Quality;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Services\SelectDataService;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Methods\MethodsServices;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;

class QualityController extends Controller
{
    
    protected $SelectDataService;
    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {

        $userSelect = $this->SelectDataService->getUsers();
        $ServicesSelect = $this->SelectDataService->getServices();
        $CompaniesSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->orderBy('label')->get();
        $CausesSelect = $this->SelectDataService->getQualityCause();
        $FailuresSelect = $this->SelectDataService->getQualityFailure();
        $CorrectionsSelect = $this->SelectDataService->getQualityCorrection();
        $NonConformitysSelect = $this->SelectDataService->getQualityNonConformity();

        $QualityActions = QualityAction::orderBy('id')->paginate(10);
        $QualityCauses = QualityCause::All();
        $QualityFailures = QualityFailure::All();
        $QualityCorrections = QualityCorrection::All();
        $QualityDerogations = QualityDerogation::orderBy('id')->paginate(10);
        $QualityNonConformitys = QualityNonConformity::orderBy('id')->paginate(10);
        $QualityControlDevices = QualityControlDevice::orderBy('id')->paginate(10);
        $QualityDerogations = QualityDerogation::orderBy('id')->paginate(10);

        $LastAction =  DB::table('quality_actions')->orderBy('id', 'desc')->first();
        $LastNonConformity =  DB::table('quality_non_conformities')->orderBy('id', 'desc')->first();
        $LastDerogation =  DB::table('quality_derogations')->orderBy('id', 'desc')->first();
        
        //*************bar**********************

        // Nombre total d'entrées pour chaque catégorie
        $totalDerogations = QualityDerogation::count();
        $totalDerogationsOpen = QualityDerogation::where('statu',1)->count();
        $totalNonConformities = QualityNonConformity::count();
        $totalNonConformitiesOpen = QualityNonConformity::where('statu',1)->count();
        $totalActions = QualityAction::count();
        $totalActionsOpen = QualityAction::where('statu',1)->count();

        // Nombre d'entrées internes pour chaque catégorie
        $internalDerogations = QualityDerogation::where('type', 1)->count();
        $internalNonConformities = QualityNonConformity::where('type', 1)->count();
        $internalActions = QualityAction::where('type', 1)->count();

        // Calculer le taux d'interne en pourcentage pour chaque catégorie
        $internalDerogationRate = ($totalDerogations > 0) ? ($internalDerogations / $totalDerogations) * 100 : 0;
        $internalNonConformityRate = ($totalNonConformities > 0) ? ($internalNonConformities / $totalNonConformities) * 100 : 0;
        $internalActionRate = ($totalActions > 0) ? ($internalActions / $totalActions) * 100 : 0;

        // Taux d'externe en pourcentage
        $externalDerogationRate = 100 - $internalDerogationRate;
        $externalNonConformityRate = 100 - $internalNonConformityRate;
        $externalActionRate = 100 - $internalActionRate;

        //*****************bar **********************/
            // Requête pour obtenir les 10 plus grands générateurs de non-conformités
            $topGenerators = QualityNonConformity::select('companie_id', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('companie_id')
            ->groupBy('companie_id')
            ->orderByDesc('companie_id')
            ->orderByDesc('count')
            ->limit(7)
            ->get();

            // Récupération des noms des entreprises associées aux identifiants
            $companies = Companies::orderByDesc('id')
                                    ->whereIn('id', $topGenerators->pluck('companie_id'))
                                    ->pluck('label', 'id');

            // Tableau de couleurs par défaut
            $defaultColors = [
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 205, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(201, 203, 207, 0.2)'
            ];

            // Préparation des données pour le graphique
            $chartData = [
            'labels' => $companies->values()->all(),
            'datasets' => [
                [
                    'label' => __('general_content.non_conformities_trans_key'),
                    'data' => $topGenerators->pluck('count')->all(),
                    'backgroundColor' => $defaultColors,
                    'beginAtZero' => true,
                ],
            ],
            ];

        //********************radar********************************
        $allStatus = [1, 2, 3, 4];

        $derogationStatusCounts = QualityDerogation::groupBy('statu')->select('statu', DB::raw('count(*) as count'))->orderby('statu')->pluck('count', 'statu')->toArray();
        $nonConformityStatusCounts = QualityNonConformity::groupBy('statu')->select('statu', DB::raw('count(*) as count'))->orderby('statu')->pluck('count', 'statu')->toArray();
        $actionStatusCounts = QualityAction::groupBy('statu')->select('statu', DB::raw('count(*) as count'))->orderby('statu')->pluck('count', 'statu')->toArray();

        foreach ($allStatus as $status) {
            if (!isset($derogationStatusCounts[$status])) {
                $derogationStatusCounts[$status] = 0;
            }
            if (!isset($nonConformityStatusCounts[$status])) {
                $nonConformityStatusCounts[$status] = 0;
            }
            if (!isset($actionStatusCounts[$status])) {
                $actionStatusCounts[$status] = 0;
            }
        }

        ksort($derogationStatusCounts);
        ksort($nonConformityStatusCounts);
        ksort($actionStatusCounts);
        
        return view('quality/quality-index', [
            'QualityActions' => $QualityActions,
            'LastAction' => $LastAction,
            'QualityDerogations' => $QualityDerogations, 
            'LastDerogation' =>  $LastDerogation,
            'QualityCauses' => $QualityCauses,
            'CausesSelect' =>  $CausesSelect,
            'QualityFailures' => $QualityFailures,
            'FailuresSelect' =>  $FailuresSelect,
            'QualityCorrections' => $QualityCorrections,
            'CorrectionsSelect' => $CorrectionsSelect ,
            'QualityDerogations' => $QualityDerogations,
            'QualityNonConformitys' => $QualityNonConformitys,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'LastNonConformity' => $LastNonConformity,
            'QualityControlDevices' => $QualityControlDevices,
            'userSelect' => $userSelect,
            'ServicesSelect' =>  $ServicesSelect,
            'CompaniesSelect' =>  $CompaniesSelect,
            'derogationStatusCounts' => $derogationStatusCounts,
            'nonConformityStatusCounts' => $nonConformityStatusCounts,
            'actionStatusCounts' => $actionStatusCounts, 
            'internalDerogationRate'=> $internalDerogationRate,
            'externalDerogationRate'=> $externalDerogationRate,
            'internalNonConformityRate'=> $internalNonConformityRate,
            'externalNonConformityRate'=> $externalNonConformityRate,
            'internalActionRate'=> $internalActionRate,
            'externalActionRate'=> $externalActionRate, 
            'chartData'=> $chartData, 
            'totalDerogationsOpen'=> $totalDerogationsOpen,
            'totalNonConformitiesOpen'=> $totalNonConformitiesOpen,
            'totalActionsOpen'=> $totalActionsOpen,
        ]);
    }
}
