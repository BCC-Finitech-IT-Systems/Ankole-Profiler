<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Add sub-categories to the Education department (id=1) to match organization categories
$educationDept = \App\Models\Department::where('name', 'Education')->first();

if ($educationDept) {
    $existing = $educationDept->subCategories()->pluck('name')->toArray();
    $needed = ['Primary', 'Secondary', 'Tertiary'];

    foreach ($needed as $name) {
        if (!in_array($name, $existing)) {
            $educationDept->subCategories()->create(['name' => $name, 'is_active' => true]);
            echo "Added sub-category '$name' to Education department." . PHP_EOL;
        } else {
            echo "Sub-category '$name' already exists." . PHP_EOL;
        }
    }
} else {
    echo "Education department not found!" . PHP_EOL;
}

// Verify
$educationDept->refresh();
$educationDept->load('subCategories');
echo PHP_EOL . "Education sub-categories now: " . $educationDept->subCategories->pluck('name')->join(', ') . PHP_EOL;
