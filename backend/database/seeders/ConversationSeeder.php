<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@eyecare.test')->first();
        $staff = User::where('email', 'staff@eyecare.test')->first();
        $admin = User::where('email', 'admin@eyecare.test')->first();

        if (! $customer || ! $staff || ! $admin) {
            $this->command->warn('ConversationSeeder skipped: seed users not found. Run UserSeeder first.');

            return;
        }

        $conversation = Conversation::create([
            'user_id' => $customer->id,
            'last_message_at' => now()->subMinutes(5),
        ]);

        $this->addMessages($conversation->id, [
            [$customer->id, 'Hi, I wanted to ask about my lens prescription. My doctor gave me a new one — do I need to come in for a fitting?', now()->subHours(3)],
            [$staff->id, 'Hello Juan! Yes, if you have a new prescription we recommend dropping by so we can verify the measurements and check your current frames are still suitable.', now()->subHours(2)->subMinutes(45)],
            [$customer->id, 'Great, can I walk in or do I need an appointment?', now()->subHours(2)->subMinutes(20)],
            [$staff->id, 'Walk-ins are welcome during business hours (Mon–Sat, 9AM–6PM). We usually process lens replacements on the spot if the frames are with us.', now()->subHours(1)->subMinutes(50)],
            [$customer->id, 'Perfect, I\'ll come by this Saturday. Thank you!', now()->subMinutes(5)],
        ]);
    }

    /**
     * @param  array<array{0: int, 1: string, 2: \Carbon\Carbon}>  $messages
     */
    private function addMessages(int $conversationId, array $messages, bool $readAll = false): void
    {
        foreach ($messages as $i => [$senderId, $body, $sentAt]) {
            $isRead = $readAll || ($i < count($messages) - 1);

            Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'body' => $body,
                'is_read' => $isRead,
                'read_at' => $isRead ? $sentAt->copy()->addMinutes(rand(2, 10)) : null,
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ]);
        }
    }
}
