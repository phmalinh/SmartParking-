<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Repository\Contracts\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected UserRepositoryInterface $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function register(array $data): array
    {
        if ($this->userRepo->findByEmail($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['Email này đã được sử dụng.'],
            ]);
        }

        $user = $this->userRepo->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    public function login(array $credentials): array
    {
        // 1. Kiểm tra đăng nhập
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng'],
            ]);
        }

        // 2. Lấy đối tượng User (phải là Object, không phải ID)
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 3. Tạo token từ Object user
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user // Trả về cả object user để frontend dùng
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}