<?php

namespace Database\Seeders;

use App\Models\DataExport;
use App\Models\Project;
use App\Models\Role;
use App\Models\SavedDashboard;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $dataAnalystRole = Role::create(['slug' => 'data_analyst', 'name_en' => 'Data Analyst', 'name_ar' => 'محلل بيانات']);
        $adminRole = Role::create(['slug' => 'admin', 'name_en' => 'Admin', 'name_ar' => 'مشرف']);
        $studentRole = Role::create(['slug' => 'student', 'name_en' => 'Student', 'name_ar' => 'طالب']);
        $universityRole = Role::create(['slug' => 'university', 'name_en' => 'University', 'name_ar' => 'جامعة']);

        // --- Login accounts -------------------------------------------------
        $analyst = User::create([
            'name' => 'Data Analyst',
            'email' => 'analyst@example.com',
            'password' => Hash::make('password'),
        ]);
        $analyst->roles()->attach($dataAnalystRole->id, ['assigned_at' => now()]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->roles()->attach($adminRole->id, ['assigned_at' => now()]);

        // --- Background users, spread across the last 6 months, so the ---
        // --- "New User Growth" chart and "Users by Role" chart have -------
        // --- real month-by-month + role-by-role data to show. -------------
        $roleCycle = [$studentRole, $universityRole, $studentRole, $studentRole];
        for ($i = 0; $i < 40; $i++) {
            $createdAt = now()->subDays(rand(0, 179));
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
            ]);
            // created_at/updated_at aren't mass-assignable — set them
            // directly (bypassing the fillable guard) so the growth chart
            // has signups spread realistically over the last 6 months.
            $user->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
            $role = $roleCycle[$i % count($roleCycle)];
            $user->roles()->attach($role->id, ['assigned_at' => $createdAt]);
        }

        // --- Universities -----------------------------------------------
        $universities = collect([
            ['name_en' => 'Cairo University', 'name_ar' => 'جامعة القاهرة', 'verification_status' => 'verified'],
            ['name_en' => 'Ain Shams University', 'name_ar' => 'جامعة عين شمس', 'verification_status' => 'verified'],
            ['name_en' => 'Alexandria University', 'name_ar' => 'جامعة الإسكندرية', 'verification_status' => 'verified'],
            ['name_en' => 'Mansoura University', 'name_ar' => 'جامعة المنصورة', 'verification_status' => 'verified'],
            ['name_en' => 'Assiut University', 'name_ar' => 'جامعة أسيوط', 'verification_status' => 'pending'],
            ['name_en' => 'Zagazig University', 'name_ar' => 'جامعة الزقازيق', 'verification_status' => 'verified'],
        ])->map(fn ($attrs) => University::create($attrs));

        // --- Projects, spread across time + category, so growth/trend/ --
        // --- category charts all have something real to compute. --------
        $categories = ['AI & Machine Learning', 'Web Development', 'Mobile Apps', 'IoT', 'Cybersecurity', 'Data Science'];
        $statuses = ['published', 'published', 'published', 'pending', 'rejected'];

        for ($i = 0; $i < 120; $i++) {
            $createdAt = now()->subDays(rand(0, 89));
            $project = Project::create([
                'university_id' => $universities->random()->id,
                'title' => 'Graduation Project ' . ($i + 1),
                'category' => $categories[array_rand($categories)],
                'status' => $statuses[array_rand($statuses)],
            ]);
            $project->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        // --- The analyst's own saved dashboards + recent exports ---------
        SavedDashboard::create(['user_id' => $analyst->id, 'name' => 'Admissions Overview', 'is_default' => true]);
        SavedDashboard::create(['user_id' => $analyst->id, 'name' => 'University Growth Watch', 'is_default' => false]);

        DataExport::create(['user_id' => $analyst->id, 'export_type' => 'user_growth_csv', 'status' => 'completed']);
        DataExport::create(['user_id' => $analyst->id, 'export_type' => 'category_distribution_pdf', 'status' => 'completed']);
        DataExport::create(['user_id' => $analyst->id, 'export_type' => 'university_leaderboard_csv', 'status' => 'processing']);
    }
}
