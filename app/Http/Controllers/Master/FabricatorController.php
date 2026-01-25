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
use App\Models\Zone;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


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

    public function getBdmByZone($zone_id)
    {
        return User::where('action', '0')
            ->whereIn('id', function ($q) {
                $q->select('model_id')
                    ->from('model_has_roles')
                    ->where('model_type', 'App\Models\User')
                    ->where('role_id', 4); // BDM ROLE ID
            })
            ->where('zone_id', $zone_id)
            ->select('id', 'name')
            ->get();
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

            $query = Fabricator::with(['state', 'district', 'city', 'pincode', 'creator', 'zone', 'salesPerson'])
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
                ->addColumn('zone', fn($r) => $r->zone->name ?? '-')
                ->addColumn('sales_person', fn($r) => $r->salesPerson->name ?? '-')
                ->make(true);
        }


        $states = State::where('action', '0')->get();
        $brands = Brand::where('action', '0')->get();
        $zones  = Zone::where('action', '0')->get();
        $bdm  = User::where('action', '0')->get();

        return view('masters.fabricators.index', compact('states', 'brands', 'zones', 'bdm'));
    }

    /**
     * Store
     */
    public function store(Request $request)
    {
        $this->authorize('fabricators.create');

        // Laravel automatically sends a 422 JSON response if this fails during AJAX
        $validator = Validator::make($request->all(), [
            'shop_name' => 'required|string|max:255',
            'zone_id'   => 'required',
            'mobile'    => 'required|unique:fabricators',
            'password'  => 'nullable|min:6',
            'address'   => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $isExisting = $request->has('is_existing') ? '1' : '0';
        $fabricator = Fabricator::create([
            'shop_name' => $request->shop_name,
            'password' => Hash::make($request->password),
            'noHashPassword' => $request->password,
            // ... (rest of your fields) ...
            'division' => $request->division,
            'category' => $request->category,
            'segment' => $request->segment,
            'sub_segment' => $request->sub_segment,
            'zone_id' => $request->zone_id,
            'state_id' => $request->state_id,
            'district_id' => $request->district_id,
            'city_id' => $request->city_id,
            'area_id' => $request->area_id,
            'pincode_id' => $request->pincode_id,
            'address' => $request->address,
            'shipping_address' => $request->shipping_address,
            'billing_address' => $request->billing_address,
            'bank_name' => $request->bank_name,
            'ifsc_code' => $request->ifsc_code,
            'account_number' => $request->account_number,
            'branch' => $request->branch,
            'payment_credit_terms' => $request->payment_credit_terms,
            'credit_limit' => $request->credit_limit,
            'contact_person' => $request->contact_person,
            'sales_person_id' => $request->sales_person_id,

            'contact_type' => $request->contact_type,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'gst' => $request->gst,
            'created_by' => auth()->id(),
            'is_existing' => $isExisting
        ]);

        if ($request->brands) {
            $fabricator->brands()->sync($request->brands);
        }

        // CHANGED: Return JSON instead of back()
        return response()->json(['success' => 'Fabricator created successfully']);
    }

    /**
     * Edit
     */
    public function edit($id)
    {
        $this->authorize('fabricators.edit');

        return Fabricator::with(['state', 'district', 'city', 'area', 'pincode', 'brands', 'salesPerson'])->findOrFail($id);
    }

    /**
     * Update
     */
    public function update(Request $request, $id)
    {
        $this->authorize('fabricators.edit');
        $fabricator = Fabricator::findOrFail($id);

        $request->validate([
            'shop_name' => 'required',
            'mobile' => 'required|unique:fabricators,mobile,' . $id,
        ]);

        // Normalize checkbox (CRITICAL)
        $isExisting = $request->has('is_existing') ? '1' : '0';

        // Prepare data
        $data = $request->except('password');
        $data['is_existing'] = $isExisting;

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $data['noHashPassword'] = $request->password;
        }

        // Single update call (safe)
        $fabricator->update($data);

        if ($request->brands) {
            $fabricator->brands()->sync($request->brands);
        }

        return response()->json(['success' => 'Updated successfully']);
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
        $fabricator = Fabricator::with([
            'state',
            'district',
            'city',
            'pincode',
            'brands',
            'requests.lead'
        ])->findOrFail($id);

        return view(
            'masters.fabricators.show',
            compact('fabricator')
        );
    }
}
