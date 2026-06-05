<?php

namespace App\Http\Controllers;

use App\Service\ParkingHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParkingHistoryController extends Controller
{
    protected $historyService;

    public function __construct(ParkingHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    /**
     * Lấy lịch sử của một chiếc xe
     * GET /api/history/{plate_number}
     */
    public function getVehicleHistory($plateNumber, Request $request)
    {
        try {
            $limit = $request->query('limit', 50);
            $history = $this->historyService->getVehicleHistory($plateNumber, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'plate_number' => $plateNumber,
                'total' => $history->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting vehicle history', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử'
            ], 500);
        }
    }

    /**
     * Lấy lịch sử hôm nay
     * GET /api/history/today
     */
    public function getTodayHistory(Request $request)
    {
        try {
            $action = $request->query('action'); // 'Entry' hoặc 'Exit'
            $history = $this->historyService->getTodayHistory($action);
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'date' => date('Y-m-d'),
                'total' => $history->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting today history', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử hôm nay'
            ], 500);
        }
    }

    /**
     * Lấy lịch sử trong khoảng thời gian
     * GET /api/history/range?start_date=2026-01-01&end_date=2026-01-15&action=Entry
     */
    public function getHistoryByRange(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'action' => 'nullable|in:Entry,Exit'
            ]);

            $history = $this->historyService->getHistoryByDateRange(
                $validated['start_date'],
                $validated['end_date'],
                $validated['action'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total' => $history->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting history by range', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử'
            ], 500);
        }
    }

    /**
     * Lấy toàn bộ lịch sử
     * GET /api/history
     */
    public function getAllHistory(Request $request)
    {
        try {
            $limit = $request->query('limit', 100);
            $history = $this->historyService->getAllHistory($limit);
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'total' => $history->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting all history', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử'
            ], 500);
        }
    }

    /**
     * Lấy thống kê
     * GET /api/history/statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            $stats = $this->historyService->getStatistics($startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting statistics', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê'
            ], 500);
        }
    }

    /**
     * Ghi lịch sử xe vào (thường được gọi tự động)
     * POST /api/history/entry
     */
    public function recordEntry(Request $request)
    {
        try {
            $validated = $request->validate([
                'plate_number' => 'required|string',
                'car_owner' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $history = $this->historyService->recordEntry(
                $validated['plate_number'],
                $validated['car_owner'] ?? null,
                $validated['notes'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Ghi lịch sử xe vào thành công',
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording entry', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi ghi lịch sử'
            ], 500);
        }
    }

    /**
     * Ghi lịch sử xe ra (thường được gọi tự động)
     * POST /api/history/exit
     */
    public function recordExit(Request $request)
    {
        try {
            $validated = $request->validate([
                'plate_number' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $history = $this->historyService->recordExit(
                $validated['plate_number'],
                $validated['notes'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Ghi lịch sử xe ra thành công',
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording exit', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi ghi lịch sử'
            ], 500);
        }
    }
}
