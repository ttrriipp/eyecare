<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_message_to_my_thread_creates_conversation(): void
    {
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/conversations/my/messages', [
            'body' => 'Hello clinic',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'Hello clinic')
            ->assertJsonStructure(['conversation' => ['id']]);

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_start_conversation_is_idempotent_for_customer(): void
    {
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $first = $this->postJson('/api/v1/conversations', []);
        $first->assertCreated();
        $id = $first->json('conversation.id');

        $second = $this->postJson('/api/v1/conversations', []);
        $second->assertCreated();
        $this->assertSame($id, $second->json('conversation.id'));
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_customer_conversation_index_returns_single_thread(): void
    {
        $customer = User::factory()->customer()->create();
        Conversation::create([
            'user_id' => $customer->id,
            'last_message_at' => now(),
        ]);
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_unique_user_id_enforced_on_conversations_table(): void
    {
        $customer = User::factory()->customer()->create();
        Conversation::create([
            'user_id' => $customer->id,
            'last_message_at' => null,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Conversation::create([
            'user_id' => $customer->id,
            'last_message_at' => null,
        ]);
    }
}
