<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Services\AI\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_can_be_created(): void
    {
        $this->mock(EmbeddingService::class, function ($mock) {

            $mock->shouldReceive('generate')
                ->once()
                ->andReturn([
                    0.1,
                    0.2,
                    0.3,
                ]);
        });

        $response = $this->postJson('/api/notes', [
            'title' => 'Test Note',
            'content' => 'This is a test note.',
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('notes', [
            'title' => 'Test Note',
            'content' => 'This is a test note.',
        ]);
    }


    public function test_note_title_is_required(): void
    {
        $response = $this->postJson('/api/notes', [
            'content' => 'Test content',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
        ]);
    }


    public function test_notes_can_be_listed(): void
    {
        Note::create([
            'title' => 'Test',
            'content' => 'Test content',
        ]);

        $response = $this->getJson('/api/notes');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);
    }


    public function test_note_can_be_deleted(): void
    {
        $note = Note::create([
            'title' => 'Delete Me',
            'content' => 'Delete this note.',
        ]);

        $response = $this->deleteJson(
            "/api/notes/{$note->id}"
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing(
            'notes',
            [
                'id' => $note->id,
            ]
        );
    }
}