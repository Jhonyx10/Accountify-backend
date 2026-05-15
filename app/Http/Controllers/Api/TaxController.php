<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tax::where('created_by', $request->user()->creatorId());

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('type', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $taxes = $query->with('creator')->paginate($perPage);

        return TaxResource::collection($taxes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|in:Inclusive,Exclusive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tax = Tax::create([
            'name' => $request->name,
            'rate' => $request->rate,
            'type' => $request->type,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new TaxResource($tax))
            ->additional(['message' => 'Tax created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $tax = Tax::where('created_by', $request->user()->creatorId())
            ->findOrFail($id);

        return new TaxResource($tax);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tax = Tax::where('created_by', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'rate' => 'sometimes|required|numeric|min:0|max:100',
            'type' => 'sometimes|required|string|in:Inclusive,Exclusive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tax->update($request->only(['name', 'rate', 'type']));

        return (new TaxResource($tax))
            ->additional(['message' => 'Tax updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $tax = Tax::where('created_by', $request->user()->creatorId())->findOrFail($id);
        $tax->delete();

        return response()->json([
            'message' => 'Tax deleted successfully'
        ]);
    }
}
