<?php

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Like::TABLE, function (Blueprint $table) {
            $table->foreignId('post_id')->constrained(Post::TABLE)->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(User::TABLE)->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Like::TABLE);
    }
};
