<?php

namespace App\Http\Controllers;

use App\Models\MonthlySalary;
use App\Models\StaffDetail;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
   public function index()
{
    $staff = User::with(['staffDetail', 'staffDetail.position', 'staffDetail.monthlySalaries'])  
        ->where('is_admin', 0)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $staff
    ]);
}
    public function show($id)
    {
        $staff = User::with(['staffDetail', 'staffDetail.position'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Log incoming data for debugging
            Log::info('Staff store request:', $request->all());

            // Validate
            $request->validate([
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'date_of_birth' => 'required|date',
                'phone' => 'required|string|max:20',
                'id_card' => 'required|string|unique:users,id_card',
                'gender' => 'required|string',
                'address' => 'required|string',
                'position_id' => 'required|exists:positions,id',
                'employee_id' => 'required|string|unique:staff_details,employee_id',
                'base_salary' => 'required|numeric|min:0',
            ]);

            // Check position and determine is_admin
            $position = Position::find($request->position_id);
            $isAdmin = in_array(strtolower($position->name), ['admin', 'administrator', 'អ្នកគ្រប់គ្រង']) ? 1 : 0;

            // Create user
            $user = User::create([
                'name' => $request->firstName . ' ' . $request->lastName,
                'email' => $request->email,
                'password' => Hash::make($request->id_card),
                'date_of_birth' => $request->date_of_birth,
                'phone' => $request->phone,
                'id_card' => $request->id_card,
                'is_admin' => $isAdmin,
                'position_id' => $request->position_id,
            ]);

            // Handle CV upload
            $cvPath = null;
            if ($request->hasFile('cv')) {
                $cvPath = $request->file('cv')->store('cv_files', 'public');
            }

            // Create staff detail
            StaffDetail::create([
                'user_id' => $user->id,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'address' => $request->address,
                'employee_id' => $request->employee_id,
                'contract_duration' => $request->contract_duration,
                'position_id' => $request->position_id,
                'work_type' => $request->work_type ?? 'ពេញម៉ោង',
                'join_date' => $request->join_date ?? now(),
                'base_salary' => $request->base_salary,
                'allowance' => $request->allowance ?? 0,
                'cv_file' => $cvPath,
                'emergency_name' => $request->emergency_name,
                'emergency_phone' => $request->emergency_phone,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staff created successfully',
                'data' => $user->load('staffDetail')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $staffDetail = StaffDetail::where('user_id', $id)->firstOrFail();

            $request->validate([
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'position_id' => 'required|exists:positions,id',
                'base_salary' => 'required|numeric|min:0',
                'allowance' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,on_leave,inactive',
                'work_type' => 'nullable|string',
                'join_date' => 'nullable|date',
                'contract_duration' => 'nullable|string',
                'address' => 'nullable|string',
                'emergency_name' => 'nullable|string',
                'emergency_phone' => 'nullable|string',
                'password' => 'nullable|string|min:6', // ADD THIS
            ]);

            // Check position and determine is_admin
            $position = Position::find($request->position_id);
            $isAdmin = in_array(strtolower($position->name), ['admin', 'administrator', 'អ្នកគ្រប់គ្រង']) ? 1 : 0;

            // Prepare update data for user
            $userData = [
                'name' => $request->firstName . ' ' . $request->lastName,
                'email' => $request->email,
                'phone' => $request->phone,
                'is_admin' => $isAdmin,
                'position_id' => $request->position_id,
            ];

            // Update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            // Update user
            $user->update($userData);

            // Update staff detail
            $staffDetail->update([
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'phone' => $request->phone,
                'address' => $request->address,
                'position_id' => $request->position_id,
                'work_type' => $request->work_type,
                'join_date' => $request->join_date,
                'contract_duration' => $request->contract_duration,
                'base_salary' => $request->base_salary,
                'allowance' => $request->allowance ?? 0,
                'emergency_name' => $request->emergency_name,
                'emergency_phone' => $request->emergency_phone,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staff updated successfully',
                'data' => $user->load('staffDetail')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }

            if ($user->staffDetail) {
                if ($user->staffDetail->cv_file && Storage::exists('public/' . $user->staffDetail->cv_file)) {
                    Storage::delete('public/' . $user->staffDetail->cv_file);
                }
                $user->staffDetail->delete();
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Staff deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function updateSalaryStatus(Request $request, $id)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $staffDetail = StaffDetail::findOrFail($id);

        $monthlySalary = MonthlySalary::updateOrCreate(
            [
                'staff_detail_id' => $staffDetail->id,
                'year' => $request->year,
                'month' => $request->month,
            ],
            [
                'base_salary' => $staffDetail->base_salary,
                'allowance' => $staffDetail->allowance,
                'total' => $staffDetail->base_salary + $staffDetail->allowance,
                'status' => $request->status,
                'paid_date' => $request->status === 'paid' ? now() : null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $monthlySalary,
            'message' => 'Salary status updated successfully'
        ]);
    }

    public function getMonthlySalaries($id)
    {
        $staffDetail = StaffDetail::findOrFail($id);
        $salaries = $staffDetail->monthlySalaries()->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $salaries
        ]);
    }
}
