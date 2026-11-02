<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\CollaborationRepository;
use App\Repositories\WorkspaceRepository;
use App\Validators\Validator;
use PDO;
use PDOException;

final class CollaborationService
{
    private CollaborationRepository $data;
    private WorkspaceRepository $workspaces;

    public function __construct(private readonly PDO $database)
    {
        $this->data=new CollaborationRepository($database);
        $this->workspaces=new WorkspaceRepository($database);
    }

    public function comments(int $userId,int $workspaceId,int $taskId): array
    {
        $this->member($userId,$workspaceId);$this->task($taskId,$workspaceId);
        return $this->data->listComments($taskId);
    }

    public function addComment(int $userId,int $workspaceId,int $taskId,array $input): array
    {
        $this->member($userId,$workspaceId);$this->task($taskId,$workspaceId);
        $errors=Validator::validate($input,['body'=>['required','string','min:1','max:5000']]);
        if(isset($input['body'])&&is_string($input['body'])&&trim($input['body'])==='')$errors['body'][]='Comment cannot be empty.';
        if($errors!==[])throw ApiException::validation($errors);
        $body=trim($input['body']);
        $this->database->beginTransaction();
        try{$id=$this->data->addComment($taskId,$userId,$body);$this->data->logActivity($workspaceId,$userId,'comment',$id,'comment.created',['task_id'=>$taskId]);$this->database->commit();}
        catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
        return ['id'=>$id,'task_id'=>$taskId,'user_id'=>$userId,'body'=>$body];
    }

    public function deleteComment(int $userId,int $workspaceId,int $commentId): void
    {
        $membership=$this->member($userId,$workspaceId);$comment=$this->data->findComment($commentId);
        if($comment===null||$this->data->taskInWorkspace((int)$comment['task_id'],$workspaceId)===null)throw new ApiException('Comment not found.',404);
        if((int)$comment['user_id']!==$userId&&!in_array($membership['role'],['owner','admin'],true))throw new ApiException('You cannot delete this comment.',403);
        $this->database->beginTransaction();
        try{$this->data->logActivity($workspaceId,$userId,'comment',$commentId,'comment.deleted',['task_id'=>(int)$comment['task_id']]);$this->data->deleteComment($commentId);$this->database->commit();}
        catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
    }

    public function labels(int $userId,int $workspaceId): array
    {
        $this->member($userId,$workspaceId);return $this->data->listLabels($workspaceId);
    }

    public function createLabel(int $userId,int $workspaceId,array $input): array
    {
        $this->manager($userId,$workspaceId);$errors=Validator::validate($input,['name'=>['required','string','min:2','max:60'],'color'=>['string']]);
        $color=$input['color']??'#64748b';
        if(!is_string($color)||preg_match('/^#[0-9a-fA-F]{6}$/',$color)!==1)$errors['color'][]='Use a hexadecimal color such as #64748b.';
        if($errors!==[])throw ApiException::validation($errors);
        try{$id=$this->data->createLabel($workspaceId,trim($input['name']),$color);}
        catch(PDOException $e){if($e->getCode()==='23000')throw new ApiException('A label with this name already exists in the workspace.',409);throw $e;}
        $this->data->logActivity($workspaceId,$userId,'label',$id,'label.created',['name'=>trim($input['name'])]);
        return ['id'=>$id,'workspace_id'=>$workspaceId,'name'=>trim($input['name']),'color'=>$color];
    }

    public function updateLabel(int $userId,int $workspaceId,int $labelId,array $input): array
    {
        $this->manager($userId,$workspaceId);$this->label($labelId,$workspaceId);
        $errors=Validator::validate($input,['name'=>['string','min:2','max:60'],'color'=>['string']]);
        if($input===[])throw new ApiException('Provide at least one label field to update.',422);
        if(array_key_exists('color',$input)&&(!is_string($input['color'])||preg_match('/^#[0-9a-fA-F]{6}$/',$input['color'])!==1))$errors['color'][]='Use a hexadecimal color such as #64748b.';
        if($errors!==[])throw ApiException::validation($errors);
        $data=[];if(array_key_exists('name',$input))$data['name']=trim($input['name']);if(array_key_exists('color',$input))$data['color']=$input['color'];
        try{$this->data->updateLabel($labelId,$workspaceId,$data);}
        catch(PDOException $e){if($e->getCode()==='23000')throw new ApiException('A label with this name already exists in the workspace.',409);throw $e;}
        $this->data->logActivity($workspaceId,$userId,'label',$labelId,'label.updated',$data);
        return $this->label($labelId,$workspaceId);
    }

