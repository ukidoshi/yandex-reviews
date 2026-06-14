<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('yandex_org_id');
            $table->string('name');
            $table->string('source_url');
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->unsignedInteger('ratings_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'yandex_org_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('yandex_review_id');
            $table->string('author_name')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('text')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'yandex_review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('organizations');
    }
};
