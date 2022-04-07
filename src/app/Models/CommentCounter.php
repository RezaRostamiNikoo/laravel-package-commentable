<?php


namespace Rostami\Commentable\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class CommentCounter extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ["commentable_type", "commentable_id", "count"];

    public function getTable()
    {
        return config("commentable.counter_table");
    }


    public static function rebuild($modelClass)
    {
        if (empty($modelClass)) {
            throw new \Exception('$modelClass cannot be empty/null. Maybe set the $morphClass variable on your model.');
        }

        $builder = Comment::query()
            ->select(DB::raw('count(*) as count, commentable_type, commentable_id'))
            ->where('commentable_type', $modelClass)
            ->groupBy('commentable_id');

        $results = $builder->get();

        $inserts = $results->toArray();

        DB::table((new static)->table)->insert($inserts);
    }

}
