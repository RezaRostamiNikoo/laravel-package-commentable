<?php

namespace Rostami\Commentable\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * @property String title
 * @property String content
 * @property String creator_type
 * @property Integer creator_id
 * @property String commentable_type
 * @property Integer commentable_id
 * @property Integer parent_id
 * @property mixed is_confirmed
 * @property String verifier_type
 * @property Integer verifier_id
 * @property mixed verified_at
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "title",
        "content",
        "commentable_type",
        "commentable_id",
        "creator_type",
        "creator_id",
        "parent_id",
        "is_verified",
        "verifier_type",
        "verifier_id",
        "verified_at",
    ];
    protected $with = ['creator'];

    public function getTable()
    {
        return config("commentable.comment_table");
    }

    protected $dates = ['created_at', 'updated_at', 'deleted_at', "verified_at"];

    public function verifier(): MorphOne
    {
        return $this->morphOne($this->verifier_type, "verifier");
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo($this->commentable_type, "commentable");
    }

    public function creator(): MorphTo
    {
        return $this->morphTo($this->creator_type, "creator");
    }

    public function verify()
    {
        $user = Auth::user();
        $this->save([
            "verified_at" => Carbon::now(),
            "verifier_id" => $user->{$user->getKey()}, // authenticated user
            "verifier_type" => $user->getMorphClass()
        ]);
    }

    public function unverify()
    {
        $user = Auth::user();
        $this->save([
            "verified_at" => null,
            "verifier_id" => $user->{$user->getKey()}, // authenticated user
            "verifier_type" => $user->getMorphClass()
        ]);
    }

    /**
     * @param Model $commentable
     * @param array $data
     * @param Model $creator
     * @return $this
     */
    public static function createComment($commentable, $data, $creator): self
    {
        return $commentable->comments()->create(array_merge($data, [
            'creator_id' => $creator->getAuthIdentifier(),
            'creator_type' => $creator->getMorphClass(),
        ]));
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateComment($id, $data)
    {
        return (bool)static::find($id)->update($data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function deleteComment($id)
    {
        return (bool)static::find($id)->delete();
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function forceDeleteComment($id)
    {
        return (bool)static::find($id)->forceDelete();
    }


    //////////////////////////////////////
    ///     /**
    //     * @param $query
    //     * @param $user
    //     *
    //     * @return mixed
    //     */
    //    public function scopeByUser( $query, $user )
    //    {
    //        return $query->where('user_id', $user->id);
    //    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param Model                        $commenter
     * @param string                       $commentableType
     *
     * @return QueryBuilder
     */
    public function scopeBy($query, Model $commenter, $commentableType = null)
    {
        $builder = $query->getQuery();

        $builder->where('comments.commenter_type', '=', get_class($commenter))
            ->where('comments.commenter_id', '=', $commenter->getKey());

        if ($commentableType != null)
            $builder->where('comments.commentable_type', '=', $commentableType);

        return $builder;
    }
    /**
     * @param Model $commentable
     *
     * @return Comment
     * @throws \Exception
     */
    public function about(Model $commentable)
    {
        // Check have setted message,commenter yet?
        if (is_null($this->commenter_id) || is_null($this->commenter_type))
            throw new \BadMethodCallException('Can not call `about` method directly.' .
                'You must use: $commenter->commment($message)->about($object);');

        // Event
        /** @var Model $this */
        $events = $this->getEventDispatcher();
        $events->fire('namest.commentable.commenting', [$commentable, $this->message]);

        // Set commentable
        $this->commentable_id   = $commentable->getKey();
        $this->commentable_type = get_class($commentable);

        // Save
        if ($this->save()) {
            $events->fire('namest.commentable.commented', [$this]);

            return $this;
        }

        throw new \Exception("Can not save comment.");
    }


    /**
     * @param string $message
     *
     * @return string
     */
    public function getMessageAttribute($message)
    {
        try {
            $message = $this->attributes['message'];

            return $this->censor($message);
        } catch ( CensorException $e ) {
            return $message;
        }
    }
    /**
     * @param string $message
     */
    public function setMessageAttribute($message)
    {
        $this->attributes['message'] = $this->censor($message);
    }
    /**
     * @param string $message
     *
     * @return string
     * @throws CensorException
     */
    public function censor($message)
    {
        $break   = config('commentable.censor.break');
        $replace = config('commentable.censor.replace');
        $words   = config('commentable.censor.words');

        foreach ($words as $word) {
            $oldMessage = $message;

            $quote   = preg_quote($word, '/');
            $message = preg_replace("/" . $quote . "/i", $replace, $message); // Not case sensitive

            if ($oldMessage !== $message && $break)
                throw new CensorException($word, "Not allowed word [{$word}] occur.");
        }

        return $message;
    }

    /**
     * @return Model
     */
    public function getCommenterAttribute()
    {
        return forward_static_call([$this->commenter_type, 'find'], $this->commenter_id);
    }

    /**
     * @return Model
     */
    public function getCommentableAttribute()
    {
        return forward_static_call([$this->commentable_type, 'find'], $this->commentable_id);
    }

    /**
     * Determine if a comment has child comments.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }
    public function parent()
    {
        return $this->morphTo('parent');
    }
    public function ActiveToggle()
    {
        $this->active = (boolean)!$this->active;
        return $this;
    }
    public function scopeSearchComment(Builder $query, $column, $like = false)
    {
        $value = request($column, null);
        return $query->when($value, function (Builder $builder) use ($value, $like, $column) {
            $mark = $like ? '%' : '';
            return $builder->where($column, $like ? 'LIKE' : '=', $mark . $value . $mark);
        });
    }
}
