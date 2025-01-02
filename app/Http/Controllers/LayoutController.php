<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Layout;
use App\Models\Question;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class LayoutController extends Controller
{
 use AuthorizesRequests;


        public function __construct()
        {

            $this->authorize('access-sales');
        }



        public function store(Request $request)
        {
            $request->validate([
                'service_id' => 'required|exists:services,id',
                'questions' => 'required|array',
                'questions.*.question' => 'required|string',
                'questions.*.type' => 'required|string',
                'questions.*.options' => 'nullable|array',
            ]);

            $createdQuestions = [];

            foreach ($request->questions as $questionData) {
                $createdQuestions[] = Layout::create([
                    'service_id' => $request->service_id,
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'options' => $questionData['options'] ?? null,
                ]);
            }

            return response()->json($createdQuestions, 200);
        }



    // Get all questions
    public function index()
    {
        $questions = Layout::with('service')->get();
        return response()->json($questions);
    }


    public function show($id)
    {
        $question = Layout::with('service')->where('service_id',$id)->get();
        return response()->json($question);
    }

    // Update a question
    public function update(Request $request, $id)
    {
        $request->validate([
            'service_id' => 'sometimes|exists:services,id',
            'question' => 'sometimes|string',
            'type' => 'sometimes|string',
            'options' => 'sometimes|array',
        ]);

        $question = Layout::findOrFail($id);

        if ($request->has('service_id')) {
            $question->service_id = $request->service_id;
        }
        if ($request->has('question')) {
            $question->question = $request->question;
        }
        if ($request->has('type')) {
            $question->type = $request->type;
        }
        if ($request->has('options')) {
            $question->options = $request->options;
        }

        $question->save();

        return response()->json($question);
    }


    public function destroy($id)
    {
        $question = Layout::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully']);
    }


 

}
