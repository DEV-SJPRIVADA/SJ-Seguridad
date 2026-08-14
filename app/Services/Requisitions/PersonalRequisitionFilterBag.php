<?php

namespace App\Services\Requisitions;

use App\Models\PersonalRequisition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PersonalRequisitionFilterBag
{
    public function __construct(
        public readonly string $search,
        public readonly string $status,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly ?int $clientId,
        public readonly ?int $cityId,
        public readonly bool $mineOnly,
        public readonly bool $includeClosed = false,
        public readonly bool $excludeClosedStatuses = false,
    ) {}

    public static function fromManageRequest(Request $request): self
    {
        $status = $request->string('status')->toString();
        $includeClosed = $request->boolean('include_closed');

        return new self(
            search: trim($request->string('q')->toString()),
            status: $status,
            dateFrom: self::normalizeDate($request->input('date_from')),
            dateTo: self::normalizeDate($request->input('date_to')),
            clientId: null,
            cityId: null,
            mineOnly: false,
            includeClosed: $includeClosed,
            excludeClosedStatuses: $status === '' && ! $includeClosed,
        );
    }

    public static function fromTrackingRequest(Request $request): self
    {
        $clientId = $request->integer('client_id');
        $cityId = $request->integer('city_id');

        return new self(
            search: trim($request->string('q')->toString()),
            status: $request->string('status')->toString(),
            dateFrom: self::normalizeDate($request->input('date_from')),
            dateTo: self::normalizeDate($request->input('date_to')),
            clientId: $clientId > 0 ? $clientId : null,
            cityId: $cityId > 0 ? $cityId : null,
            mineOnly: $request->boolean('mine_only'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return [
            'q' => $this->search,
            'status' => $this->status,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'client_id' => $this->clientId,
            'city_id' => $this->cityId,
            'mine_only' => $this->mineOnly,
            'include_closed' => $this->includeClosed,
            'exclude_closed' => $this->excludeClosedStatuses,
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== ''
            || $this->dateFrom !== null
            || $this->dateTo !== null
            || $this->clientId !== null
            || $this->cityId !== null
            || $this->mineOnly;
    }

    /**
     * @param  Builder<PersonalRequisition>  $query
     */
    public function applyCommonFilters(Builder $query, bool $includeRequesterInSearch = false): void
    {
        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($inner) use ($search, $includeRequesterInSearch): void {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('leader_name', 'like', "%{$search}%")
                    ->orWhere('required_profile', 'like', "%{$search}%")
                    ->orWhere('replacement_name', 'like', "%{$search}%")
                    ->orWhereHas('position', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('city', fn ($q) => $q->where('name', 'like', "%{$search}%"));

                if ($includeRequesterInSearch) {
                    $inner->orWhereHas('requester', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                }
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom !== null) {
            $query->whereDate('request_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->whereDate('request_date', '<=', $this->dateTo);
        }

        if ($this->clientId !== null) {
            $query->where('client_id', $this->clientId);
        }

        if ($this->cityId !== null) {
            $query->where('city_id', $this->cityId);
        }

        if ($this->excludeClosedStatuses) {
            $query->whereNotIn('status', PersonalRequisition::closedStatuses());
        }
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        return $string;
    }
}
