<?php

namespace App\Services\Messaging;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\Media\OptimizedImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageDeliveryService
{
    public function __construct(
        protected OptimizedImageUploadService $imageUploadService
    ) {}

    /**
     * Send a message in a conversation with safe file cleanup on failure.
     *
     * @param  array<string, mixed>  $validated
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        User $receiver,
        array $validated,
        ?UploadedFile $file = null,
        ?int $extractedDuration = null
    ): Message {
        $path = null;
        if ($file instanceof UploadedFile) {
            $path = $this->imageUploadService->store($file, 'conversations/'.$conversation->id);
        }

        try {
            $data = [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'reply_to_id' => $validated['reply_to_id'] ?? null,
                'type' => $validated['type'],
                'message' => $validated['message'] ?? null,
                'duration' => $validated['duration'] ?? $extractedDuration,
            ];

            if ($path && $file instanceof UploadedFile) {
                $data['file_path'] = $path;
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
            }

            /** @var Message $message */
            $message = DB::transaction(function () use ($data, $conversation, $sender, $receiver): Message {
                $newMessage = Message::create($data);

                $conversation->restoreFor($sender->id);
                $conversation->restoreFor($receiver->id);
                $conversation->update(['last_message_id' => $newMessage->id]);

                return $newMessage;
            });

            $message->load('replyTo.sender:id,first_name,last_name');

            event(new MessageSent($message));

            if (! $conversation->isArchivedFor($receiver->id)) {
                $unreadSummary = Conversation::unreadSummaryFor($receiver->id);

                event(new ConversationUpdated(
                    $conversation,
                    $receiver,
                    $conversation->unreadCountFor($receiver->id),
                    $message,
                    $unreadSummary['unread_conversation_count'],
                    $unreadSummary['total_unread_messages'],
                ));

                $receiver->notify(new NewMessageNotification($message));
            }

            return $message;
        } catch (\Throwable $e) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }
}
