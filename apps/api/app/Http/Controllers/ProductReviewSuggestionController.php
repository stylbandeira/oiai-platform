<?php

namespace App\Http\Controllers;

use App\Models\CompanyProductCode;
use App\Models\ProductReviewSuggestion;
use App\Services\Product\ProductNormalizationDecisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReviewSuggestionController extends Controller
{
    public function review(
        Request $request,
        ProductReviewSuggestion $suggestion,
        ProductNormalizationDecisionService $decisions,
    ) {
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        return DB::transaction(function () use ($suggestion, $data, $request, $decisions) {
            $suggestion = ProductReviewSuggestion::query()->lockForUpdate()->findOrFail($suggestion->id);
            if ($suggestion->status !== 'pending') {
                return response()->json(['message' => 'Sugestão já revisada.'], 409);
            }

            if ($data['decision'] === 'approved') {
                if ($suggestion->type === 'equivalence') {
                    if (! $suggestion->suggestedProduct || ! $suggestion->suggestedProduct->validated) {
                        return response()->json(['message' => 'O produto sugerido precisa estar validado.'], 422);
                    }

                    // Historical invoice rows keep their original product ID;
                    // future invoices resolve the merchant code to the approved one.
                    CompanyProductCode::query()
                        ->where('product_id', $suggestion->product_id)
                        ->update(['product_id' => $suggestion->suggested_product_id]);
                    $suggestion->product->update(['validated' => false]);
                    ProductReviewSuggestion::query()
                        ->where('product_id', $suggestion->product_id)
                        ->where('status', 'pending')
                        ->where('id', '!=', $suggestion->id)
                        ->update([
                            'status' => 'rejected',
                            'reviewed_by' => $request->user()->id,
                            'reviewed_at' => now(),
                        ]);
                } else {
                    // Only an approved proposal enters the reusable normalization
                    // decisions; pending/rejected proposals never train matching.
                    $decisions->recordManualDecision(
                        product: $suggestion->product,
                        selectedValues: (array) $suggestion->suggested_values,
                        reviewedBy: (int) $request->user()->id,
                        algorithmVersion: 2,
                        trustedReviewer: true,
                    );
                    $suggestion->product->update(['validated' => true, 'validated_by' => $request->user()->id]);
                }
            }

            $suggestion->update([
                'status' => $data['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return response()->json(['status' => $suggestion->status]);
        });
    }
}
