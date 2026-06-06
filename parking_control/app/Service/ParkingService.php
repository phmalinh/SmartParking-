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
     private ParkingHistoryService $historyService;

    // private FileCenterService $fileCenterService;

    public function __construct(ParkingRepository $parkingRepository, ParkingHistoryService $historyService)
    {
        $this->parkingRepository = $parkingRepository;
        $this->historyService = $historyService;
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

        // $response = Http::timeout(30)
        //     ->attach(
        //         'file',
        //         file_get_contents($request->file('file')),
        //         'capture.jpg'
        //     )
        //     ->post('http://127.0.0.1:8001/predict');
        $response = Http::timeout(60) // Tăng timeout lên 60s phòng trường hợp Render Free bị ngủ đông (Sleep)
            ->attach(
                'file',
                file_get_contents($request->file('file')),
                'capture.jpg'
            )
            ->post('https://smartparking-ai.onrender.com/predict');

        if ($response->failed()) {
            throw new \Exception('OCR service failed');
        }

        $plate = $response->json('plate') ?? '';
        $plateImage = $response->json('plate_image');
        $allowed = $this->parkingRepository->isAllowedPlate($plate);
        $vehicle = $this->parkingRepository->findByPlateNumber($plate);
        $isInside = false;

        // Kiểm tra xem xe có đang đậu trong bãi không
        if ($allowed && $vehicle) {
            $canEnter = $this->historyService->canVehicleEnter($vehicle->plate_number);
            if (!$canEnter) {
                // Xe đang đậu trong bãi, từ chối vào
                $allowed = false;
                $isInside = true;
            }
        }

        // Ghi lịch sử tự động - chỉ ghi khi vào thành công
        try {
            if ($allowed && $vehicle) {
                // Xe được phép vào
                $this->historyService->recordEntry(
                    $vehicle->plate_number,
                    $vehicle->car_owner,
                    'AI OCR scan - Entry'
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to record AI OCR history: ' . $e->getMessage());
            // Không ảnh hưởng đến kết quả chính
        }

        return [
            'plate' => $plate,
            'allowed' => $allowed,
            'plate_image' => $plateImage,
            'reason' => $isInside ? 'Xe đang đậu trong bãi' : null,
            'vehicle' => $vehicle ? [
                'car_owner' => $vehicle->car_owner,
                'plate_number' => $vehicle->plate_number
            ] : null
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