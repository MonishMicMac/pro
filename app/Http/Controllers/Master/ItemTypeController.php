<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ItemType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ItemTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = ItemType::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.item_types.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:item_types,name,NULL,id,action,0'
        ]);

        ItemType::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Item Type added']);
    }

    public function edit(ItemType $item_type)
    {
        return response()->json($item_type);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:item_types,name,' . $id . ',id,action,0'
        ]);

        ItemType::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        ItemType::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Item Type deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        ItemType::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected item types deactivated'
        ]);
    }
}
