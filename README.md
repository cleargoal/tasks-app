# Smart Tasks API Documentation

## Overview
Smart Tasks is a task management application built with Laravel. The API allows you to manage tasks, including creating, updating, filtering, and organizing them by priority and status.

## Installation
### Prerequisites
- Docker & Docker Compose
- Git

### Step 1: Clone the Repository
bash git clone git@github.com:cleargoal/tasks-app.git smart-tasks

### Step 2: Install Dependencies

```
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### Step 3: Environment Configuration
#### Copy environment file
`cp .env.example .env`
#### Generate application key
`./vendor/bin/sail artisan key:generate`

### Step 4: Start the Application
#### Start all services in containers
`./vendor/bin/sail up -d`
#### Run database migrations and seeding
`./vendor/bin/sail artisan migrate --seed`

## Testing

### Setting up the sail alias (optional but recommended)

For easier command execution, you can set up a bash alias for sail.

Add this line to your ~/.bashrc or ~/.zshrc file:

`alias sail='./vendor/bin/sail'`

Then reload your shell:

`source ~/.bashrc`

### Running Tests

Run all tests:

`./vendor/bin/sail artisan test`

or with alias:

`sail artisan test`

Run tests with code coverage report:

`./vendor/bin/sail artisan test --coverage`

or with alias:

`sail artisan test --coverage`

The coverage report will show which parts of your code are covered by tests, helping you identify areas that need more testing.

### Code Quality Tools

#### Code Style with Laravel Pint
Check and fix code style issues:

`./vendor/bin/sail pint`

or with alias:

`sail pint`

Check code style without making changes (dry run):

`./vendor/bin/sail pint --test`

or with alias:

`sail pint --test`

#### Static Analysis with PHPStan
Run PHPStan to catch potential bugs and type errors:

`./vendor/bin/sail exec laravel.test ./vendor/bin/phpstan analyse`

or with alias:

`sail exec laravel.test ./vendor/bin/phpstan analyse`

### Pre-commit Checklist
Before committing your changes, it's recommended to run:
1. `sail pint` - Fix code style issues
2. `sail artisan test` - Ensure all tests pass
3. `sail exec laravel.test ./vendor/bin/phpstan analyse` - Check for static analysis issues

## OpenAPI Documentation
A detailed OpenAPI (version 3.0.3) specification is available in `openapi.yaml`. This file contains complete API schema definitions, request/response examples, and detailed parameter descriptions. You can use this file with any OpenAPI-compatible tool for a more interactive documentation experience.

## Authentication
All API endpoints except registration and login require Bearer token authentication.

### Registration
Register a new user:
`POST /api/register`

Required fields:
- name: Your full name
- email: Your email address
- password: Your password
- password_confirmation: Repeat your password

### Login
Login to get authentication token:
`POST /api/login`

Required fields:
- email: Your email address
- password: Your password

## Tasks

### Task Priority Levels
- 1: Highest
- 2: High
- 3: Medium
- 4: Low
- 5: Lowest

### Task Status Options
- todo: Task to be done
- done: Completed task

### Create Task
Create a new task:
`POST /api/tasks`

Fields:
- 'title': Task title (required)
- 'description': Task description (optional)
- 'priority': Priority level 1-5 (optional, default: 5)
- 'status': todo/done (optional, default: todo)
- 'due_date': Due date in YYYY-MM-DD format (optional)

### Update Task
Update existing task:
`PUT /api/tasks/{id}`

Available fields (all optional):
- title: New task title
- description: New task description
- priority: New priority level (1-5)
- due_date: New due date (YYYY-MM-DD)

### Filtering Tasks
The API supports various filtering options through query parameters.

Examples:

Filter by title:
`GET /api/tasks?filters[title]="Project"`

Filter by priority and status:
`GET /api/tasks?filters[priority]=1&filters[status]="todo"`

Filter with sorting:
`GET /api/tasks?filters[status]="todo"&sort=due_date:asc`

Available filters:
- filters[title]: Filter by task title
- filters[description]: Filter by task description
- filters[priority]: Filter by priority (1-5)
- filters[status]: Filter by status (todo/done)
- filters[due_date]: Filter by due date (YYYY-MM-DD)
- filters[completed_at]: Filter by completion date (YYYY-MM-DD)

### Sorting
Sort tasks using the sort parameter:
`GET /api/tasks?sort=priority:asc,due_date:desc`

Available sort fields:
- created_at
- title
- priority
- due_date
- status
- completed_at

Sort directions:
- asc: Ascending
- desc: Descending

### Complete Task
Mark a task as completed:
`POST /api/tasks/{id}/complete`

### Delete Task
Delete a task:
`DELETE /api/tasks/{id}`

Note: You cannot delete completed tasks (tasks with status "done"). Only tasks with status "todo" can be deleted.
