<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Http\Resources\V1\ConversationResource;
use App\Http\Resources\V1\MessageResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * List messages in a conversation (oldest first) and mark unread ones as read.
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $this->messageService->listMessages(
            user: $request->user(),
            conversation: $conversation,
        );

        return response()->json([
            'data' => MessageResource::collection($messages),
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $message = $this->messageService->sendMessage(
            user: $request->user(),
            conversation: $conversation,
            data: $request->validated(),
        );

        return response()->json([
            'message' => 'Message sent.',
            'data' => new MessageResource($message),
        ], 201);
    }

    /**
     * Customer-only: send to their single persistent thread, creating the conversation row
     * on the first message if needed.
     */
    public function storeForMyConversation(SendMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isCustomer(), 403);

        $conversation = $this->conversationService->getOrCreateForCustomer($user, []);

        $this->authorize('sendMessage', $conversation);

        $message = $this->messageService->sendMessage(
            user: $user,
            conversation: $conversation,
            data: $request->validated(),
        );

        $conversation->refresh()->loadCount('messages');

        return response()->json([
            'message' => 'Message sent.',
            'data' => new MessageResource($message),
            'conversation' => new ConversationResource($conversation),
        ], 201);
    }
}
