@extends('adminlte::page')

@section('title', __('general_content.gantt_trans_key'))

@section('content_header')
	<div class="row mb-2">
		<div class="col-sm-6">
			<h1>{{__('general_content.gantt_trans_key') }}</h1>
		</div>
		<div class="col-sm-6">
			<!-- Button Modal -->
			<button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationDate">
				{{__('general_content.gantt_info_1_trans_key') }} ({{ $countTaskNullDate }})
			</button>
		</div>
	</div>
@stop

@section('right-sidebar')

@section('content')
	@livewire('task-calculation-date')
    <div id="gantt_here" style='width:100%; height:800px;'></div>
@stop

@section('css')
<style type="text/css">
    .weekend{
        background: #a5cfeb !important;
    }
	.gantt_task_cell.no_work_hour {
		background-color: #a5cfeb;
	}
	.gantt_task_row.gantt_selected .gantt_task_cell.no_work_hour {
		background-color: #F8EC9C;
	}
</style>
@stop


@section('js')
<script type="text/javascript">
	gantt.config.date_format = "%Y-%m-%d %H:%i:%s";
	gantt.config.min_column_width = 20;
	gantt.config.scale_height = 90;
	gantt.config.fit_tasks = true; 
	gantt.config.work_time = true;
	gantt.config.skip_off_time = true;
	gantt.config.duration_unit = "hour";
	gantt.config.duration_step = 1; 
	gantt.config.show_tasks_outside_timescale = true;
	gantt.config.readonly = true;

	gantt.setWorkTime({hours: [8, 12, 13, 17]});//global working hours. 8:00-12:00, 13:00-18:00
	gantt.setWorkTime({day: 0, hours: false});// make  day-off
	gantt.setWorkTime({day: 6, hours: false});// make  day-off

	// default columns definition
	gantt.config.columns = [
		{name:"text",       label:"Task name",  width:"*", tree:true, width:220 },
		{name:"start_date", label:"Start time", align:"center" },
		{name:"end_date",   label:"End date",   align:"center" },
		{name:"duration",   label:"Duration (h)",   align:"center", width:120 },
	];

	var daysStyle = function(date){
		var dateToStr = gantt.date.date_to_str("%D");
		if (dateToStr(date) == "Sun"||dateToStr(date) == "Sat")  return "weekend";
	
		return "";
	};

	gantt.config.scales = [
		{unit: "day", step: 1, format: "%l, %F %d", css:daysStyle},
		{unit: "hour", step: 1, format: "%G"}
	];

	gantt.templates.scale_cell_class = function(date){
		if(!gantt.isWorkTime({task:task, date: date})){
			return "weekend";
		}
		return "";
	};
	gantt.templates.timeline_cell_class = function(task,date){
		if(!gantt.isWorkTime({task:task, date: date})){
			return "weekend" ;
		}
		return "";
	};
	gantt.templates.timeline_cell_class = function (task, date) {
		if (!gantt.isWorkTime(date, 'hour')) {
			return ("no_work_hour");
		}
		return "";
	};
	
	gantt.init("gantt_here");
	gantt.load("/api/gantt/data");
</script> 
@stop



