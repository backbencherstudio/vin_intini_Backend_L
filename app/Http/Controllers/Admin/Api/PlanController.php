<?php

namespace App\Http\Controllers\Admin\Api;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct(private StripeService $stripe) {}

    public function index(): JsonResponse
    {
        $plans = Plan::latest()->get();

        return response()->json(['success' => true, 'data' => $plans], 200);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $plan], 200);
    }

    public function features(): JsonResponse
    {
        $features = collect(PlanFeature::cases())->map(fn(PlanFeature $f) => [
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
        ]);

        $stripeProduct = $this->stripe->createProduct(
            $validated['name'],
            $validated['short_description'] ?? null,
            $validated['features'],
        );

        $unitAmount = (int) (round((float) $validated['billing_rate'], 2) * 100);
        $stripePrice = $this->stripe->createPrice(
            $unitAmount,
            'usd',
            $validated['billing_cycle'] === 'monthly' ? 'month' : 'year',
            $stripeProduct->id,
        );

        $plan = Plan::create([
            'name' => $validated['name'],
            'short_description' => $validated['short_description'],
            'billing_rate' => $validated['billing_rate'],
            'billing_cycle' => $validated['billing_cycle'],
            'discount_percent' => $validated['discount_percent'],
            'discount_duration' => $validated['discount_duration'],
            'badge_color' => $validated['badge_color'],
            'status' => $validated['status'],
            'features' => $validated['features'],
            'stripe_product_id' => $stripeProduct->id,
            'stripe_price_id' => $stripePrice->id,
        ]);

        return response()->json(['success' => true, 'data' => $plan], 201);
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
        ]);

        $data = [];

        if (array_key_exists('name', $validated) || array_key_exists('short_description', $validated)) {
            $stripeData = [];
            if (array_key_exists('name', $validated)) {
                $stripeData['name'] = $validated['name'];
            }
            if (array_key_exists('short_description', $validated)) {
                $stripeData['description'] = $validated['short_description'];
            }
            $this->stripe->updateProduct($plan->stripe_product_id, $stripeData);
        }

        if (
            (array_key_exists('billing_rate', $validated) && $validated['billing_rate'] != $plan->billing_rate)
            || (array_key_exists('billing_cycle', $validated) && $validated['billing_cycle'] !== $plan->billing_cycle)
        ) {
            $this->stripe->archivePrice($plan->stripe_price_id);

            $newRate = $validated['billing_rate'] ?? $plan->billing_rate;
            $newCycle = $validated['billing_cycle'] ?? $plan->billing_cycle;

            $unitAmount = (int) (round((float) $newRate, 2) * 100);
            $newPrice = $this->stripe->createPrice(
                $unitAmount,
                'usd',
                $newCycle === 'monthly' ? 'month' : 'year',
                $plan->stripe_product_id,
            );

            $data['stripe_price_id'] = $newPrice->id;
        }

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
        ];

        foreach ($fillableFields as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
            }
        }

        $plan->update($data);

        return response()->json(['success' => true, 'data' => $plan], 200);
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
