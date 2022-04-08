<?php

namespace Rostami\Commentable\App\Traits;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Ramsey\Uuid\Type\Integer;
use Rostami\Commentable\App\Models\Comment;
use Rostami\Commentable\App\Models\CommentCounter;

trait HasComment
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
            ->where('commentable_id', $this->{$this->getKey()})
            ->delete();

        CommentCounter::where('commentable_type', static::class)
            ->where('commentable_id', $this->{$this->getKey()})
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
     * @param $query
     * @param Model $creator
     * @return mixed
     */
    public function scopeCommentedBy($query, $creator = null)
    {
        if (is_null($creator)) {
            $creator = $this->loggedInUser();
        }

        return $query->whereHas('comments', function ($q) use ($creator) {
            $q->where("creator_type", "=", $creator->getMorphClass())
                ->where('creator_id', '=', $creator->{$creator->getKey()});
        });
    }

    /**
     * Fetch the primary ID of the currently logged in user
     * @return number
     */
    public function loggedInUserId()
    {
        return auth()->user()->{auth()->user()->getKey()};
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
     * @param array $data
     * @param $creator Model - If null will use currently logged in user.
     * @param Integer|null $parent_id
     * @return mixed
     */
    public function addComment($data, $creator = null, $parent_id = null)
    {
        if (is_null($creator)) {
            $creator = $this->loggedInUser();
        }

        if (!is_null($parent_id)) { // check if parent exists
            $parent = Comment::where("id", "=", $parent_id)->first();
            if (!$parent) $parent_id = null;
        }

        Comment::createComment($this, array_merge($data, ['parent_id' => $parent_id,]), $creator);
        $this->incrementCommentCount();

    }

    /**
     * @param $id
     * @param $data array
     * @param Model|null $parent
     * @return mixed
     */
    public function updateComment($id, $data, $parent = null)
    {
        return Comment::updateComment($id, $data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteComment(int $id)
    {
        return (bool)Comment::deleteComment($id);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function forceDeleteComment(int $id)
    {
        return (bool)Comment::forceDeleteComment($id);
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



    ///////////////////////////////////////////////
    public function isCommented()
    {
        return $this->comments()->exists();
    }

    public function isCommentedByUser(int $user_id)
    {
        return $this->comments()->where('user_id', $user_id)->exists();
    }
    public function scopeOrderByCommentsCount(Builder $query, string $direction = 'asc')
    {
        return $query
            ->leftJoin('comments', function (JoinClause $join) {
                $join
                    ->on('comments.commentable_id', $this->getTable() . '.id')
                    ->where('comments.commentable_type', in_array(__CLASS__, Relation::morphMap()) ? array_search(__CLASS__, Relation::morphMap()) : __CLASS__);
            })
            ->addSelect(DB::raw('COUNT(comments.id) as count_comments'))
            ->groupBy($this->getTable(). '.id')
            ->orderBy('count_comments', $direction);
    }
    public function resetComments()
    {
        return $this->comments()->delete();
    }
    public function deleteCommentsForUser(int $user_id)
    {
        return $this->comments()->where('user_id', $user_id)->delete();
    }
    public function creators()
    {
        return $this->morphToMany(config('commentable.user'), 'commentable', 'comments');
    }
    public function scopeHasComments( $query )
    {
        return $query->has('comments');
    }
    public function scopeHasNoComments( $query )
    {
        return $query->has('comments', 0);
    }
    public function scopeHasCommentsByUser( $query, $user )
    {
        return $query->whereHas('comments', function ( $query ) use ( $user )
        {
            $query->where('user_id', $user->id);
        });
    }
    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param Model                        $commenter
     *
     * @return QueryBuilder
     */
    public function scopeHasCommentBy($query, Model $commenter)
    {
        $table   = $this->getTable();
        $builder = $query->getQuery();

        $builder->join('comments', 'comments.commentable_id', '=', "{$table}.id")
            ->where('comments.commentable_type', '=', get_class($this))
            ->where('comments.commenter_type', '=', get_class($commenter))
            ->where('comments.commenter_id', '=', $commenter->getKey());

        return $builder;
    }
    /**
     * TODO Optimize performance by reduce SQL query
     *
     * @return array
     */
    public function getCommentersAttribute()
    {
        $relation = $this->hasMany(Comment::class, 'commentable_id');
        $relation->getQuery()->where('commentable_type', '=', get_class($this));

        return new Collection(array_map(function ($like) {
            return forward_static_call([$like['commenter_type'], 'find'], $like['commenter_id']);
        }, $relation->getResults()->toArray()));
    }
}
