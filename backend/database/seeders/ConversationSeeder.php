<?php

namespace Database\Seeders;

use App\Enums\ConversationStatus;
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

        // ── Open (only one open conversation per customer in seed data)
        // ── Lens prescription inquiry
        $conv1 = Conversation::create([
            'user_id' => $customer->id,
            'subject' => 'Lens prescription inquiry',
            'status' => ConversationStatus::Open,
            'last_message_at' => now()->subMinutes(5),
        ]);

        $this->addMessages($conv1->id, [
            [$customer->id, 'Hi, I wanted to ask about my lens prescription. My doctor gave me a new one — do I need to come in for a fitting?', now()->subHours(3)],
            [$staff->id,   'Hello Juan! Yes, if you have a new prescription we recommend dropping by so we can verify the measurements and check your current frames are still suitable.', now()->subHours(2)->subMinutes(45)],
            [$customer->id, 'Great, can I walk in or do I need an appointment?', now()->subHours(2)->subMinutes(20)],
            [$staff->id,   'Walk-ins are welcome during business hours (Mon–Sat, 9AM–6PM). We usually process lens replacements on the spot if the frames are with us.', now()->subHours(1)->subMinutes(50)],
            [$customer->id, 'Perfect, I\'ll come by this Saturday. Thank you!', now()->subMinutes(5)],
        ]);

        // ── Closed: Contact lens stock (only one open thread per customer at a time)
        $conv2 = Conversation::create([
            'user_id' => $customer->id,
            'subject' => 'Contact lens availability',
            'status' => ConversationStatus::Closed,
            'last_message_at' => now()->subMinutes(20),
        ]);

        $this->addMessages($conv2->id, [
            [$customer->id, 'Good afternoon! Do you currently have daily disposable contact lenses in stock? Specifically looking for -2.50 power.', now()->subHours(4)],
            [$admin->id,   'Good afternoon, Juan! Let me check the inventory for you.', now()->subHours(3)->subMinutes(55)],
            [$admin->id,   'Yes, we have Acuvue Oasys and FreshLook in -2.50 available. Would you like to reserve a box?', now()->subHours(3)->subMinutes(40)],
            [$customer->id, 'Yes please, one box of Acuvue Oasys would be great.', now()->subHours(2)],
            [$admin->id,   'Reserved under your name. Please pick up within 3 business days. We\'ll send you a reminder.', now()->subHours(1)],
            [$customer->id, 'Picked them up — thanks again!', now()->subMinutes(35)],
            [$admin->id,   'Glad they worked out. Message us anytime if you need more.', now()->subMinutes(20)],
        ], readAll: true);

        // ── Closed: Frame repair
        $conv3 = Conversation::create([
            'user_id' => $customer->id,
            'subject' => 'Frame repair request',
            'status' => ConversationStatus::Closed,
            'last_message_at' => now()->subDays(1)->addHours(2),
        ]);

        $this->addMessages($conv3->id, [
            [$customer->id, 'Hello, one of the nose pads on my glasses came off. Can you fix it?', now()->subDays(2)->subHours(3)],
            [$staff->id,   'Hi Juan! Nose pad replacements are quick — usually free of charge. Just bring the frame in.', now()->subDays(2)->subHours(2)],
            [$customer->id, 'Wonderful, I\'ll pop by tomorrow morning.', now()->subDays(2)->subHour()],
            [$staff->id,   'We\'ll be happy to help. Ask for the technician at the front desk.', now()->subDays(1)],
            [$customer->id, 'All fixed — thanks!', now()->subDays(1)->addHour()],
            [$staff->id,   'Perfect. Reach out if anything else comes up.', now()->subDays(1)->addHours(2)],
        ], readAll: true);

        // ── Closed Conversation: Resolved billing query ───────────────────────
        $conv4 = Conversation::create([
            'user_id' => $customer->id,
            'subject' => 'Question about my last bill',
            'status' => ConversationStatus::Closed,
            'last_message_at' => now()->subDays(7),
        ]);

        $this->addMessages($conv4->id, [
            [$customer->id, 'Hi, I noticed my bill from last week shows a full price for the lenses. I was told there would be a PWD discount applied.', now()->subDays(10)->subHours(5)],
            [$staff->id,   'Hello Juan, I\'m sorry for the confusion! Let me check the records.', now()->subDays(10)->subHours(4)->subMinutes(55)],
            [$staff->id,   'You\'re right — the 20% PWD discount was not applied to the lenses. I\'ll escalate this to admin for a correction.', now()->subDays(10)->subHours(4)],
            [$admin->id,   'Hi Juan, I\'ve reviewed your bill and issued a corrected invoice with the 20% PWD discount. A refund of the difference has been noted for your next visit.', now()->subDays(9)->subHours(6)],
            [$customer->id, 'Thank you so much! That\'s a relief. I really appreciate how quickly this was resolved.', now()->subDays(9)->subHours(3)],
            [$admin->id,   'Happy to help! If you have any further questions, don\'t hesitate to reach out.', now()->subDays(7)],
        ], readAll: true);
    }

    /**
     * Bulk-insert messages for a conversation.
     *
     * @param  array<array{0: int, 1: string, 2: \Carbon\Carbon}>  $messages
     */
    private function addMessages(int $conversationId, array $messages, bool $readAll = false): void
    {
        foreach ($messages as $i => [$senderId, $body, $sentAt]) {
            // Mark all messages as read in closed conversations.
            // In open conversations, leave the last message by each non-staff
            // sender unread to simulate realistic inbox state.
            $isRead = $readAll || ($i < count($messages) - 1);

            Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'body' => $body,
                'is_read' => $isRead,
                'read_at' => $isRead ? $sentAt->addMinutes(rand(2, 10)) : null,
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ]);
        }
    }
}
