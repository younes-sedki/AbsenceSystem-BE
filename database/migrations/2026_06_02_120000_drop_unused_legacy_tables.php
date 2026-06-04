<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('problem_reports');
        Schema::dropIfExists('profile_change_requests');
        Schema::dropIfExists('sms_logs');
    }

    public function down(): void
    {
        // Legacy tables removed from the application scope.
    }
};
