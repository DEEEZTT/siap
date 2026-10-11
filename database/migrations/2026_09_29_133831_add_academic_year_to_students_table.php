<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('academic_year', 9)->nullable()->after('nis');
            $table->dropUnique('students_nis_unique');
        });

        DB::table('students')->whereNull('academic_year')->orderBy('id')->chunkById(200, function ($students): void {
            foreach ($students as $student) {
                $date = Carbon::parse($student->internship_start ?? $student->created_at ?? now());
                $startYear = $date->month >= 7 ? $date->year : $date->year - 1;

                DB::table('students')->where('id', $student->id)->update([
                    'academic_year' => $startYear.'/'.($startYear + 1),
                ]);
            }
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('academic_year', 9)->nullable(false)->change();
            $table->unique(['nis', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique(['nis', 'academic_year']);
            $table->unique('nis');
            $table->dropColumn('academic_year');
        });
    }
};
