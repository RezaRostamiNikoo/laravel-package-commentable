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
            $table->morphs("commenter");
            $table->morphs("commentable");
            $table->string("content");
            $table->foreignId("parent_id")->nullable()
                ->constrained(config("commentable.comment_table"))
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->boolean("is_verified")->default(false);
            $table->nullableMorphs("verifier");
            $table->timestamp("verified_at")->nullable();
            $table->timestamps();

            $table->softDeletes();
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
