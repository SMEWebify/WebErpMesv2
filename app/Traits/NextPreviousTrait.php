<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait NextPreviousTrait {
    public function getNextPrevious(Model $model, $id) {
        $previousId = $model::where('id', '<', $id)->orderBy('id', 'desc')->first();
        $nextId = $model::where('id', '>', $id)->orderBy('id', 'asc')->first();
        $routeName = str_replace('_', '.', $model->getTable());
        $previousUrl = $previousId ? route("{$routeName}.show", ['id' => $previousId]) : null;
        $nextUrl = $nextId ? route("{$routeName}.show", ['id' => $nextId]) : null;

        return [$previousUrl, $nextUrl];
    }
}
