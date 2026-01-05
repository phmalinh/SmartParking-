<?php

namespace App\Repository;

use App\Models\Parking;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;

class ParkingRepository extends Repository
{
    protected $model;

    public function __construct(Parking $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy danh sách tất cả các note kèm thông tin user
     */
    public function index()
    {
        return $this->model->latest()->get();
    }
     public function isAllowedPlate(string $plate): bool
    {
        return Parking::where('plate_number', $plate)
            ->where('action', 'Activate')
            ->exists();
    }

    public function create(array $data)
    {
        return $this->model->create([
            'car_owner' => $data['car_owner'],
            'plate_number' => $data['plate_number'],
            'action' => $data['action']? 'Activate' : 'Deactivate',
        ]);
    }
    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function update(Parking $parking, array $data)
    {
        $parking->update([
            'car_owner'   => $data['car_owner'],
            'plate_number'=> $data['plate_number'],
            'action'      => $data['action'], // Activate | Deactivate
        ]);

        return $parking->fresh();
    }

    public function delete(Parking $parking)
    {
        return $parking->delete();
    }
}