    public function deleteLabel(int $userId,int $workspaceId,int $labelId): void
    {
        $this->manager($userId,$workspaceId);$label=$this->label($labelId,$workspaceId);
        $this->database->beginTransaction();
        try{$this->data->logActivity($workspaceId,$userId,'label',$labelId,'label.deleted',['name'=>$label['name']]);$this->data->deleteLabel($labelId,$workspaceId);$this->database->commit();}
        catch(\Throwable $e){if($this->database->inTransaction())$this->database->rollBack();throw $e;}
    }

    public function taskLabels(int $userId,int $workspaceId,int $taskId): array
    {
        $this->member($userId,$workspaceId);$this->task($taskId,$workspaceId);return $this->data->listTaskLabels($taskId);
    }

    public function attachLabel(int $userId,int $workspaceId,int $taskId,int $labelId): array
    {
        $this->member($userId,$workspaceId);$this->task($taskId,$workspaceId);$label=$this->label($labelId,$workspaceId);
        try{$this->data->attachLabel($taskId,$labelId);}
        catch(PDOException $e){if($e->getCode()==='23000')throw new ApiException('This label is already assigned to the task.',409);throw $e;}
        $this->data->logActivity($workspaceId,$userId,'task',$taskId,'task.label_attached',['label_id'=>$labelId,'label_name'=>$label['name']]);
        return $this->data->listTaskLabels($taskId);
    }

    public function detachLabel(int $userId,int $workspaceId,int $taskId,int $labelId): array
    {
        $this->member($userId,$workspaceId);$this->task($taskId,$workspaceId);$label=$this->label($labelId,$workspaceId);
        $this->data->detachLabel($taskId,$labelId);
        $this->data->logActivity($workspaceId,$userId,'task',$taskId,'task.label_detached',['label_id'=>$labelId,'label_name'=>$label['name']]);
        return $this->data->listTaskLabels($taskId);
    }

    public function activity(int $userId,int $workspaceId,array $query): array
    {
        $this->member($userId,$workspaceId);
        $limit=isset($query['limit'])?filter_var($query['limit'],FILTER_VALIDATE_INT):20;
        $before=isset($query['before_id'])?filter_var($query['before_id'],FILTER_VALIDATE_INT):0;
        if($limit===false||$limit<1||$limit>100||$before===false||$before<0)throw ApiException::validation(['pagination'=>['limit must be 1-100 and before_id must be a positive identifier.']]);
        $items=$this->data->listActivity($workspaceId,$limit,$before);
        foreach($items as &$item){if($item['metadata']!==null)$item['metadata']=json_decode($item['metadata'],true)??[];}
        unset($item);return $items;
    }

    private function member(int $userId,int $workspaceId): array
    {
        $m=$this->workspaces->findMembership($workspaceId,$userId);
        if($m===null)throw new ApiException('Workspace not found.',404);return $m;
    }
    private function manager(int $userId,int $workspaceId): array
    {
        $m=$this->member($userId,$workspaceId);
        if(!in_array($m['role'],['owner','admin'],true))throw new ApiException('Only workspace owners and admins can manage labels.',403);return $m;
    }
    private function task(int $taskId,int $workspaceId): array
    {
        $t=$this->data->taskInWorkspace($taskId,$workspaceId);
        if($t===null)throw new ApiException('Task not found.',404);return $t;
    }
    private function label(int $labelId,int $workspaceId): array
    {
        $l=$this->data->findLabel($labelId,$workspaceId);
        if($l===null)throw new ApiException('Label not found.',404);return $l;
    }
}
