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
 * @property String commenter_type
 * @property Integer commenter_id
 * @property String commentable_type
 * @property Integer commentable_id
 * @property String content
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
        "commenter_type",
        "commenter_id",
        "commentable_type",
        "commentable_id",
        "content",
        "parent_id",
        "is_verified",
        "verifier_type",
        "verifier_id",
        "verified_at",
    ];

    public function getTable()
    {
        return config("commentable.comment_table");
    }


    public function verifier(): MorphOne
    {
        return $this->morphOne($this->verifier_type, "verifier");
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo($this->commentable_type, "commentable");
    }

    public function commenter(): MorphTo
    {
        return $this->morphTo($this->commenter_type, "commenter");
    }

    public function verify()
    {
        $user = Auth::user();
        $this->save([
            "verified_at" => Carbon::now(),
            "verifier_id" => $user[$user->getKey()], // authenticated user
            "verifier_type" => get_class($user)
        ]);
    }

    public function unverify()
    {
        $user = Auth::user();
        $this->save([
            "verified_at" => null,
            "verifier_id" => $user[$user->getKey()], // authenticated user
            "verifier_type" => get_class($user)
        ]);
    }

    public function verifyById()
    {

    }

}
