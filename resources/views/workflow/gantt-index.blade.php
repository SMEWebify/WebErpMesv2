@extends('adminlte::page')

@section('title', __('general_content.gantt_trans_key'))

@section('content_header')
	<div class="row mb-2">
		<div class="col-sm-6">
			<h1>{{ __('general_content.gantt_trans_key') }}</h1>
		</div>
		<div class="col-sm-6">
			<!-- Button Modal -->
			<button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationDate">
				{{ __('general_content.gantt_info_1_trans_key') }} ({{ $countTaskNullDate }})
			</button>
		</div>
	</div>
@stop

@section('right-sidebar')

@section('content')
	@livewire('task-calculation-date')
	<x-adminlte-card theme="lime" theme-mode="outline">
		@include('include.alert-result')
		<form action="{{ route('production.gantt') }}" method="GET">
			<div class="row">
				<div class="form-group col-4">
					<x-adminlte-select2 name="order_line_id" id="order_line_id" label="{{ __('general_content.select_order_line_trans_key') }}" label-class="text-secondary"
						igroup-size="s" data-placeholder="{{ __('general_content.select_order_line_trans_key') }}">
						<x-slot name="prependSlot">
							<div class="input-group-text bg-gradient-secondary">
								<i class="fas fa-list"></i>
							</div>
						</x-slot>
						<option value="null">{{ __('general_content.select_order_line_trans_key') }}</option>
						@foreach ($OrderLineList as $item)
						<option value="{{ $item->id }}">#{{ $item->id }} {{ $item->label }} - {{ $item->Order->code }}</option>
						@endforeach
					</x-adminlte-select2>
				</div>
				<div class="form-group col-2">
					<x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
				</div>
			</div>
		</form>
	</x-adminlte-card>
	<x-adminlte-card theme="lime" theme-mode="outline">
        <div class="row">
            <div class="col-12">
                <div id="chart_div"></div>
            </div>
        </div>
    
	</x-adminlte-card>
@stop

@section('css')
<style type="text/css">
    .weekend {
        background: #a5cfeb !important;
    }
</style>
@stop

@section('js')
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
	google.charts.load("current", { packages: ["gantt"] });
	google.charts.setOnLoadCallback(drawChart);

	function toMilliseconds(minutes) {
		return minutes * 60 * 1000;
	}

	function hoursToMilliseconds(hours) {
		return hours * 60 * 60 * 1000;
	}

	function drawChart() {
		// Remplacez 'your_order_lines_id' par la variable PHP correspondant à l'ID de la ligne de commande
		let orderLinesId = {{ $orderLineId }};

		fetch(`/api/gantt/order/${orderLinesId}`)
			.then(response => response.json())
			.then(data => {
				const tasks = data.data.map(task => [
					task.id.toString(),  // Task ID
					task.label,          // Task Name
					task.resource,       // Resource
					task.start_date ? new Date(task.start_date.date) : null,  // Start Date
					task.end_date ? new Date(task.end_date.date) : null,      // End Date
					hoursToMilliseconds(task.duration),  // Duration in milliseconds
					task.progress,       // Percent Complete
					task.dependencies ? task.dependencies.toString() : null    // Dependencies
				]);

				var otherData = new google.visualization.DataTable();
				otherData.addColumn("string", "Task ID");
				otherData.addColumn("string", "Task Name");
				otherData.addColumn("string", "Resource");
				otherData.addColumn("date", "Start");
				otherData.addColumn("date", "End");
				otherData.addColumn("number", "Duration");
				otherData.addColumn("number", "Percent Complete");
				otherData.addColumn("string", "Dependencies");

				otherData.addRows(tasks);

				var options = {
						height: 600, 
						gantt: {
							defaultStartDate: new Date(),
							percentEnabled: true,
							timeline: {
								showRowLabels: true,        // Affiche les étiquettes des lignes
								showBarLabels: true,        // Affiche les étiquettes des barres de tâches
								colorByRowLabel: true,      // Colore les lignes par étiquette
								groupByRowLabel: false,     // Ne regroupe pas les tâches par étiquette
								weekStart: 1,               // Commence la semaine le lundi
								// Format d'affichage en fonction du type d'échelle choisi
								dayFormat: 'd MMM',         // Format pour les jours
								weekFormat: 'MMM d',        // Format pour les semaines
								monthFormat: 'MMM yyyy',    // Format pour les mois
								quarterFormat: '[Q]Q yyyy', // Format pour les trimestres
								yearFormat: 'yyyy',         // Format pour les années
							}
						},
					};

				var chart = new google.visualization.Gantt(
					document.getElementById("chart_div")
				);

				chart.draw(otherData, options);
			});
	}
</script>

@stop
