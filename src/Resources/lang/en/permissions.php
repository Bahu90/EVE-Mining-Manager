<?php

return [
    // Base access
    'view_label' => 'View Mining Manager',
    'view_description' => 'Base access to the Mining Manager plugin. Grants access to the Help page only.',

    // Member tier
    'member_label' => 'Member',
    'member_description' => 'View own mining data, join/leave events, view moon schedules. Read-only access to personal information.',

    // Director tier
    'director_label' => 'Director',
    'director_description' => 'All Member permissions plus: view all corporation data, process ledger, create/edit events, update moon data, view analytics and reports.',

    // See all data, read-only (standalone, not a tier)
    'view_all_label' => 'See all data (read-only)',
    'view_all_description' => 'Read what directors read, change nothing: the corporation\'s mining and guest miners on the Dashboard, every pilot on Mining Summary and Moon Mining, Analytics, the Moon Planner and its blueprints, and Metenox Cargo. Reports and settings stay with directors and admins. Give it together with Member.',

    // Moon Manager capability (standalone, not a tier)
    'moon_manager_label' => 'Moon Manager',
    'moon_manager_description' => 'Access the Moon Extraction Planner (assign, move, and auto-fill planned moon pulls across refineries to stagger arrivals), Moon Analytics, and the Extraction Simulator with Find Moons. Directors and admins also have this access.',
    'moon_finder_label' => 'Moon Finder',
    'moon_finder_description' => 'Use the Extraction Simulator and Find Moons on it: search every scanned moon by location, composition, value and quality, and see better moons of the same class nearby. Directors, admins and moon managers have this access as well.',

    // Admin tier
    'admin_label' => 'Admin',
    'admin_description' => 'All Director permissions plus: manage settings, delete data, generate/export reports, API access, diagnostics.',
];
