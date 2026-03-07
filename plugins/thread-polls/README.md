# Thread Polls Plugin

Add polls to forum threads. Thread authors and admins can attach a poll to any thread. Authenticated users can vote and see live results.

## API Endpoints

| Method   | Endpoint                      | Auth     | Description                                 |
|----------|-------------------------------|----------|---------------------------------------------|
| `POST`   | `/api/threads/{thread}/poll`  | Required | Create a poll (thread author or admin only)  |
| `GET`    | `/api/polls/{poll}`           | Optional | Get poll with vote counts and user vote info |
| `POST`   | `/api/polls/{poll}/vote`      | Required | Cast a vote                                 |
| `DELETE` | `/api/polls/{poll}/vote`      | Required | Remove your vote                            |

### Create Poll — `POST /api/threads/{thread}/poll`

```json
{
  "question": "What's your favorite framework?",
  "allow_multiple": false,
  "closes_at": "2026-04-01T00:00:00Z",
  "options": ["Laravel", "Rails", "Django", "Express"]
}
```

### Cast Vote — `POST /api/polls/{poll}/vote`

```json
{
  "option_ids": [1]
}
```

For `allow_multiple` polls, pass multiple IDs: `"option_ids": [1, 3]`.

### Thread Detail Response

When fetching a thread via `GET /api/threads/{id}`, the response includes a `poll` object if one exists:

```json
{
  "poll": {
    "id": 1,
    "question": "What's your favorite framework?",
    "allow_multiple": false,
    "closes_at": "2026-04-01T00:00:00.000000Z",
    "is_closed": false,
    "total_votes": 42,
    "user_voted_option_ids": [2],
    "options": [
      { "id": 1, "label": "Laravel", "sort_order": 0, "vote_count": 20, "vote_percentage": 47.6 },
      { "id": 2, "label": "Rails", "sort_order": 1, "vote_count": 12, "vote_percentage": 28.6 },
      { "id": 3, "label": "Django", "sort_order": 2, "vote_count": 7, "vote_percentage": 16.7 },
      { "id": 4, "label": "Express", "sort_order": 3, "vote_count": 3, "vote_percentage": 7.1 }
    ]
  }
}
```

---

## Frontend Components

### PollCreate.vue

Shown to the thread author in thread creation/edit view. Allows attaching a poll to the thread.

**Features:**
- Text input for the poll question
- Dynamic list of option inputs (2 minimum, 10 maximum) with add/remove buttons
- "Allow multiple answers" toggle switch
- Optional "Closes at" datetime picker
- Submit button calls `POST /api/threads/{threadId}/poll`
- Validation: question required, at least 2 non-empty options

**Placement:** Render inside the thread create/edit form, or as a separate section below the thread body editor. Only visible to the thread author or admins.

```vue
<template>
  <div class="poll-create">
    <h3>Add a Poll</h3>
    <input v-model="question" placeholder="Ask a question..." maxlength="500" />

    <div v-for="(option, i) in options" :key="i" class="poll-option-input">
      <input v-model="options[i]" :placeholder="'Option ' + (i + 1)" maxlength="255" />
      <button v-if="options.length > 2" @click="options.splice(i, 1)">Remove</button>
    </div>
    <button v-if="options.length < 10" @click="options.push('')">Add Option</button>

    <label>
      <input type="checkbox" v-model="allowMultiple" />
      Allow multiple answers
    </label>

    <label>
      Closes at (optional)
      <input type="datetime-local" v-model="closesAt" />
    </label>

    <button @click="submit" :disabled="!isValid">Create Poll</button>
  </div>
</template>
```

### PollDisplay.vue

Shown in the thread view below the first post when a poll exists.

**Features:**
- Displays the poll question as a heading
- Shows each option as a horizontal bar with:
  - Option label
  - Vote count and percentage
  - Animated progress bar (CSS transition on width)
  - Highlighted styling if the current user voted for that option
- Vote buttons on each option (or checkboxes for `allow_multiple` polls)
- After voting, options switch to results view automatically
- "Remove vote" link to retract your vote
- Total vote count displayed at the bottom
- If `closes_at` is set, show a countdown timer ("Closes in 2 days, 3 hours")
- If closed, show "Poll closed" badge and disable vote buttons

**Placement:** Render inside the thread detail view, directly below the first post / thread body.

```vue
<template>
  <div class="poll-display" v-if="poll">
    <h3>{{ poll.question }}</h3>

    <div v-for="option in poll.options" :key="option.id" class="poll-option">
      <div class="poll-bar-container">
        <div
          class="poll-bar"
          :style="{ width: option.vote_percentage + '%' }"
          :class="{ voted: hasVoted(option.id) }"
        />
        <span class="poll-label">{{ option.label }}</span>
        <span class="poll-stats">{{ option.vote_count }} ({{ option.vote_percentage }}%)</span>
      </div>
      <button
        v-if="canVote && !hasVotedAny"
        @click="vote(option.id)"
      >Vote</button>
    </div>

    <div class="poll-footer">
      <span>{{ poll.total_votes }} total votes</span>
      <button v-if="hasVotedAny && !poll.is_closed" @click="removeVote">Remove vote</button>
      <span v-if="poll.closes_at" class="poll-countdown">
        {{ poll.is_closed ? 'Poll closed' : 'Closes ' + timeUntil(poll.closes_at) }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.poll-bar {
  height: 32px;
  background: var(--primary-color, #3b82f6);
  border-radius: 4px;
  transition: width 0.6s ease;
}
.poll-bar.voted {
  background: var(--primary-color-dark, #2563eb);
}
</style>
```

**API calls:**
- Vote: `POST /api/polls/{pollId}/vote` with `{ option_ids: [id] }`
- Remove vote: `DELETE /api/polls/{pollId}/vote`
- Refresh poll: `GET /api/polls/{pollId}`
