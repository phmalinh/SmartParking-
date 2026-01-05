<?php

namespace App\Service;

use App\Repositories\NB\NoteRepository;
use App\Repositories\test\NoteRepositories;
use App\Repository\ParkingRepository;
use App\Services\Common\FileCenterService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;

class ParkingService
{
     private ParkingRepository $parkingRepository;

    // private FileCenterService $fileCenterService;

    public function __construct(ParkingRepository $parkingRepository)
    {
        $this->parkingRepository = $parkingRepository;
    }

    public function index()
    {
        return $this->parkingRepository->index();
    }

    public function create( $data)
    {
       // $data['user_id'] = $user->id;
        $parkingData = [
            'car_owner' => $data['car_owner'],
            'plate_number' => $data['plate_number'],
            'action' => $data['action']? 'Activate' : 'Deactivate',
        ];
        $parking = $this->parkingRepository->create($parkingData);

        return $parking->refresh();
    }
    public function process(Request $request): array
    {
        if (!$request->hasFile('file')) {
            throw new \Exception('Không có ảnh');
        }

        $response = Http::timeout(30)
            ->attach(
                'file',
                file_get_contents($request->file('file')),
                'capture.jpg'
            )
            ->post('http://127.0.0.1:8001/predict');

        if ($response->failed()) {
            throw new \Exception('OCR service failed');
        }

        $plate = $response->json('plate') ?? '';
        $allowed = $this->parkingRepository->isAllowedPlate($plate);

        return [
            'plate' => $plate,
            'allowed' => $allowed
        ];
    }

    public function update(array $data, $id)
    {
        $parking = $this->parkingRepository->findById($id);
        if (! $parking) {
            abort(404, 'Parking not found');
        }
        return $this->parkingRepository->update($parking, $data);
    }

    public function delete($id)
    {
        $parking = $this->parkingRepository->findById($id);
        if (! $parking) {
            return false;
        }
        return $this->parkingRepository->delete($parking);
    }
}