<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Todo;

class TodoController extends Controller {
    private ?array $requestData = null;

    private function getRequestData($key) {
        if ($this->requestData === null) {
            $input = file_get_contents('php://input');
            $this->requestData = json_decode($input, true) ?? [];
        }
        return $this->requestData[$key] ?? ($_POST[$key] ?? null);
    }

    public function index(): never {
        $todoModel = new Todo();
        $todos = $todoModel->all();
        $this->json([
            'status' => 'success',
            'data' => $todos
        ]);
    }

    public function store(): never {
        if (!$this->getRequestData('title')) {
            $this->json([
                'status' => 'error',
                'message' => 'Title is required'
            ], 400);
        }

        try {
            $todoModel = new Todo();
            $data = [
                'title' => $this->getRequestData('title'),
                'description' => $this->getRequestData('description') ?? '',
                'completed' => false
            ];

            foreach ($data as $key => $value) {
                $todoModel->$key = $value;
            }

            if (!$todoModel->save()) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Failed to create todo: ' . ($todoModel->getError() ?? 'Unknown error')
                ], 500);
            }

            // Get the created todo
            $newTodo = $todoModel->find((int)$todoModel->id);

            $this->json([
                'status' => 'success',
                'message' => 'Todo created successfully',
                'data' => $newTodo
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Failed to create todo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): never {
        $todoModel = new Todo();
        $todo = $todoModel->find((int)$id);

        if (!$todo) {
            $this->json([
                'status' => 'error',
                'message' => 'Todo not found'
            ], 404);
        }

        $this->json([
            'status' => 'success',
            'data' => $todo
        ]);
    }

    public function update(string $id): never {
        $todoModel = new Todo();
        $todo = $todoModel->find((int)$id);

        if (!$todo) {
            $this->json([
                'status' => 'error',
                'message' => 'Todo not found'
            ], 404);
        }

        try {
            $updateData = [];

            if (($title = $this->getRequestData('title')) !== null) {
                $updateData['title'] = $title;
            }

            if (($description = $this->getRequestData('description')) !== null) {
                $updateData['description'] = $description;
            }

            if (($completed = $this->getRequestData('completed')) !== null) {
                $updateData['completed'] = (bool) $completed;
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                if (!$todoModel->update($updateData, ['id' => (int)$id])) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Failed to update todo'
                    ], 500);
                }
            }

            // Get updated todo
            $updatedTodo = $todoModel->find((int)$id);

            $this->json([
                'status' => 'success',
                'message' => 'Todo updated successfully',
                'data' => $updatedTodo
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Failed to update todo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): never {
        $todoModel = new Todo();
        $todo = $todoModel->find((int)$id);

        if (!$todo) {
            $this->json([
                'status' => 'error',
                'message' => 'Todo not found'
            ], 404);
        }

        try {
            if (!$todoModel->delete(['id' => (int)$id])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Failed to delete todo: ' . ($todoModel->getError() ?? 'Unknown error')
                ], 500);
            }

            $this->json([
                'status' => 'success',
                'message' => 'Todo deleted successfully'
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Failed to delete todo: ' . $e->getMessage()
            ], 500);
        }
    }
}