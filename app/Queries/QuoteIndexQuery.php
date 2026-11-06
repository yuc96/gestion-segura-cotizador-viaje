<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Quote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class QuoteIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
        $perPage = (int) ($filters['per_page'] ?? 10);

        return Quote::query()
            ->with('insured')
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status']),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query->where('destination_name', 'like', $like)
                        ->orWhere('destination_code', 'like', $like)
                        ->orWhereHas('insured', function ($insured) use ($like): void {
                            $insured->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('identification', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->when(
                filled($filters['departure_from'] ?? null),
                fn ($query) => $query->whereDate('departure_date', '>=', $filters['departure_from']),
            )
            ->when(
                filled($filters['departure_to'] ?? null),
                fn ($query) => $query->whereDate('departure_date', '<=', $filters['departure_to']),
            )
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
