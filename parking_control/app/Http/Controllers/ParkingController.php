<?php

namespace App\Http\Controllers;

use App\Http\Resources\ParkingResource;
use App\Models\Parking;
use App\Service\ParkingService;
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

    public function __construct(ParkingService $parkingService)
    {
        $this->parkingService = $parkingService;
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
        return response()->json([
            'allowed' => $allowed
        ]);
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