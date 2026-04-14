<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadSubscription;
use App\Models\User;
use App\Notifications\NewPrivateMessage;
use App\Notifications\NewThreadReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_muting_a_thread_skips_reply_notifications_for_that_user(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $muter = User::factory()->create();
        $replier = User::factory()->create();

        $forum = Forum::factory()->create(['slug' => 'general']);
        $thread = Thread::factory()
            ->for($forum)
            ->for($author, 'author')
            ->create(['slug' => 'mute-me']);
        Post::factory()->for($thread)->for($author, 'author')->create();
        Post::factory()->for($thread)->for($muter, 'author')->create();

        ThreadSubscription::create([
            'user_id' => $muter->id,
            'thread_id' => $thread->id,
            'state' => ThreadSubscription::STATE_MUTED,
        ]);

        $this->actingAs($replier)
            ->post("/forums/{$forum->slug}/threads/{$thread->slug}/posts", ['body' => 'hello'])
            ->assertRedirect();

        Notification::assertSentTo($author, NewThreadReply::class);
        Notification::assertNotSentTo($muter, NewThreadReply::class);
    }

    public function test_mute_and_unmute_endpoints_toggle_subscription(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $thread = Thread::factory()->for($forum)->for($user, 'author')->create();

        $this->actingAs($user)->post(route('threads.mute', $thread))->assertRedirect();
        $this->assertDatabaseHas('thread_subscriptions', [
            'user_id' => $user->id, 'thread_id' => $thread->id, 'state' => 'muted',
        ]);

        $this->actingAs($user)->post(route('threads.unmute', $thread))->assertRedirect();
        $this->assertDatabaseMissing('thread_subscriptions', [
            'user_id' => $user->id, 'thread_id' => $thread->id,
        ]);
    }

    public function test_reply_preferences_control_the_notification_channels(): void
    {
        Notification::fake();

        $author = User::factory()->create([
            'notify_reply_email' => false,
            'notify_reply_app' => true,
        ]);
        $replier = User::factory()->create();

        $forum = Forum::factory()->create();
        $thread = Thread::factory()->for($forum)->for($author, 'author')->create();
        Post::factory()->for($thread)->for($author, 'author')->create();

        $this->actingAs($replier)
            ->post("/forums/{$forum->slug}/threads/{$thread->slug}/posts", ['body' => 'hi'])
            ->assertRedirect();

        Notification::assertSentTo($author, NewThreadReply::class, function ($notification, $channels) {
            return $channels === ['database'];
        });
    }

    public function test_opting_out_of_both_pm_channels_sends_no_notification(): void
    {
        Notification::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'notify_pm_email' => false,
            'notify_pm_app'   => false,
        ]);

        $this->actingAs($sender)->post('/messages', [
            'recipient_id' => $recipient->id,
            'body' => 'hello',
        ])->assertRedirect();

        // With both channels disabled, Laravel skips dispatch entirely.
        Notification::assertNotSentTo($recipient, NewPrivateMessage::class);
    }

    public function test_preferences_endpoint_updates_booleans(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.notifications.update'), [
                'notify_reply_email' => false,
                'notify_pm_app' => false,
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->notify_reply_email);
        $this->assertFalse($user->notify_pm_app);
        // Checkboxes left unchecked are absent from form data and should default to false.
        $this->assertFalse($user->notify_reply_app);
        $this->assertFalse($user->notify_pm_email);
    }
}
