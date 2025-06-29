<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
        'priority' => PriorityEnum::class,
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include tasks for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter tasks by priority.
     */
    public function scopeByPriority(Builder $query, PriorityEnum $priority): Builder
    {
        return $query->where('priority', $priority->value);
    }

    /**
     * Scope a query to filter tasks by status.
     */
    public function scopeByStatus(Builder $query, StatusEnum $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope a query to filter tasks by title containing a specific text.
     */
    public function scopeWithTitleContaining(Builder $query, string $text): Builder
    {
        return $query->where('title', 'like', '%' . $text . '%');
    }

    /**
     * Scope a query to filter tasks by description containing a specific text.
     */
    public function scopeWithDescriptionContaining(Builder $query, string $text): Builder
    {
        return $query->where('description', 'like', '%' . $text . '%');
    }

    /**
     * Scope a query to filter tasks by due date.
     */
    public function scopeDueOn(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('due_date', $date->toDateString());
    }

    /**
     * Scope a query to filter tasks by completion date.
     */
    public function scopeCompletedOn(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('completed_at', $date->toDateString());
    }

    /**
     * Scope a query to filter tasks that are incomplete.
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('status', StatusEnum::TODO->value);
    }

    /**
     * Scope a query to filter tasks that are completed.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', StatusEnum::DONE->value);
    }

    /**
     * Scope a query to filter tasks that are subtasks of a specific task.
     */
    public function scopeSubtasksOf(Builder $query, int $taskId): Builder
    {
        return $query->where('parent_id', $taskId);
    }

    /**
     * Scope a query to order tasks by a specific field.
     */
    public function scopeOrderByField(Builder $query, string $field, string $direction = 'asc'): Builder
    {
        return $query->orderBy($field, $direction);
    }
}
