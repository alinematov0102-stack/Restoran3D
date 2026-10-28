<?php

namespace App\Http\Controllers;

use App\Models\Taom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaomController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        if ($request->hasFile('rasm')) {
            $data['rasm'] = $request->file('rasm')->store('taomlar', 'public');
        }
        Taom::create($data);
        return redirect()->route('admin.dashboard')->with('success', 'Taom muvaffaqiyatli qo‘shildi.');
    }

    public function update(Request $request, Taom $taom)
    {
        $data = $this->validatedData($request);
        if ($request->hasFile('rasm')) {
            if ($taom->rasm) Storage::disk('public')->delete($taom->rasm);
            $data['rasm'] = $request->file('rasm')->store('taomlar', 'public');
        } else {
            unset($data['rasm']);
        }
        $taom->update($data);
        return redirect()->route('admin.dashboard')->with('success', 'Taom ma’lumotlari yangilandi.');
    }

    public function destroy(Taom $taom)
    {
        if ($taom->rasm) Storage::disk('public')->delete($taom->rasm);
        $taom->delete();
        return redirect()->route('admin.dashboard')->with('success', 'Taom o‘chirildi.');
    }

    private function validatedData(Request $request)
    {
        return $request->validate([
            'nomi' => 'required|string|max:255',
            'narxi' => 'required|numeric|min:0|max:999999999',
            'miqdori' => 'required|integer|min:0|max:1000000',
            'kategoriya' => 'nullable|string|max:100',
            'tavsif' => 'nullable|string|max:1000',
            'rasm' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
    }
}
