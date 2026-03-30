<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Student;
use App\Models\Course;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $response = Http::post('https://quiztoxml.ucas.edu.ps/api/login', [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        $data = $response->json();

        if (isset($data['success']) && $data['success']) {
            Student::updateOrCreate(
                ['student_id' => (string) $data['data']['user_id']],
                [
                    'name' => $data['data']['user_ar_name'],
                    'token' => $data['Token']
                ]
            );
        }

        return response()->json([
            'request' => $request->all(),   
            'api_response' => $data,
            'saved_student' => Student::where('student_id', $data['data']['user_id'] ?? '')->first()
        ]);
    }

    public function getTable(Request $request)
    {
        $student = Student::latest()->first();

        if (!$student) {
            return response()->json([
                'message' => 'لا يوجد طالب مسجل'
            ], 400);
        }

        $response = Http::post('https://quiztoxml.ucas.edu.ps/api/get-table', [
            'token' => $student->token,
            'user_id' => $student->student_id,
        ]);

        $data = $response->json();

        if (!isset($data['data'])) {
            return response()->json([
                'message' => 'لا توجد بيانات جدول',
                'data' => $data
            ], 400);
        }

        foreach ($data['data'] as $item) {

            $time = $item['M'] ?: $item['T'] ?: $item['W'] ?: $item['R'] ?: null;

            Course::create([
                'course_name' => $item['subject_name'],
                'doctor' => $item['teacher_name'],
                'time' => $time,
                'room' => $item['room_no'] ?: null,
            ]);
        }

        return response()->json([
            'message' => 'تم جلب الجدول بنجاح ',
            'data' => $data['data']
        ]);
    }
}