<?php

namespace Modules\FrontendSetting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\FrontendSetting\Models\WhyChoose;
use Modules\FrontendSetting\Models\WhyChooseFeature;

class WhyChooseSettingController extends Controller
{
    /**
     * Show the form for editing the Why Choose Us section.
     */
    public function show()
    {
        $data = WhyChoose::latest()->first();
        return view('frontendsetting::sections.why_choose_section', compact('data'));
    }

    /**
     * Store the Why Choose Us section data.
     */
    public function store(Request $request)
    {

        try {
            $validated = $request->validate([
                'chooseUs_title' => 'required|string',
                // 'chooseUs_subtitle' => 'required|string',
                'chooseUs_description' => 'required|string',
                'chooseUs_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,avif|max:10000',
                'add_more_title' => 'nullable|array',
                'add_more_subtitle' => 'nullable|array',
                'add_more_image' => 'nullable|array',
            ]);

            $imagePath = $request->input('existing_image', '');
            if ($request->hasFile('chooseUs_image')) {
                $image = $request->file('chooseUs_image');
                $imagePath = $image->store('why_choose', 'public');
            }

            $data = [
                'image' => $imagePath,
                'title' => $validated['chooseUs_title'],
                // 'subtitle' => $validated['chooseUs_subtitle'],
                'description' => $validated['chooseUs_description'],
            ];

            $whyChoose = WhyChoose::updateOrCreate([], $data);

            // Remove old features
            // $whyChoose->features()->delete(); // Removed to preserve existing features

            // Save new features if any
            if ($request->has('add_more_title')) {
                $titles = $request->input('add_more_title', []);
                $subtitles = $request->input('add_more_subtitle', []);
                $images = $request->file('add_more_image', []);
                foreach ($titles as $i => $title) {
                    $featureData = [
                        'title' => $title,
                        'subtitle' => $subtitles[$i] ?? null,
                        'image' => null,
                    ];
                    if (isset($images[$i]) && $images[$i]) {
                        $featureData['image'] = $images[$i]->store('why_choose_features', 'public');
                    }
                    $whyChoose->features()->create($featureData);
                }
            }


            return redirect()->back()->with('success', 'Why Choose Us section updated successfully.');
        } catch (\Exception $e) {

            return redirect()->back()->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }
    public function destroy($id)
    {
        $feature = WhyChooseFeature::findOrFail($id);
        $feature->delete();
        return redirect()->back()->with('success', 'Feature deleted successfully.');
    }
}
