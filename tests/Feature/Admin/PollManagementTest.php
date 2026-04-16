<?php

namespace Tests\Feature\Admin;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function pollWithOptions(int $count = 3): Poll
    {
        $poll = Poll::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            $poll->options()->create([
                'text' => "Option {$i}",
                'position' => $i,
                'votes_count' => 0,
            ]);
        }
        return $poll->fresh('options');
    }

    public function test_admin_can_list_polls(): void
    {
        $this->pollWithOptions();

        $this->actingAs($this->admin())
            ->get('/admin/polls')
            ->assertOk();
    }

    public function test_non_admin_cannot_access_polls(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/polls')->assertForbidden();
    }

    public function test_update_renames_question_and_toggles_allow_multiple(): void
    {
        $poll = $this->pollWithOptions();

        $this->actingAs($this->admin())
            ->put("/admin/polls/{$poll->id}", [
                'question' => 'Updated question?',
                'allow_multiple' => true,
                'closes_at' => null,
                'options' => $poll->options->map(fn ($o) => ['id' => $o->id, 'text' => $o->text])->values()->all(),
            ])
            ->assertRedirect();

        $poll->refresh();
        $this->assertSame('Updated question?', $poll->question);
        $this->assertTrue($poll->allow_multiple);
    }

    public function test_update_preserves_vote_counts_on_kept_options(): void
    {
        $poll = $this->pollWithOptions();
        $first = $poll->options->first();
        $first->update(['votes_count' => 7]);

        // Keep the first option with the same id, rename the others.
        $payload = [
            'question' => $poll->question,
            'allow_multiple' => false,
            'closes_at' => null,
            'options' => [
                ['id' => $first->id, 'text' => 'Renamed but kept'],
                ['id' => $poll->options[1]->id, 'text' => 'Second'],
                ['id' => $poll->options[2]->id, 'text' => 'Third'],
            ],
        ];

        $this->actingAs($this->admin())
            ->put("/admin/polls/{$poll->id}", $payload)
            ->assertRedirect();

        $first->refresh();
        $this->assertSame('Renamed but kept', $first->text);
        $this->assertSame(7, $first->votes_count);
    }

    public function test_update_removes_dropped_options_and_their_votes(): void
    {
        $poll = $this->pollWithOptions();
        $dropped = $poll->options->last();

        // Plant a vote on the option we're about to drop.
        $voter = User::factory()->create();
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $dropped->id,
            'user_id' => $voter->id,
        ]);

        $payload = [
            'question' => $poll->question,
            'allow_multiple' => false,
            'closes_at' => null,
            'options' => [
                ['id' => $poll->options[0]->id, 'text' => $poll->options[0]->text],
                ['id' => $poll->options[1]->id, 'text' => $poll->options[1]->text],
            ],
        ];

        $this->actingAs($this->admin())
            ->put("/admin/polls/{$poll->id}", $payload)
            ->assertRedirect();

        $this->assertDatabaseMissing('poll_options', ['id' => $dropped->id]);
        $this->assertDatabaseMissing('poll_votes', ['poll_option_id' => $dropped->id]);
    }

    public function test_update_adds_new_options_with_zero_votes(): void
    {
        $poll = $this->pollWithOptions(2);

        $payload = [
            'question' => $poll->question,
            'allow_multiple' => false,
            'closes_at' => null,
            'options' => [
                ['id' => $poll->options[0]->id, 'text' => $poll->options[0]->text],
                ['id' => $poll->options[1]->id, 'text' => $poll->options[1]->text],
                ['id' => null, 'text' => 'Brand new option'],
            ],
        ];

        $this->actingAs($this->admin())
            ->put("/admin/polls/{$poll->id}", $payload)
            ->assertRedirect();

        $this->assertSame(3, $poll->options()->count());
        $added = $poll->options()->where('text', 'Brand new option')->first();
        $this->assertNotNull($added);
        $this->assertSame(0, $added->votes_count);
    }

    public function test_update_rejects_fewer_than_two_options(): void
    {
        $poll = $this->pollWithOptions();

        $this->actingAs($this->admin())
            ->put("/admin/polls/{$poll->id}", [
                'question' => $poll->question,
                'allow_multiple' => false,
                'closes_at' => null,
                'options' => [
                    ['id' => $poll->options[0]->id, 'text' => 'Only one left'],
                ],
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_destroy_deletes_poll(): void
    {
        $poll = $this->pollWithOptions();

        $this->actingAs($this->admin())
            ->delete("/admin/polls/{$poll->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('polls', ['id' => $poll->id]);
    }
}
