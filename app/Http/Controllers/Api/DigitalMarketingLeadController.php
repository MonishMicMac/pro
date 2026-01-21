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
    // --- 1. MANUAL VALIDATION ---

    // Validate Name (Required)
    if (empty($request->name)) {
        return response()->json([
            'status' => 'error',
            'message' => 'The name field is required.'
        ], 422);
    }

    // Validate Phone Number (Required)
    if (empty($request->phone_number)) {
        return response()->json([
            'status' => 'error',
            'message' => 'The phone number field is required.'
        ], 422);
    }

    // Validate Phone Number Format (10 digits, starting with 6-9)
    // pattern: ^ starts with, [6-9] first digit, [0-9]{9} next 9 digits, $ end
    if (!preg_match('/^[6-9][0-9]{9}$/', $request->phone_number)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid phone number. It must be a 10-digit mobile number starting with 6, 7, 8, or 9.'
        ], 422);
    }

    // --- 2. DUPLICATE CHECK LOGIC ---

    $lead = DigitalMarketingLead::where('phone_number', $request->phone_number)->first();

    if ($lead) {
        // Update existing lead
        $lead->update(array_merge($request->all(), [
            'enquiry_count' => $lead->enquiry_count + 1,
            'notes' => $lead->notes . "\n[Update]: Enquired again on " . date('d-m-Y H:i'),
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Duplicate found: Enquiry count updated to ' . $lead->enquiry_count,
            'enquiry_status' => 'repeated',
            'data' => $lead
        ], 200);
    }

    // --- 3. CREATE NEW LEAD ---
    
    $newLead = DigitalMarketingLead::create(array_merge($request->all(), [
        'enquiry_count' => 1
    ]));

    return response()->json([
        'status' => 'success',
        'message' => 'New lead recorded successfully',
        'enquiry_status' => 'new',
        'data' => $newLead
    ], 200);
}
}