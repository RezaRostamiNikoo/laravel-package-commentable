<?php

namespace Rostami\Commentable\App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rostami\Commentable\App\Models\Comment;
use Rostami\Commentable\App\Models\CommentCounter;

trait CanComment
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, "creator");
    }


    /////////
    public function scopeWasCommentedOn($query, Model $commentable)
    {
        $table   = $this->getTable();
        $builder = $query->getQuery();

        $builder->join('comments', 'comments.commenter_id', '=', "{$table}.id")
            ->where('comments.commenter_type', '=', get_class($this))
            ->where('comments.commentable_id', '=', $commentable->getKey())
            ->where('comments.commentable_type', '=', get_class($commentable));

        return $builder;
    }
    /**
     * TODO Optimize performance by reduce SQL query
     *
     * @return array
     */
    public function getCommentablesAttribute()
    {
        $relation = $this->hasMany(Comment::class, 'commenter_id');
        $relation->getQuery()->where('commenter_type', '=', get_class($this));

        return new Collection(array_map(function ($like) {
            return forward_static_call([$like['commentable_type'], 'find'], $like['commentable_id']);
        }, $relation->getResults()->toArray()));
    }
}
