<?php

namespace Rostami\Commentable\App\Traits;


use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Rostami\Commentable\App\Models\Comment;
use Rostami\Commentable\App\Models\CommentCounter;

trait WithCommentable
{
    public static function bootWithCommentable()
    {
        if (static::removeCommentsOnDelete()) {
            static::deleting(function ($model) {
                $model->removeComments();
            });
        }
    }

    public static function removeCommentsOnDelete()
    {
        return isset(static::$removeCommentsOndelete)
            ? static::$removeCommentsOndelete
            : true;
    }


    public function removeComments()
    {
        Comment::where('commentable_type', static::class)
            ->where('commentable_id', $this[$this->getKey()])
            ->delete();

        CommentCounter::where('commentable_type', static::class)
            ->where('commentable_id', $this[$this->getKey()])
            ->delete();
    }


    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, "commentable");
    }


    /**
     * Counter is a record that stores the total comments for the
     * morphed record
     */
    public function commentCounter(): MorphTo
    {
        return $this->morphTo(CommentCounter::class, 'commentable');
    }


    /**
     * Fetch records that are commented by a given user.
     * Ex: Comment::likedBy(123)->get();
     */
    public function scopeCommentedBy($query, $commenter = null)
    {
        if (is_null($commenter)) {
            $commenter = $this->loggedInUser();
        }

        return $query->whereHas('comments', function ($q) use ($commenter) {
            $q->where("commenter_type", "=", get_class($commenter))
                ->where('commenter_id', '=', $commenter[$commenter->getKey()]);
        });
    }

    /**
     * Fetch the primary ID of the currently logged in user
     * @return number
     */
    public function loggedInUserId()
    {
        return auth()->user()[auth()->user()->getKey()];
    }

    /**
     * Fetch the currently logged in user
     * @return mixed
     */
    public function loggedInUser()
    {
        return auth()->user();
    }

    /**
     * Add a comment for this record by the given user.
     * @param $content String
     * @param $commenter mixed - If null will use currently logged in user.
     * @param $parent_id mixed
     */
    public function like($content, $commenter = null, $parent_id = null)
    {
        if (is_null($commenter)) {
            $commenter = $this->loggedInUser();
        }

        if (!is_null($parent_id)) { // check if parent exists
            $parent = Comment::where("id", "=", $parent_id)->first();
            if (!$parent) $parent_id = null;
        }
        $comment = new Comment();
        $comment->parent_id = $parent_id;
        $comment->commenter_type = get_class($commenter);
        $comment->commenter_id = $commenter[$commenter->getKey()];
        $comment->content = $content;
        $this->comments()->save($comment);

        $this->incrementCommentCount();
    }

    /**
     * Private. Increment the total comment count stored in the counter
     */
    private function incrementCommentCount()
    {
        $counter = $this->commentCounter;

        if ($counter) {
            $counter->count++;
            $counter->save();
        } else {
            $counter = new CommentCounter();
            $counter->count = 1;
            $this->commentCounter()->save($counter);
        }
    }

    /**
     * Populate the $model->comments attribute
     */
    public function getLikeCountAttribute()
    {
        return $this->commentCounter ? $this->commentCounter->count : 0;
    }
}
