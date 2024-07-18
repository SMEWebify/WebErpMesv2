<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        
        $Roles = Role::all();
        $Permissions = Permission::all();

        return view('admin/factory-roles-permissions', [
            'Roles' => $Roles,
            'Permissions' => $Permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate(['name' => ['required','unique:roles','min:3']]);
        $role = Role::create($validated);

        /*if ($request->has('permissions')) {
            $role->givePermissionTo(collect($request->permissions)->pluck('id')->toArray());
        }*/

        return to_route('admin.roles.permissions')->with('success', 'Role Created successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\id  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function update(Request $request,$id)
    {
        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();
        
        $role->syncPermissions($request->permission);
        
        return to_route('admin.roles.permissions')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Contracts\View\View
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return to_route('admin.roles.permissions')->with('success', 'Role deleted successfully.');
    }

     /**
     * Store the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function RolePemissionStore(Request $request){

        $role= Role::findById($request->role_id);
        $role->syncPermissions($request->permission);

        
        return to_route('admin.roles.permissions')->with('success', 'Permissions added in Role successfully.');
        /*$data = array();
        $permissions = ;
        foreach($permissions as $key => $item){
            $data['role_id'] = $request->role_id;
            $data['permission_id'] = $item;

            DB::table('role_has_permissions')->insert($data);
        }*/
    }
}
