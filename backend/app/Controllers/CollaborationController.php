<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Services\CollaborationService;
use App\Services\TokenService;
use PDO;

final class CollaborationController
{
    private TokenService $tokens;
    private CollaborationService $service;

    public function __construct(PDO $database)
    {
        $this->tokens=new TokenService($database);$this->service=new CollaborationService($database);
    }

    public function comments(Request $r,array $p): Response
    {
        $u=$this->user($r);$w=$this->id($p['workspaceId']??null);$t=$this->id($p['taskId']??null);
        return Response::json(['success'=>true,'data'=>['comments'=>$this->service->comments((int)$u['id'],$w,$t)]]);
    }
    public function addComment(Request $r,array $p): Response
    {
        $u=$this->user($r);$w=$this->id($p['workspaceId']??null);$t=$this->id($p['taskId']??null);
        return Response::json(['success'=>true,'message'=>'Comment created.','data'=>['comment'=>$this->service->addComment((int)$u['id'],$w,$t,$r->body())]],201);
    }
    public function deleteComment(Request $r,array $p): Response
    {
        $u=$this->user($r);$this->service->deleteComment((int)$u['id'],$this->id($p['workspaceId']??null),$this->id($p['commentId']??null));
        return Response::json(['success'=>true,'message'=>'Comment deleted.']);
    }
    public function labels(Request $r,array $p): Response
    {
        $u=$this->user($r);return Response::json(['success'=>true,'data'=>['labels'=>$this->service->labels((int)$u['id'],$this->id($p['workspaceId']??null))]]);
    }
    public function createLabel(Request $r,array $p): Response
    {
        $u=$this->user($r);return Response::json(['success'=>true,'data'=>['label'=>$this->service->createLabel((int)$u['id'],$this->id($p['workspaceId']??null),$r->body())]],201);
    }
    public function updateLabel(Request $r,array $p): Response
    {
        $u=$this->user($r);return Response::json(['success'=>true,'data'=>['label'=>$this->service->updateLabel((int)$u['id'],$this->id($p['workspaceId']??null),$this->id($p['labelId']??null),$r->body())]]);
    }
    public function deleteLabel(Request $r,array $p): Response
    {
        $u=$this->user($r);$this->service->deleteLabel((int)$u['id'],$this->id($p['workspaceId']??null),$this->id($p['labelId']??null));return Response::json(['success'=>true,'message'=>'Label deleted.']);
    }
    public function taskLabels(Request $r,array $p): Response
    {
        $u=$this->user($r);return Response::json(['success'=>true,'data'=>['labels'=>$this->service->taskLabels((int)$u['id'],$this->id($p['workspaceId']??null),$this->id($p['taskId']??null))]]);
    }
    public function attachLabel(Request $r,array $p): Response
    {
        $u=$this->user($r);$body=$r->body();$label=$this->id($body['label_id']??null);
        return Response::json(['success'=>true,'data'=>['labels'=>$this->service->attachLabel((int)$u['id'],$this->id($p['workspaceId']??null),$this->id($p['taskId']??null),$label)]],201);
    }
    public function detachLabel(Request $r,array $p): Response
    {
        $u=$this->user($r);return Response::json(['success'=>true,'data'=>['labels'=>$this->service->detachLabel((int)$u['id'],$this->id($p['workspaceId']??null),$this->id($p['taskId']??null),$this->id($p['labelId']??null))]]);
    }
    public function activity(Request $r,array $p): Response
    {
        $u=$this->user($r);return Response::json(['success'=>true,'data'=>['activity'=>$this->service->activity((int)$u['id'],$this->id($p['workspaceId']??null),$r->query())]]);
    }

    private function user(Request $r): array
    {
        $header=$r->header('Authorization')??'';
        if(preg_match('/^Bearer\s+([a-f0-9]{64})$/i',trim($header),$m)!==1)throw new ApiException('A valid Bearer token is required.',401);
        return $this->tokens->authenticate($m[1]);
    }
    private function id(mixed $value): int
    {
        if((!is_string($value)&&!is_int($value))||filter_var($value,FILTER_VALIDATE_INT)===false||(int)$value<1)throw new ApiException('A valid resource identifier is required.',400);
        return (int)$value;
    }
}
