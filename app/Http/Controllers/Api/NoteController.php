<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Services\AI\AIService;
use App\Services\AI\EmbeddingService;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Get notes list with pagination.
     */
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        // Minimum 1, maximum 100
        $limit = max(1, min($limit, 100));

        $notes = Note::latest()->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Notes retrieved successfully.',
            'data' => $notes->items(),
            'pagination' => [
                'current_page' => $notes->currentPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
                'last_page' => $notes->lastPage(),
            ],
        ], 200);
    }


    /**
     * Create a new note.
     */
    public function store(
        StoreNoteRequest $request,
        EmbeddingService $embeddingService
    ) {
        $data = $request->validated();

        try {

            /*
             * Generate embedding from title + content
             */
            $embedding = $embeddingService->generate(
                $data['title'] . "\n" . $data['content']
            );

            $data['embedding'] = json_encode($embedding);

            /*
             * Create note
             */
            $note = Note::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Note created successfully.',
                'data' => $note,
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to create note.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * Get single note.
     */
    public function show(Note $note)
    {
        return response()->json([
            'success' => true,
            'message' => 'Note retrieved successfully.',
            'data' => $note,
        ], 200);
    }


    /**
     * Update note.
     */
    public function update(
        UpdateNoteRequest $request,
        Note $note,
        EmbeddingService $embeddingService
    ) {
        $data = $request->validated();

        try {

            /*
             * Get updated values.
             *
             * If title/content isn't provided,
             * use existing values.
             */
            $title = $data['title'] ?? $note->title;

            $content = $data['content'] ?? $note->content;

            /*
             * Generate new embedding
             */
            $embedding = $embeddingService->generate(
                $title . "\n" . $content
            );

            $data['embedding'] = json_encode($embedding);

            /*
             * Update note
             */
            $note->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Note updated successfully.',
                'data' => $note->fresh(),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update note.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * Delete note.
     */
    public function destroy(Note $note)
    {
        try {

            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Note deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete note.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * Generate AI summary.
     */
    public function summary(
        Note $note,
        AIService $aiService
    ) {
        try {

            /*
             * Generate summary from note content
             */
            $summary = $aiService->generateSummary(
                $note->content
            );

            /*
             * Save summary
             */
            $note->update([
                'summary' => $summary,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI summary generated successfully.',
                'data' => [
                    'id' => $note->id,
                    'summary' => $summary,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate AI summary.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * AI Semantic Search.
     *
     * Example:
     * GET /api/notes/search?q=learning%20laravel
     */
    public function search(
        Request $request,
        EmbeddingService $embeddingService
    ) {
        /*
         * Validate search query
         */
        $request->validate([
            'q' => [
                'required',
                'string',
                'min:2',
                'max:500',
            ],
        ]);

        try {

            /*
             * Generate embedding for search query
             */
            $queryEmbedding = $embeddingService->generate(
                $request->query('q')
            );

            /*
             * Get notes which have embeddings
             */
            $notes = Note::whereNotNull('embedding')->get();

            $results = [];

            foreach ($notes as $note) {

                /*
                 * Convert stored JSON embedding
                 * back to PHP array
                 */
                $noteEmbedding = json_decode(
                    $note->embedding,
                    true
                );

                if (!is_array($noteEmbedding)) {
                    continue;
                }

                /*
                 * Calculate cosine similarity
                 */
                $score = $this->cosineSimilarity(
                    $queryEmbedding,
                    $noteEmbedding
                );

                $results[] = [
                    'note' => $note,
                    'similarity' => round($score, 4),
                ];
            }

            /*
             * Highest similarity first
             */
            usort(
                $results,
                function ($a, $b) {
                    return $b['similarity']
                        <=> $a['similarity'];
                }
            );

            /*
             * Return top 10 results
             */
            return response()->json([
                'success' => true,
                'message' => 'Semantic search completed.',
                'query' => $request->query('q'),
                'data' => array_slice($results, 0, 10),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Semantic search failed.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * Calculate cosine similarity between
     * two embedding vectors.
     */
    private function cosineSimilarity(
        array $a,
        array $b
    ): float {

        $dotProduct = 0;

        $normA = 0;

        $normB = 0;

        $length = min(
            count($a),
            count($b)
        );

        for ($i = 0; $i < $length; $i++) {

            $dotProduct +=
                $a[$i] * $b[$i];

            $normA +=
                $a[$i] ** 2;

            $normB +=
                $b[$i] ** 2;
        }

        /*
         * Avoid division by zero
         */
        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct /
            (
                sqrt($normA) *
                sqrt($normB)
            );
    }
}