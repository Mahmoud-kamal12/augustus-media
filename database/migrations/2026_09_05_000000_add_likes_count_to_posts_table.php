<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Post::TABLE, function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('content');
        });
    }

    public function down(): void
    {
        Schema::table(Post::TABLE, function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};
