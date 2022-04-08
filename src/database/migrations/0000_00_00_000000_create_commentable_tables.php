<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config("commentable.comment_table"), function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->text("body");
            $table->morphs("commentable");
            $table->morphs("creator");
            $table->foreignId("parent_id")->nullable()
                ->constrained(config("commentable.comment_table"))
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->boolean("is_verified")->default(false);
            $table->nullableMorphs("verifier");

//            $table->integer('parent_id')->nullable();
//            $table->integer('lft')->nullable();
//            $table->integer('rgt')->nullable();
//            $table->integer('depth')->nullable();



            $table->index('user_id');
            $table->index('commentable_id');
            $table->index('commentable_type');


            $table->timestamp("verified_at")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create(config("commentable.counter_table"), function (Blueprint $table) {
            $table->id();
            $table->morphs("commentable");
            $table->integer("count");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config("commentable.comment_table"));
        Schema::dropIfExists(config("commentable.counter_table"));
    }
}
