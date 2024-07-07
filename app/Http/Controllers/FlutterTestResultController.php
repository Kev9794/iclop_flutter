<?php

namespace App\Http\Controllers;

use App\Models\FlutterTestResult;
use Illuminate\Http\Request;

class FlutterTestResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('flutter.student.material.topics_detail');
    }

    public function getResultData(Request $request)
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi request jika diperlukan
        $request->validate([
            'success_tests' => 'required|array',
            'success_tests.*' => 'string', // setiap elemen dalam array success_tests harus berupa string
            'failed_tests' => 'required|array',
            'failed_tests.*' => 'string', // setiap elemen dalam array failed_tests harus berupa string
            'score' => 'required|string',
        ]);


        // Simpan data ke dalam database menggunakan model
        $testResult = FlutterTestResult::create([
            'success_tests' => $request->success_tests,
            'failed_tests' => $request->failed_tests,
            'score' => $request->score,
        ]);

        // Jika ingin memberikan respons atau melakukan sesuatu setelah penyimpanan
        return response()->json(['message' => 'Data berhasil disimpan.', 'data' => $testResult]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FlutterTestResult $flutterTestResult)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FlutterTestResult $flutterTestResult)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FlutterTestResult $flutterTestResult)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FlutterTestResult $flutterTestResult)
    {
        //
    }
}