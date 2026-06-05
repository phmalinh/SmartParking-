<?php

namespace App\Service;

use App\Models\ParkingHistory;
use Carbon\Carbon;

class ParkingHistoryService
{
    /**
     * Ghi lịch sử xe vào
     */
    public function recordEntry($plateNumber, $carOwner = null, $notes = null)
    {
        // Làm sạch biển số trước khi ghi
        $cleanPlate = preg_replace('/[^A-Z0-9]/', '', strtoupper($plateNumber));
        
        // Tìm biển số chính xác trong database để lấy format đúng
        $vehicle = \App\Models\Parking::whereRaw(
            "REPLACE(UPPER(plate_number), '-', '') = ?",
            [$cleanPlate]
        )->first();
        
        $finalPlate = $vehicle ? $vehicle->plate_number : $plateNumber;
        $finalOwner = $carOwner ?? ($vehicle ? $vehicle->car_owner : null);
        
        return ParkingHistory::create([
            'plate_number' => $finalPlate,
            'car_owner' => $finalOwner,
            'action' => 'Entry',
            'action_time' => Carbon::now(),
            'notes' => $notes
        ]);
    }

    /**
     * Ghi lịch sử xe ra
     */
    public function recordExit($plateNumber, $notes = null)
    {
        // Làm sạch biển số trước khi ghi
        $cleanPlate = preg_replace('/[^A-Z0-9]/', '', strtoupper($plateNumber));
        
        // Tìm biển số chính xác trong database để lấy format đúng
        $vehicle = \App\Models\Parking::whereRaw(
            "REPLACE(UPPER(plate_number), '-', '') = ?",
            [$cleanPlate]
        )->first();
        
        $finalPlate = $vehicle ? $vehicle->plate_number : $plateNumber;
        
        return ParkingHistory::create([
            'plate_number' => $finalPlate,
            'action' => 'Exit',
            'action_time' => \Carbon\Carbon::now(),
            'notes' => $notes
        ]);
    }

    /**
     * Lấy lịch sử của một xe
     */
    public function getVehicleHistory($plateNumber, $limit = 50)
    {
        return ParkingHistory::where('plate_number', $plateNumber)
            ->orderBy('action_time', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy lịch sử trong khoảng thời gian
     */
    public function getHistoryByDateRange($startDate, $endDate, $action = null)
    {
        $query = ParkingHistory::whereBetween('action_time', [$startDate, $endDate]);
        
        if ($action) {
            $query->where('action', $action);
        }
        
        return $query->orderBy('action_time', 'desc')->get();
    }

    /**
     * Lấy lịch sử hôm nay
     */
    public function getTodayHistory($action = null)
    {
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        
        return $this->getHistoryByDateRange($startOfDay, $endOfDay, $action);
    }

    /**
     * Lấy toàn bộ lịch sử
     */
    public function getAllHistory($limit = 100)
    {
        return ParkingHistory::orderBy('action_time', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Thống kê xe vào/ra
     */
    public function getStatistics($startDate = null, $endDate = null)
    {
        $query = ParkingHistory::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('action_time', [$startDate, $endDate]);
        }
        
        $entries = (clone $query)->where('action', 'Entry')->count();
        $exits = (clone $query)->where('action', 'Exit')->count();
        
        return [
            'entries' => $entries,
            'exits' => $exits,
            'total' => $entries + $exits
        ];
    }

    /**
     * Xóa lịch sử cũ (tùy chọn)
     */
    public function deleteOldHistory($daysOld = 30)
    {
        $date = Carbon::now()->subDays($daysOld);
        return ParkingHistory::where('action_time', '<', $date)->delete();
    }

    /**
     * Kiểm tra trạng thái hiện tại của xe
     * Return: 'inside' = xe đang đậu trong bãi
     *         'outside' = xe đã ra khỏi bãi hoặc chưa vào
     */
    public function getVehicleStatus($plateNumber)
    {
        $lastRecord = ParkingHistory::where('plate_number', $plateNumber)
            ->orderBy('action_time', 'desc')
            ->first();
        
        // Nếu chưa có lịch sử = xe chưa vào
        if (!$lastRecord) {
            return 'outside';
        }
        
        // Nếu lần cuối là "Exit" = xe đã ra
        if ($lastRecord->action === 'Exit') {
            return 'outside';
        }
        
        // Nếu lần cuối là "Entry" = xe đang đậu
        return 'inside';
    }

    /**
     * Kiểm tra xe có thể vào không (đã ra hoặc chưa từng vào)
     */
    public function canVehicleEnter($plateNumber)
    {
        $status = $this->getVehicleStatus($plateNumber);
        return $status === 'outside';
    }
}