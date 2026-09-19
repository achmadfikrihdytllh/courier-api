<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourierRequest;
use App\Http\Requests\IndexCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    /**
     * GET /api/couriers
     *
     * Query params (semua opsional):
     *  - search    : kata kunci nama, mis. "budi agung"
     *  - level     : filter level, mis. "2,3"
     *  - sort      : "name" (default) atau "created_at" (tanggal didaftarkan)
     *  - direction : "asc" (default) atau "desc"
     *  - per_page  : 1-100 (default 15)
     */
    public function index(IndexCourierRequest $request): AnonymousResourceCollection
    {
        $couriers = Courier::query()
            ->search($request->validated('search'))
            ->ofLevels($request->levels())
            ->sorted($request->sortColumn(), $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return CourierResource::collection($couriers);
    }

    /** POST /api/couriers */
    public function store(CourierRequest $request): CourierResource
    {
        $courier = Courier::create($request->validated());

        return new CourierResource($courier);
    }

    /** GET /api/couriers/{courier} */
    public function show(Courier $courier): CourierResource
    {
        return new CourierResource($courier);
    }

    /** PUT|PATCH /api/couriers/{courier} */
    public function update(CourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->validated());

        return new CourierResource($courier);
    }

    /** DELETE /api/couriers/{courier} */
    public function destroy(Courier $courier): Response
    {
        $courier->delete();

        return response()->noContent();
    }
}
