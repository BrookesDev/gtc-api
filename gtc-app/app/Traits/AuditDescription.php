<?php

namespace App\Traits;

use App\Models\User;

trait AuditDescription
{
    public function getUser($id)
    {
        $user = User::find($id);
        return $user;
    }
    private function generateDescription(array $data, $user): string
    {

        $action = $data['event'];
        $modelName = class_basename($this);
        $name = $user->name ?? "";
        $description = "{$name} {$action} {$modelName}. ";

        $action = $data['event'];
        switch ($action) {
            case 'created':
                $description .= $this->formatAuditData($data['new_values']);
                break;
            case 'updated':
                $description .= "Previous (" . $this->formatAuditData($data['old_values']) . ") Later (" . $this->formatAuditData($data['new_values']) . ")";
                break;
            case 'deleted':
                $description .= "deleted data: " . $this->formatAuditData($data['old_values']);
                break;
            default:
                $description .= ".";
                break;
        }

        return $description;
    }

    private function formatAuditData(array $data): string
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[] = "$key: $value";
        }
        return implode(', ', $formatted);
    }
}
