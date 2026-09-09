<?php

namespace App\Http\Controllers\Admin\Api;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Jobs\ProvisionPlan;
use App\Models\Plan;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct(
        private StripeService $stripe,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $billingCycle = $request->input('billing_cycle');

        $plans = Plan::query()
            ->when($billingCycle, function ($query, $billingCycle) {
                $query->where('billing_cycle', $billingCycle);
            })
            ->withCount('subscriptions')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => PlanResource::collection($plans)], 200);
    }

    public function show(Plan $plan): JsonResponse
    {
        $plan->loadCount('subscriptions');

        return response()->json(['success' => true, 'data' => new PlanResource($plan)], 200);
    }

    public function features(): JsonResponse
    {
        $features = collect(PlanFeature::cases())->map(fn (PlanFeature $f) => [
            'value' => $f->value,
            'label' => PlanFeature::labels()[$f->value],
        ]);

        return response()->json(['success' => true, 'data' => $features], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'billing_rate' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_duration' => ['nullable', 'date'],
            'badge_color' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'features' => ['required', 'array'],
            'features.*' => ['required', Rule::in(PlanFeature::values())],
            'revenuecat_store_identifier_ios' => ['nullable', 'string', 'max:255'],
            'revenuecat_store_identifier_android' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = DB::transaction(fn () => Plan::create([
            'name' => $validated['name'],
            'short_description' => $validated['short_description'] ?? null,
            'billing_rate' => $validated['billing_rate'],
            'billing_cycle' => $validated['billing_cycle'],
            'discount_percent' => $validated['discount_percent'] ?? null,
            'discount_duration' => $validated['discount_duration'] ?? null,
            'badge_color' => $validated['badge_color'] ?? null,
            'status' => $validated['status'],
            'features' => $validated['features'],
            'revenuecat_store_identifier_ios' => $validated['revenuecat_store_identifier_ios'] ?? null,
            'revenuecat_store_identifier_android' => $validated['revenuecat_store_identifier_android'] ?? null,
        ]));

        ProvisionPlan::dispatch($plan);

        return response()->json(['success' => true, 'data' => new PlanResource($plan->fresh())], 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'billing_rate' => ['sometimes', 'required', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'required', Rule::in(['monthly', 'yearly'])],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_duration' => ['nullable', 'date'],
            'badge_color' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'features' => ['sometimes', 'required', 'array'],
            'features.*' => ['required_with:features', Rule::in(PlanFeature::values())],
            'revenuecat_store_identifier_ios' => ['sometimes', 'nullable', 'string', 'max:255'],
            'revenuecat_store_identifier_android' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $data = [];

        $updateProduct = false;
        $productData = [];

        if (array_key_exists('name', $validated) || array_key_exists('short_description', $validated)) {
            $updateProduct = true;
            if (array_key_exists('name', $validated)) {
                $productData['name'] = $validated['name'];
            }
            if (array_key_exists('short_description', $validated)) {
                $productData['description'] = $validated['short_description'];
            }
        }

        $recreatePrice = (array_key_exists('billing_rate', $validated) && $validated['billing_rate'] != $plan->billing_rate)
            || (array_key_exists('billing_cycle', $validated) && $validated['billing_cycle'] !== $plan->billing_cycle);

        $fillableFields = [
            'name',
            'short_description',
            'billing_rate',
            'billing_cycle',
            'discount_percent',
            'discount_duration',
            'badge_color',
            'status',
            'features',
            'revenuecat_store_identifier_ios',
            'revenuecat_store_identifier_android',
        ];

        foreach ($fillableFields as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
            }
        }

        $plan->update($data);

        ProvisionPlan::dispatch($plan, $updateProduct, $productData, $recreatePrice);

        return response()->json(['success' => true, 'data' => new PlanResource($plan->fresh())], 200);
    }

    public function toggleStatus(Plan $plan): JsonResponse
    {
        $plan->status = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->save();

        $this->stripe->updateProduct($plan->stripe_product_id, [
            'active' => $plan->status === 'active',
        ]);

        return response()->json(['success' => true, 'data' => $plan], 200);
    }
}
