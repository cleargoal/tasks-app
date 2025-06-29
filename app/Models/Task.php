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

/**
 * Task model representing a task in the system.
 *
 * This model stores all task-related data including title, description, status,
 * priority, due date, and completion date. It supports parent-child relationships
 * for subtasks and belongs to a specific user.
 *
 * @property int $id The unique identifier of the task
 * @property int $user_id The ID of the user who owns the task
 * @property int|null $parent_id The ID of the parent task, if this is a subtask
 * @property string $title The title of the task
 * @property string $description The detailed description of the task
 * @property StatusEnum $status The current status of the task (e.g., TODO, IN_PROGRESS, DONE)
 * @property PriorityEnum $priority The priority level of the task
 * @property Carbon|null $due_date The due date of the task
 * @property Carbon|null $completed_at The date when the task was completed
 * @property Carbon $created_at The date when the task was created
 * @property Carbon $updated_at The date when the task was last updated
 *
 * @method static Builder|static forUser(int $userId)
 * @method static Builder|static byPriority(PriorityEnum $priority)
 * @method static Builder|static byStatus(StatusEnum $status)
 * @method static Builder|static withTitleContaining(string $text)
 * @method static Builder|static withDescriptionContaining(string $text)
 * @method static Builder|static dueOn(Carbon $date)
 * @method static Builder|static completedOn(Carbon $date)
 * @method static Builder|static incomplete()
 * @method static Builder|static completed()
 * @method static Builder|static subtasksOf(int $taskId)
 * @method static Builder|static orderByField(string $field, string $direction = 'asc')
 */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => StatusEnum::class,
        'priority' => PriorityEnum::class,
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the parent task that this task belongs to.
     *
     * @return BelongsTo The parent task relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Get the user that owns the task.
     *
     * @return BelongsTo The user relationship
     */
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
