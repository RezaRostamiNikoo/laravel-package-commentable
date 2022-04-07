<?php

namespace Rostami\Commentable\App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rostami\Commentable\App\Models\Comment;
use Rostami\Commentable\App\Models\CommentCounter;

trait WithCommenter
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, "commenter");
    }
}
