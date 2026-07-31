<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\ResolveChatDirectory;
use App\Chat\Actions\GetConversationMessages;
use App\Chat\Actions\ListConversations;
use App\Chat\Actions\MarkConversationRead;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Http\Controllers\Controller;
use App\Http\Resources\Chat\ConversationResource;
use App\Http\Resources\Chat\MessageResource;
use App\Models\User;
use App\Support\Chat\ChatDirectory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    /**
     * The inbox, with no conversation open.
     */
    public function index(
        Request $request,
        ListConversations $listConversations,
        ResolveChatDirectory $resolveChatDirectory,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $conversations = $listConversations->handle($user->id);

        return Inertia::render('chat/Index', [
            'conversations' => $this->propsFromDirectory(
                $request,
                $conversations,
                $resolveChatDirectory->handle($conversations->items()),
                $user->id,
            ),
            'conversation' => null,
            'messages' => null,
        ]);
    }

    /**
     * The inbox with one conversation open. Renders the same page component as
     * index so moving between conversations keeps the list in place.
     */
    public function show(
        Request $request,
        Conversation $conversation,
        ListConversations $listConversations,
        GetConversationMessages $getConversationMessages,
        MarkConversationRead $markConversationRead,
        ResolveChatDirectory $resolveChatDirectory,
    ): Response {
        Gate::authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();

        // Opening a conversation is what marks it read. Done before the inbox
        // query so the unread badge reflects this visit.
        $markConversationRead->handle($conversation, $user->id);

        $conversations = $listConversations->handle($user->id);
        $conversation->loadMissing('participants');

        // One directory for the inbox and the open conversation together, so
        // opening a conversation costs no extra identity resolution.
        $directory = $resolveChatDirectory->handle(
            [...$conversations->items(), $conversation],
        );

        $messages = $getConversationMessages->handle($conversation);

        return Inertia::render('chat/Index', [
            'conversations' => $this->propsFromDirectory($request, $conversations, $directory, $user->id),
            'conversation' => (new ConversationResource($conversation, $directory, $user->id))->toArray($request),
            'messages' => $messages->through(
                fn (Message $message) => (new MessageResource($message))->toArray($request),
            ),
        ]);
    }

    /**
     * Mapped through the paginator so the flat shape the Vue `Paginated<T>` type
     * expects is preserved (.ai/guidelines/api-architecture).
     *
     * toArray() rather than resolve(): resolve() is typed as a bare `array`,
     * while toArray() is declared array<string, mixed>. Neither resource here
     * uses conditional attributes, so there is no MissingValue to filter out.
     *
     * @param  LengthAwarePaginator<int, Conversation>  $conversations
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function propsFromDirectory(
        Request $request,
        LengthAwarePaginator $conversations,
        ChatDirectory $directory,
        string $viewerId,
    ): LengthAwarePaginator {
        return $conversations->through(
            fn (Conversation $conversation) => (new ConversationResource($conversation, $directory, $viewerId))->toArray($request),
        );
    }
}
