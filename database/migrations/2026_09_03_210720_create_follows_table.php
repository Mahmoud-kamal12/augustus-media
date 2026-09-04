<?php

use App\Models\Follow;
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
        Schema::create(Follow::TABLE, function (Blueprint $table) {
            $table->foreignId('follower_id')->constrained(User::TABLE)->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained(User::TABLE)->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['follower_id', 'followed_id']);
            $table->index(['followed_id', 'follower_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Follow::TABLE);
    }
};
