<?php

namespace App\Http\Controllers;

use App\Http\Resources\ParkingResource;
use App\Models\Parking;
use App\Service\ParkingService;
use App\Service\ParkingHistoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ParkingController extends Controller
{
    use ApiResponse;

    private $parkingService;
    private $historyService;

    public function __construct(ParkingService $parkingService, ParkingHistoryService $historyService)
    {
        $this->parkingService = $parkingService;
        $this->historyService = $historyService;
    }

     public function index()
    {
        $notes = $this->parkingService->index();

        return ParkingResource::collection($notes);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_owner' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255',
            'action' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $validatedData = $validator->validated();
        $park = $this->parkingService->create($validatedData);

        return new ParkingResource($park);
    }
 public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'car_owner'    => 'required|string|max:255',
            'plate_number' => 'required|string|max:255',
            'action'       => 'required|in:Activate,Deactivate',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $parking = $this->parkingService->update(
            $request->only(['car_owner', 'plate_number', 'action']),
            $id
        );

        return new ParkingResource($parking);
    }
    public function destroy($id)
    {
        $deleted = $this->parkingService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Not found or already deleted',
            ], 404);
        }

        return response()->json([
            'message' => '刪除成功',
        ], 200);
    }


    public function check(Request $request)
    {
       // $plate = strtoupper($request->plate);
        $plate = strtoupper($request->json('plate') ?? '');
        $plate = preg_replace('/[^A-Z0-9]/', '', $plate);
        $allowed = Parking::whereRaw(
                "REPLACE(UPPER(plate_number), '-', '') = ?",
                [$plate]
                )
                ->where('action', 'Activate')
                ->exists();

        // Lấy thông tin chủ xe
        $vehicle = Parking::whereRaw(
                "REPLACE(UPPER(plate_number), '-', '') = ?",
                [$plate]
                )
                ->where('action', 'Activate')
                ->first();

        // Kiểm tra xem xe có đang đậu trong bãi không
        $isInside = false;
        if ($allowed && $vehicle) {
            // Kiểm tra trạng thái xe từ lịch sử
            $canEnter = $this->historyService->canVehicleEnter($vehicle->plate_number);
            if (!$canEnter) {
                // Xe đang đậu trong bãi, từ chối vào
                $allowed = false;
                $isInside = true;
            }
        }

        // Ghi lịch sử - chỉ ghi khi được phép vào
        if ($allowed && $vehicle) {
            try {
                $this->historyService->recordEntry(
                    $vehicle->plate_number,
                    $vehicle->car_owner,
                    'Manual check - Entry'
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to record entry history: ' . $e->getMessage());
            }
        }

        return response()->json([
            'allowed' => $allowed,
            'reason' => $isInside ? 'Xe đang đậu trong bãi' : null
        ]);
    }

    /**
     * Ghi nhận xe ra
     */
    public function checkExit(Request $request)
    {
        $plate = strtoupper($request->json('plate') ?? '');
        $plate = preg_replace('/[^A-Z0-9]/', '', $plate);
        
        // Lấy thông tin chủ xe từ whitelist
        $vehicle = Parking::whereRaw(
                "REPLACE(UPPER(plate_number), '-', '') = ?",
                [$plate]
                )
                ->where('action', 'Activate')
                ->first();

        // Kiểm tra nếu xe không trong hệ thống
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Xe không trong hệ thống',
                'vehicle' => [
                    'owner' => 'Unknown',
                    'registered' => false
                ]
            ], 404);
        }

        // Kiểm tra xem xe có đang đậu trong bãi không
        $vehicleStatus = $this->historyService->getVehicleStatus($vehicle->plate_number);
        if ($vehicleStatus !== 'inside') {
            return response()->json([
                'success' => false,
                'message' => 'Xe chưa vào hoặc đã ra rồi',
                'vehicle' => [
                    'plate' => $vehicle->plate_number,
                    'owner' => $vehicle->car_owner,
                    'registered' => true,
                    'status' => $vehicleStatus
                ]
            ], 400);
        }

        // Ghi lịch sử xe ra
        try {
            $this->historyService->recordExit(
                $vehicle->plate_number,
                'Manual check - Exit'
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to record exit history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi ghi nhận lịch sử'
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Ghi nhận xe ra thành công',
            'vehicle' => [
                'plate' => $vehicle->plate_number,
                'owner' => $vehicle->car_owner,
                'registered' => true,
                'status' => 'outside'
            ]
        ]);
    }

    /**
     * Ghi nhận xe ra bằng AI OCR
     */
    public function processAiOcrExit(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'Không có ảnh'], 400);
            }

            $response = Http::timeout(30)
                ->attach(
                    'file',
                    file_get_contents($request->file('file')),
                    'capture.jpg'
                )
                ->post('http://127.0.0.1:8001/predict');

            if ($response->failed()) {
                return response()->json(['error' => 'OCR service failed'], 500);
            }

            $plate = $response->json('plate') ?? '';
            $plateImage = $response->json('plate_image');
            
            // Làm sạch biển số để tìm kiếm
            $cleanPlate = preg_replace('/[^A-Z0-9]/', '', strtoupper($plate));

            // Lấy thông tin chủ xe
            $vehicle = Parking::whereRaw(
                    "REPLACE(UPPER(plate_number), '-', '') = ?",
                    [$cleanPlate]
                )
                ->where('action', 'Activate')
                ->first();

            // Kiểm tra xem xe có đang đậu trong bãi không
            $canExit = false;
            $status = 'unknown';
            
            if ($vehicle) {
                $status = $this->historyService->getVehicleStatus($vehicle->plate_number);
                $canExit = ($status === 'inside'); // Chỉ cho phép ra nếu đang ở trong bãi
                
                if ($canExit) {
                    try {
                        $this->historyService->recordExit(
                            $vehicle->plate_number,
                            'AI OCR scan - Exit'
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Failed to record OCR exit history: ' . $e->getMessage());
                    }
                }
            }

            return response()->json([
                'plate' => $plate,
                'plate_image' => $plateImage,
                'success' => $canExit,
                'vehicle' => $vehicle ? [
                    'owner' => $vehicle->car_owner,
                    'registered' => true,
                    'status' => $status
                ] : [
                    'owner' => 'Unknown',
                    'registered' => false,
                    'status' => 'unknown'
                ],
                'message' => $vehicle ? 
                    ($canExit ? 'Ghi nhận xe ra thành công' : 'Xe chưa vào hoặc đã ra rồi') :
                    'Xe không trong hệ thống'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // public function process_ai_ocr(Request $request)
    // {
    //     try {
    //         if (!$request->hasFile('file')) {
    //             return response()->json(['error' => 'Không có ảnh'], 400);
    //         }

    //         $response = Http::timeout(30)->attach(
    //             'file',
    //             file_get_contents($request->file('file')),
    //             'capture.jpg'
    //         )->post('http://127.0.0.1:8001/predict');

    //         if ($response->failed()) {
    //             return response()->json(['error' => 'OCR service failed'], 500);
    //         }
    //         $plate = $response->json('plate') ?? '';
    //         $allowed = Parking::where('plate_number', $plate)
    //                 ->where('action', 'Activate')
    //                 ->exists();
    //         return response()->json([
    //             'plate' => $plate,
    //             'allowed' => $allowed
    //         ]);       
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    public function process_ai_ocr(Request $request)
    {
        try {
            $result = $this->parkingService->process($request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}