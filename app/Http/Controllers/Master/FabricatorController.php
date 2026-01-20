<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Fabricator;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\Area;
use App\Models\Pincodes;
use App\Models\Brand;

class FabricatorController extends Controller
{
    use AuthorizesRequests;

    public function getDistricts($state_id)
    {
        return District::where('state_id', $state_id)->get();
    }

    public function getCities($district_id)
    {
        return City::where('district_id', $district_id)->get();
    }

    public function getAreas($city_id)
    {
        return Area::where('city_id', $city_id)->get();
    }

    public function getPincodes($area_id)
    {
        return Pincodes::where('area_id', $area_id)->get();
    }
    /**
     * List
     */
    // public function index(Request $request)
    // {
    //     $this->authorize('fabricators.view');

    //     if ($request->ajax()) {

    //         $query = Fabricator::with(['state', 'district', 'city', 'pincode']);

    //         if ($request->state) {
    //             $query->where('state_id', $request->state);
    //         }

    //         if ($request->district) {
    //             $query->where('district_id', $request->district);
    //         }

    //         return DataTables::of($query)
    //             ->addColumn('state', fn($r) => $r->state->name ?? '-')
    //             ->addColumn('district', fn($r) => $r->district->district_name ?? '-')
    //             ->addColumn('city', fn($r) => $r->city->city_name ?? '-')
    //             ->addColumn('pincode', fn($r) => $r->pincode->pincode ?? '-')
    //             ->make(true);
    //     }

    //     return view('masters.fabricators.index');
    // }


    public function index(Request $request)
    {
        $this->authorize('fabricators.view');

        if ($request->ajax()) {

            $query = Fabricator::with(['state', 'district', 'city', 'pincode', 'creator'])
                ->where('action', '0');

            if ($request->state) {
                $query->where('state_id', $request->state);
            }

            if ($request->district) {
                $query->where('district_id', $request->district);
            }

            return DataTables::of($query)
                ->addColumn('state', fn($r) => $r->state->name ?? '-')
                ->addColumn('district', fn($r) => $r->district->district_name ?? '-')
                ->addColumn('city', fn($r) => $r->city->city_name ?? '-')
                ->addColumn('pincode', fn($r) => $r->pincode->pincode ?? '-')
                ->addColumn('brands', fn($r) => $r->brands->pluck('name')->implode(', '))
                ->addColumn('created_by', fn($r) => $r->creator->name ?? '-')
                ->make(true);
        }


        $states = State::where('action', '0')->get();
        $brands = Brand::where('action', '0')->get();

        return view('masters.fabricators.index', compact('states', 'brands'));
    }

    /**
     * Store
     */
    public function store(Request $request)
    {
        $this->authorize('fabricators.create');

        $request->validate([
            'shop_name'     => 'required',
            'mobile'   => 'required|unique:fabricators',
            'password' => 'required|min:6',
            'address'  => 'required',
        ]);

        $fabricator = Fabricator::create([
            'shop_name' => $request->shop_name,
            'password' => $request->password,

            // TEXT VALUES
            'division' => $request->division,
            'category' => $request->category,
            'segment' => $request->segment,
            'sub_segment' => $request->sub_segment,

            // LOCATION
            'state_id' => $request->state_id,
            'district_id' => $request->district_id,
            'city_id' => $request->city_id,
            'area_id' => $request->area_id,
            'pincode_id' => $request->pincode_id,

            'address' => $request->address,
            'shipping_address' => $request->shipping_address,
            'billing_address' => $request->billing_address,

            // BANK
            'bank_name' => $request->bank_name,
            'ifsc_code' => $request->ifsc_code,
            'account_number' => $request->account_number,
            'branch' => $request->branch,
            'payment_credit_terms' => $request->credit_terms,
            'credit_limit' => $request->credit_limit,

            // CONTACT
            'contact_person' => $request->contact_person,
            'sales_person' => $request->sales_person,
            'contact_type' => $request->contact_type,
            'email' => $request->email,
            'mobile' => $request->mobile,

            'gst' => $request->gst,
            'created_by' => auth()->id(),
        ]);


        if ($request->brands) {
            $fabricator->brands()->sync($request->brands);
        }

        return back()->with('success', 'Fabricator created successfully');
    }

    /**
     * Edit
     */
    public function edit($id)
    {
        $this->authorize('fabricators.edit');

        return Fabricator::with(['state', 'district', 'city', 'area', 'pincode', 'brands'])->findOrFail($id);
    }

    /**
     * Update
     */
    public function update(Request $request, $id)
    {
        $this->authorize('fabricators.edit');

        $fabricator = Fabricator::findOrFail($id);

        $request->validate([
            'shop_name'   => 'required',
            'mobile' => 'required|unique:fabricators,mobile,' . $id,
        ]);

        $data = $request->except('password');

        if ($request->password) {
            $data['password'] = $request->password; // auto hash via model
        }

        $fabricator->update($data);
        if ($request->brands) {
            $fabricator->brands()->sync($request->brands);
        }


        return redirect()->route('masters.fabricators.index')
            ->with('success', 'Updated successfully');
    }

    /**
     * Delete
     */
    public function destroy($id)
    {
        Fabricator::findOrFail($id)->delete();
        return back()->with('success', 'Deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('fabricators.delete');
        if (!$request->ids) {
            return response()->json(['error' => 'No records selected'], 400);
        }

        Fabricator::whereIn('id', $request->ids)
            ->update(['action' => '1']);


        return response()->json(['success' => true]);
    }

    /**
     * Status toggle
     */
    public function bulkStatus(Request $r)
    {
        if (!$r->has('ids') || empty($r->ids)) {
            return response()->json(['error' => 'No records selected'], 400);
        }

        if ($r->status == 0) { // APPROVE
            Fabricator::whereIn('id', $r->ids)->update([
                'status' => '0',
                'approved_date' => now()
            ]);
        } else { // DECLINE
            Fabricator::whereIn('id', $r->ids)->update([
                'status' => '1',
                'remarks' => $r->remarks,
            ]);
        }

        return response()->json(true);
    }

    public function show($id)
    {
        return Fabricator::with(['state', 'district', 'city', 'pincode', 'brands'])
            ->findOrFail($id);
    }
}
