<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccount::with(['accountType', 'accountSubType', 'parentAccount', 'creator', 'journalItems']);

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('sub_type')) {
            $query->where('sub_type', $request->sub_type);
        }

        if ($request->has('is_enabled')) {
            $query->where('is_enabled', $request->is_enabled);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        if ($perPage == -1) {
            $accounts = $query->orderBy('code')->get();
            return ChartOfAccountResource::collection($accounts);
        } else {
            $accounts = $query->orderBy('code')->paginate($perPage);
            return ChartOfAccountResource::collection($accounts);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'type' => 'required|integer',
            'sub_type' => 'nullable|integer',
            'is_enabled' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $account = ChartOfAccount::create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'sub_type' => $request->sub_type ?? 0,
            'parent' => $request->parent ?? 0,
            'is_enabled' => $request->is_enabled ?? 1,
            'description' => $request->description,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new ChartOfAccountResource($account->load(['accountType', 'accountSubType'])))
            ->additional(['message' => 'Chart of Account created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccount::with(['accountType', 'accountSubType', 'parentAccount', 'creator']);

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $account = $query->findOrFail($id);

        return new ChartOfAccountResource($account);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccount::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $account = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Make sure we can set parent
        $data = $request->except(['created_by']);
        if ($request->has('parent')) {
            $data['parent'] = $request->parent;
        }

        $account->update($data);

        return (new ChartOfAccountResource($account->load(['accountType', 'accountSubType'])))
            ->additional(['message' => 'Chart of Account updated successfully']);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccount::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $account = $query->findOrFail($id);

        // check if this account has children
        if ($account->childrenAccounts()->count() > 0) {
            return response()->json(['message' => 'Cannot delete account with sub-accounts.'], 422);
        }

        // check if account is used in journal items
        if ($account->journalItems()->count() > 0) {
            return response()->json(['message' => 'Cannot delete account with existing transactions.'], 422);
        }

        $account->delete();

        return response()->json(['message' => 'Chart of Account deleted successfully']);
    }
}
