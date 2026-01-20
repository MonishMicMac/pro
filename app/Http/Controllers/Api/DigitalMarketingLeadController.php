<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalMarketingLead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DigitalMarketingLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 1. Validation Logic
        $validatedData = $request->validate([
            'name'           => 'required|string|max:255',
            'phone_number'   => 'required|string|max:20',
            'email'          => 'nullable|email',
            'date'           => 'nullable|date',
            'city'           => 'nullable|string',
            'color_preference' => 'nullable|string',
            // Add other fields as optional
        ]);

        // 2. Check for Duplicate Phone Number
        $lead = DigitalMarketingLead::where('phone_number', $request->phone_number)->first();

        if ($lead) {
            // Logic for "Enquired Again"
            $lead->update(array_merge($request->all(), [
                'enquiry_count' => $lead->enquiry_count + 1,
                'notes' => $lead->notes . "\n[Update]: Enquired again on " . now()->format('d-m-Y H:i'),
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Duplicate found: Enquiry count updated to ' . $lead->enquiry_count,
                'enquiry_status' => 'repeated',
                'data' => $lead
            ], 200);
        }

        // 3. Create New Lead if not a duplicate
        $newLead = DigitalMarketingLead::create(array_merge($request->all(), [
            'enquiry_count' => 1
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'New lead recorded successfully',
            'enquiry_status' => 'new',
            'data' => $newLead
        ], 201);
    }
}