<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $personalAccessToken = new PersonalAccessToken;
        $personalAccessTokenTable = $personalAccessToken->getTable();

        Schema::create($personalAccessTokenTable, function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $personalAccessToken = new PersonalAccessToken;
        $personalAccessTokenTable = $personalAccessToken->getTable();

        Schema::dropIfExists($personalAccessTokenTable);
    }
};
