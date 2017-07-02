<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePostIdToUserPostIdInUserLikes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_likes', function (Blueprint $table) {
            $table->renameColumn('post_id', 'user_post_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_likes', function (Blueprint $table) {
            $table->renameColumn('user_post_id', 'post_id');
        });
    }
}
