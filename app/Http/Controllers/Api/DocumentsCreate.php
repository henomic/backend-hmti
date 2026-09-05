<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\documents;
use App\Models\timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DocumentsCreate extends Controller
{
    public function store($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'type_documents' => 'required|in:pict,note,file',
            'file_path' => 'nullable',
        ]);

        if ($validator->fails()) {

            Log::info("validasi gagal");
            Log::info($request->all());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tm = timetable::find($id);

            if (!$tm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timetable tidak ditemukan',
                ], 404);
            }

            $document = $tm->documents()->create([
                'name' => $request->name,
                'note' => $request->note,
                'type_documents' => $request->type_documents,
                'file_path' => $request->file_path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document berhasil ditambahkan',
                'data' => $document,
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Error insert document', [
                'error' => $th->getMessage(),
                'timetable_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan document',
                'error' => $th->getMessage(),
            ], 500);
        }
    }



    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'type_documents' => 'required|in:pict,note,file',
            'file_path' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $document = documents::find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document tidak ditemukan',
                ], 404);
            }

            $document->update([
                'name' => $request->name,
                'note' => $request->note,
                'type_documents' => $request->type_documents,
                'file_path' => $request->file_path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document berhasil diperbarui',
                'data' => $document->fresh(),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error update document', [
                'error' => $th->getMessage(),
                'document_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui document',
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $document = documents::find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document tidak ditemukan',
                ], 404);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document berhasil dihapus',
                'id' => $id,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error delete document', [
                'error' => $th->getMessage(),
                'document_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus document',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
