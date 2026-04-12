<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StartConversationRequest;
use App\Http\Resources\V1\ConversationResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * List conversations (role-aware — customers see own, staff/admin see all).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $conversations = $this->conversationService->listForUser(
            user: $request->user(),
            filters: [],
            perPage: $perPage,
        );

        return response()->json([
            'data' => ConversationResource::collection($conversations),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * Ensure the customer's single message thread exists (idempotent).
     */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $conversation = $this->conversationService->startConversation(
            user: $request->user(),
            data: $request->validated(),
        );

        return response()->json([
            'message' => 'Conversation ready.',
            'conversation' => new ConversationResource($conversation),
        ], 201);
    }

    /**
     * View a single conversation.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation = $this->conversationService->getConversation(
            user: $request->user(),
            conversation: $conversation,
        );

        return response()->json([
            'conversation' => new ConversationResource($conversation),
        ]);
    }

    /**
     * Unread message count for the authenticated user (role-aware).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->conversationService->getUnreadCount($request->user());

        return response()->json(['unread_count' => $count]);
    }
}
