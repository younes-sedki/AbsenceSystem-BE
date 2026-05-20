<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('annee', ['1', '2']);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('classe_id')->references('id')->on('classes')->nullOnDelete();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('intitule');
            $table->enum('annee', ['1', '2']);
            $table->timestamps();
        });

        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'classe_id', 'module_id']);
        });

        Schema::create('sessions_appel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->date('date');
            $table->time('heure_debut');
            $table->enum('statut', ['ouverte', 'soumise'])->default('ouverte');
            $table->timestamps();
        });

        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions_appel')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('users')->cascadeOnDelete();
            $table->enum('statut', ['non_justifiee', 'en_attente', 'justifiee', 'rejetee'])->default('non_justifiee');
            $table->timestamp('date_limite');
            $table->timestamps();
            $table->unique(['session_id', 'etudiant_id']);
        });

        Schema::create('justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absence_id')->constrained('absences')->cascadeOnDelete();
            $table->enum('type', ['maladie', 'deces', 'convocation', 'accident', 'autre']);
            $table->text('notes')->nullable();
            $table->string('fichier_path')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->enum('statut', ['en_attente', 'acceptee', 'rejetee'])->default('en_attente');
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('destinataire_nom');
            $table->string('telephone')->nullable();
            $table->text('message');
            $table->enum('type', ['absence_detectee', 'rappel_justification', 'justification_acceptee', 'justification_rejetee']);
            $table->enum('statut', ['envoye', 'echoue']);
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::create('profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('champ');
            $table->text('ancienne_valeur')->nullable();
            $table->text('nouvelle_valeur');
            $table->enum('statut', ['en_attente', 'approuvee', 'rejetee'])->default('en_attente');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['classe_id']);
        });

        Schema::dropIfExists('profile_change_requests');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('justifications');
        Schema::dropIfExists('absences');
        Schema::dropIfExists('sessions_appel');
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('classes');
    }
};
