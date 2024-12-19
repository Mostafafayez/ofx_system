<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Create a new note.
     */
    public function createNote(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('owner') && !$user->hasRole('manager') && !$user->hasRole('teamleader') ) {
            return response()->json(['message' => 'Permission denied .'], 403);
        }


        $validated = $request->validate([
            'note' => 'required|string',
            'user_id' => 'nullable|integer',
            'notable_type' => 'nullable|string', // Model type (e.g., Contract,Task,lead,user{private note})
            'notable_id' => 'nullable|integer',
        ]);

        $validated['user_id'] = $user->id;

        $note = Note::create($validated);

        return response()->json([
            'message' => 'Note created successfully',
            'data' => $note
        ], 201);
    }

    /**
     * Retrieve a single note by ID.
     */
    public function getNote($id)
    {

        $note = Note::with('notable', 'user')->where('notable_id',$id);

        return response()->json([
            'message' => 'Note retrieved successfully',
            'data' => $note
        ]);
    }

    //notebly type maybe for lead or contract & task andeach lead & contract &task  assign to user  : iwant api to get all notes for each user may use hasManyThrough in note model

    /**
     * Retrieve all notes.
     */
    public function getAllNotes()
    {
        $user = auth()->user();
        if (!$user->hasRole('owner')) {
            return response()->json(['message' => 'Permission denied .'], 403);
        }
        $notes = Note::with('notable', 'user')->get();

        return response()->json([
            'message' => 'Notes retrieved successfully',
            'data' => $notes
        ]);
    }

    /**
     * Delete a note by ID.
     */
    public function deleteNote($id)
    {
        $note = Note::findOrFail($id);
        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully'
        ]);
    }


     /**
     * Get notes by receiver ID.
     */



    public function getNotes_leads()     //by user_id
    {
        $sales = auth()->user();

        $notes = User::with(['leads_notes' => function ($query) {
            $query->where('notable_type', 'lead')->with('notable','user');
        }])
        ->where('id', $sales->id)
        ->get();

        return response()->json([
            'message' => 'Notes retrieved successfully',
            'data' => $notes
        ]);
    }

    public function getNotes_Contracts()     //by user_id
    {
        $sales = auth()->user();

        $notes = User::with(['contracts_notes' => function ($query) {
            $query->where('notable_type', 'contract')->with('notable','user');
        }])
        ->where('id', $sales->id)
        ->get();

        return response()->json([
            'message' => 'Notes retrieved successfully',
            'data' => $notes
        ]);
    }


    public function getNotes_tasks()     //by user_id
    {
        $sales = auth()->user();

        $notes = User::with(['contracts_tasks' => function ($query) {
            $query->where('notable_type', 'task')->with('notable');
        }])
        ->where('id', $sales->id)
        ->get();

        return response()->json([
            'message' => 'Notes retrieved successfully',
            'data' => $notes
        ]);
    }

    public function getNotes_user()     //by user_id
    {
        $sales = auth()->user();

        $users = Note::where('notable_type', 'user')
                  ->where('notable_id', $sales->id)->with('notable','user')
                  ->get();

        return response()->json([
            'message' => 'Users with specific notes retrieved successfully',
            'data' => $users
        ]);
    }
    /**
     * Get notes by user ID (creator).
     */
    public function getNotesByUser()
    {

          $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }
        $notes = Note::with('notable', 'user')
        ->where('user_id', $user->id)
        ->get();
;

        return response()->json([
            'message' => 'Notes retrieved successfully',
            'data' => $notes
        ]);
    }
}



