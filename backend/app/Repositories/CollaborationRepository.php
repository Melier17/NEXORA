<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CollaborationRepository
{
    public function __construct(private readonly PDO $database) {}

    public function taskInWorkspace(int $taskId, int $workspaceId): ?array
    {
        $q = $this->database->prepare('SELECT t.id, t.project_id FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.id=:task AND p.workspace_id=:workspace LIMIT 1');
        $q->execute(['task'=>$taskId,'workspace'=>$workspaceId]);
        $row=$q->fetch();
        return $row===false?null:$row;
    }

    public function listComments(int $taskId): array
    {
        $q=$this->database->prepare('SELECT c.id,c.task_id,c.user_id,c.body,c.created_at,c.updated_at,u.name AS author_name FROM comments c JOIN users u ON u.id=c.user_id WHERE c.task_id=:task ORDER BY c.created_at ASC,c.id ASC');
        $q->execute(['task'=>$taskId]); return $q->fetchAll();
    }

    public function addComment(int $taskId,int $userId,string $body): int
    {
        $q=$this->database->prepare('INSERT INTO comments(task_id,user_id,body) VALUES(:task,:user,:body)');
        $q->execute(['task'=>$taskId,'user'=>$userId,'body'=>$body]); return (int)$this->database->lastInsertId();
    }

    public function findComment(int $commentId): ?array
    {
        $q=$this->database->prepare('SELECT id,task_id,user_id,body FROM comments WHERE id=:id LIMIT 1');
        $q->execute(['id'=>$commentId]); $r=$q->fetch(); return $r===false?null:$r;
    }

    public function deleteComment(int $commentId): void
    {
        $q=$this->database->prepare('DELETE FROM comments WHERE id=:id'); $q->execute(['id'=>$commentId]);
    }

    public function listLabels(int $workspaceId): array
    {
        $q=$this->database->prepare('SELECT id,workspace_id,name,color,created_at FROM labels WHERE workspace_id=:workspace ORDER BY name');
        $q->execute(['workspace'=>$workspaceId]); return $q->fetchAll();
    }

    public function createLabel(int $workspaceId,string $name,string $color): int
    {
        $q=$this->database->prepare('INSERT INTO labels(workspace_id,name,color) VALUES(:workspace,:name,:color)');
        $q->execute(['workspace'=>$workspaceId,'name'=>$name,'color'=>$color]); return (int)$this->database->lastInsertId();
    }

    public function findLabel(int $labelId,int $workspaceId): ?array
    {
        $q=$this->database->prepare('SELECT id,workspace_id,name,color FROM labels WHERE id=:id AND workspace_id=:workspace LIMIT 1');
        $q->execute(['id'=>$labelId,'workspace'=>$workspaceId]); $r=$q->fetch(); return $r===false?null:$r;
    }

    public function updateLabel(int $labelId,int $workspaceId,array $data): void
    {
        $sets=[];$params=['id'=>$labelId,'workspace'=>$workspaceId];
        foreach(['name','color'] as $field){if(array_key_exists($field,$data)){$sets[]="$field=:$field";$params[$field]=$data[$field];}}
        if($sets===[])return;
        $q=$this->database->prepare('UPDATE labels SET '.implode(',',$sets).' WHERE id=:id AND workspace_id=:workspace');$q->execute($params);
    }

    public function deleteLabel(int $labelId,int $workspaceId): void
    {
        $q=$this->database->prepare('DELETE FROM labels WHERE id=:id AND workspace_id=:workspace');$q->execute(['id'=>$labelId,'workspace'=>$workspaceId]);
    }

    public function listTaskLabels(int $taskId): array
    {
        $q=$this->database->prepare('SELECT l.id,l.name,l.color FROM task_labels tl JOIN labels l ON l.id=tl.label_id WHERE tl.task_id=:task ORDER BY l.name');
        $q->execute(['task'=>$taskId]);return $q->fetchAll();
    }

    public function attachLabel(int $taskId,int $labelId): void
    {
        $q=$this->database->prepare('INSERT INTO task_labels(task_id,label_id) VALUES(:task,:label)');$q->execute(['task'=>$taskId,'label'=>$labelId]);
    }

    public function detachLabel(int $taskId,int $labelId): void
    {
        $q=$this->database->prepare('DELETE FROM task_labels WHERE task_id=:task AND label_id=:label');$q->execute(['task'=>$taskId,'label'=>$labelId]);
    }

    public function logActivity(int $workspaceId,int $userId,string $type,int $entityId,string $action,array $metadata=[]): void
    {
        $q=$this->database->prepare('INSERT INTO activity_logs(workspace_id,actor_user_id,entity_type,entity_id,action,metadata) VALUES(:workspace,:actor,:type,:entity,:action,:metadata)');
        $q->execute(['workspace'=>$workspaceId,'actor'=>$userId,'type'=>$type,'entity'=>$entityId,'action'=>$action,'metadata'=>$metadata===[]?null:json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }

    public function listActivity(int $workspaceId,int $limit,int $beforeId): array
    {
        $q=$this->database->prepare('SELECT a.id,a.actor_user_id,a.entity_type,a.entity_id,a.action,a.metadata,a.created_at,u.name AS actor_name FROM activity_logs a LEFT JOIN users u ON u.id=a.actor_user_id WHERE a.workspace_id=:workspace AND (:before_id=0 OR a.id<:before_id) ORDER BY a.id DESC LIMIT '.$limit);
        $q->execute(['workspace'=>$workspaceId,'before_id'=>$beforeId]);
        return $q->fetchAll();
    }
}
