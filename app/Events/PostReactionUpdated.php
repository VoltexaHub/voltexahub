<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostReactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Post $post) {}

    public function broadcastOn(): array
    {
        return [new Channel('threads.'.$this->post->thread_id)];
    }

    public function broadcastAs(): string
    {
        return 'post.reaction';
    }

    public function broadcastWith(): array
    {
        $this->post->loadMissing('reactions');
        $counts = $this->post->reactions->groupBy('emoji')->map->count();

        return [
            'post_id' => $this->post->id,
            'counts' => $counts->all(),
        ];
    }
}
