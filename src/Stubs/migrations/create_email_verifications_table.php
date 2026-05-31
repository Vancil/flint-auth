<?php
declare(strict_types=1);

use Flint\Blueprint;
use Flint\Schema;

return new class {
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->datetime('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
