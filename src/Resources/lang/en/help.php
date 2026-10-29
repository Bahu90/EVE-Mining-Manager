<?php

return [
    // Page Title
    'help_documentation' => 'Help & Documentation',
    'navigation' => 'Navigation',
    'search_placeholder' => 'Search help documentation...',

    // Navigation Items
    'overview' => 'Overview',
    'getting_started' => 'Getting Started',
    'dashboard' => 'Dashboard',
    'mining_events' => 'Mining Events',
    'moon_mining' => 'Moon Mining',
    'analytics_reports' => 'Analytics & Reports',
    'settings' => 'Settings',
    'cli_commands' => 'CLI Commands',
    'permissions' => 'Permissions',
    'faq' => 'FAQ',
    'troubleshooting' => 'Troubleshooting',

    // Common
    'tip' => 'Tip',
    'note' => 'Note',
    'important' => 'Important',

    // Overview Section - Plugin Information
    'plugin_information' => 'Plugin Information',
    'plugin_author' => 'Bahu90',
    'plugin_based_on' => 'Based on Mining Manager by Matt Falahe (GPL-2.0)',
    'plugin_license' => 'GPL-2.0',
    'github_repository' => 'GitHub Repository',
    'full_changelog' => 'Full Changelog',
    'report_issues' => 'Report Issues',
    'readme' => 'README',
    'support_the_project' => 'Support the Project',
    'support_star' => 'Star the GitHub repository',
    'support_issues' => 'Report bugs and issues',
    'support_features' => 'Suggest new features',
    'support_contribute' => 'Contributing code improvements',
    'support_share' => 'Share with other SeAT users',

    // Overview Section - Welcome
    'welcome_title' => 'Welcome to Mining Manager',
    'welcome_desc' => 'Mining and moon tracking for EVE Online corporations using SeAT.',

    // Overview Section - What is
    'what_is_mining_manager' => 'What is Mining Manager?',
    'what_is_mining_manager_desc' => 'Mining Manager is a comprehensive SeAT plugin designed to help EVE Online corporations track mining activities, organize mining events, plan and monitor moon extractions, and provide detailed analytics on mining performance.',
    'key_benefits' => 'Key Benefits',
    'key_benefits_desc' => 'Automatic mining tracking, moon extraction tracking and planning, Discord/Slack notifications, and comprehensive analytics.',

    // Overview Section - Core Features
    'core_features' => 'Core Features',
    'feature_ledger' => 'Mining Ledger',
    'feature_ledger_desc' => 'Track all mining activities with detailed volume, quantity, and ISK value breakdowns per character and corporation. A Moon / Anomalies / All switch on My Mining, Mining Summary and each pilot\'s page shows ore mined at the corporation\'s refineries, ore from belts and anomalies, or both, with a bar splitting the two.',
    'feature_moon' => 'Moon Mining',
    'feature_moon_desc' => 'Track moon extractions, compositions, ore classifications (R4-R64), quality ratings, and structure assignments.',
    'feature_events' => 'Mining Events',
    'feature_events_desc' => 'Organize mining operations with bonus multipliers, time windows, ore type restrictions, and participation tracking.',
    'feature_analytics' => 'Analytics & Reports',
    'feature_analytics_desc' => 'Comprehensive charts, moon utilization analysis, pool vs mined comparisons, and ore popularity tracking.',

    // Getting Started
    'getting_started_intro' => 'Welcome to the SeAT Mining Manager! This guide will help you get started with managing your corporation\'s mining operations.',
    'what_is_mining_manager' => 'What is Mining Manager?',
    'what_is_mining_manager_desc' => 'Mining Manager is a comprehensive SeAT plugin designed to help EVE Online corporations track mining activities, organize mining events, plan and monitor moon extractions, and provide detailed analytics on mining performance.',

    // Quick Start Steps
    'quick_start_guide' => 'Quick Start Guide',
    'step_1_title' => 'Configure Settings',
    'step_1_desc' => 'Navigate to Settings and configure your corporation, ore valuation method, price provider, and market hub region.',
    'step_4_title' => 'Grant Permissions',
    'step_4_desc' => 'Set up permissions for your members using SeAT\'s ACL system. Assign view, member, director, or admin roles as appropriate.',
    'step_5_title' => 'Start Mining',
    'step_5_desc' => 'Members can now start mining! The system will automatically track all mining activities from ESI.',
    'getting_started_tip' => 'Mining ledger data is fetched from ESI automatically. The scheduled command processes new data every 30 minutes.',

    // Dashboard Guide
    'dashboard_guide' => 'Dashboard Overview',
    'dashboard_intro' => 'The dashboard is your central hub for monitoring mining activities. It provides different views for regular members and directors.',
    'member_dashboard' => 'Member Dashboard',
    'member_dashboard_desc' => 'As a regular member, your dashboard shows:',
    'personal_stats' => 'Personal Statistics',
    'personal_stats_desc' => 'Your mining totals and personal rankings',
    'recent_activity' => 'Recent Activity',
    'recent_activity_desc' => 'Your recent mining operations and ledger entries',
    'upcoming_events' => 'Upcoming Events',
    'upcoming_events_desc' => 'Mining events you\'re registered for',
    'director_dashboard' => 'Director Dashboard',
    'director_dashboard_desc' => 'As a director, your dashboard includes:',
    'corp_overview' => 'Corporation Overview',
    'corp_overview_desc' => 'Total mining volume and value statistics',
    'top_miners' => 'Top Miners',
    'top_miners_desc' => 'Leaderboard of most productive miners',
    'active_events' => 'Active Events',
    'active_events_desc' => 'Currently running mining events and participation',
    'dashboard_charts' => 'Director Dashboard Charts',
    'dashboard_charts_desc' => 'The director dashboard includes several 12-month charts:',
    'chart_mining_performance' => 'Corp Mining Performance - Total mining value across all corporation characters per month.',
    'chart_moon_mining' => 'Moon Mining Performance - Moon ore value mined per month, tracked separately from regular ore.',
    'chart_mining_by_group' => 'Mining by Group - Breakdown of mining value by ore category (Moon, Ice, Gas, Regular).',
    'chart_mining_by_type' => 'Mining by Type - Top 10 most mined ore types by value.',
    'dashboard_note' => 'The dashboard updates automatically as new ESI data is processed. Dashboard data is cached for 5 minutes to keep pages fast. Today\'s mining data appears immediately even before daily summaries are generated.',

    // Mining Events
    'mining_events_guide' => 'Mining Events Guide',
    'events_intro' => 'Mining events are organized operations where participants can earn bonus multipliers on their mining activity.',
    'creating_events' => 'Creating an Event (Directors)',
    'event_create_step_1' => 'Navigate to Events > Create Event',
    'event_create_step_2' => 'Fill in event details: name, location, start/end time',
    'event_create_step_3' => 'Set the bonus multiplier (e.g., 1.5x for 50% bonus)',
    'event_create_step_4' => 'Optionally restrict ore types (e.g., only moon ore)',
    'event_create_step_5' => 'Save and announce the event to your members',
    'participating_events' => 'Participating in Events',
    'participating_desc' => 'To participate in a mining event:',
    'participate_step_1' => 'View active events on the Events page or dashboard',
    'participate_step_2' => 'Mine during the event time window in the specified location',
    'participate_step_3' => 'Your bonus will be automatically applied to that mining',

    // Moon Mining
    'moon_mining_guide' => 'Moon Mining Guide',
    'moon_intro' => 'Moon mining features help you track extractions, analyze moon compositions, and calculate potential value from your Athanors and Tataras.',
    'moon_tracking' => 'Tracking Moon Extractions',
    'moon_tracking_desc' => 'The system automatically tracks all moon extraction activities from your structures. View extraction history, active extractions, completion progress, and structure names.',
    'moon_compositions' => 'Moon Compositions',
    'moon_compositions_desc' => 'A moon\'s ore comes from its scan in SeAT\'s Moons Reporter: the percentages, the rarity (R4 to R64) and an estimated value at current market prices. A moon nobody has scanned is still valued, from the game\'s own extraction notices, once the first one is in. Its extraction page says so in an amber banner, and the simulator, Find Moons and the quality ratings cannot see it until it is scanned. Moon Scan Missing, in the Plugin Health group on the webhook form, lists the moons your refineries drill that need a scan.',
    'extraction_notifications' => 'Extraction Notifications',
    'extraction_notifications_desc' => 'Moon Chunk Ready fires once, within about a minute of the chunk arriving, when it can be fractured with the laser. Its master switch and role ping are on its card under Settings, Notifications, and each webhook decides whether it gets it.',
    'moon_value' => 'Moon Value Calculator',
    'moon_value_desc' => 'The Extraction Simulator, in the Moon Planner section, estimates what a scanned moon yields over an extraction, as ore and as refined value, from the price cache. See Extraction Simulator and Find Moons below.',

    // Moon Extraction Lifecycle
    'moon_lifecycle' => 'Moon Extraction Lifecycle',
    'moon_lifecycle_intro' => 'Understanding the moon extraction lifecycle helps you track moon mining operations effectively.',
    'moon_status_extracting' => 'Extracting',
    'moon_status_extracting_desc' => 'The moon drill beam is active, forming a chunk. Duration depends on extraction settings (typically 6-56 days).',
    'moon_status_ready' => 'Ready',
    'moon_status_ready_desc' => 'The chunk has fractured and an asteroid belt is available for mining. It can be mined for 48 hours after fracture, or 72 / 96 hours when the refinery has a Moon Drilling Stability or Proficiency rig.',
    'moon_status_unstable' => 'Unstable',
    'moon_status_unstable_desc' => 'The last 2 hours of the belt\'s life, after its mining window: 48 to 50 hours after fracture, later with a moon rig. Mining should be prioritized.',
    'moon_status_expired' => 'Expired',
    'moon_status_expired_desc' => 'Belt has despawned. No further mining possible until next extraction cycle.',

    // Moon Classification
    'moon_classification' => 'Moon Classification',
    'moon_classification_desc' => 'Moons are classified by their highest rarity ore:',
    'moon_r64' => 'R64 - Contains extremely rare ores (Loparite, Monazite, Xenotime, Ytterbite)',
    'moon_r32' => 'R32 - Contains rare ores (Carnotite, Cinnabar, Pollucite, Zircon)',
    'moon_r16' => 'R16 - Contains uncommon ores (Chromite, Otavite, Sperrylite, Vanadinite)',
    'moon_r8' => 'R8 - Contains common ores (Cobaltite, Euxenite, Scheelite, Titanite)',
    'moon_r4' => 'R4 - Contains basic ores (Bitumite, Coesite, Sylvite, Zeolites)',
    'moon_quality' => 'Moon Quality Rating',
    'moon_quality_desc' => 'The Extraction Simulator and Find Moons rate a moon against every scanned moon of the same class (its rarest ore) by its value over 28 days, so a strong R4 moon can rate well. A class needs at least five scanned moons before its moons are rated.',
    'moon_quality_exceptional' => 'Exceptional - the top 10% of its class',
    'moon_quality_excellent' => 'Excellent - the top 25% of its class',
    'moon_quality_good' => 'Good - the top half of its class',
    'moon_quality_average' => 'Average - the top 75% of its class',
    'moon_quality_poor' => 'Poor - the bottom quarter of its class',

    // Jackpot Detection
    'jackpot_title' => 'Jackpot Detection & Reporting',
    'jackpot_intro' => 'Jackpot extractions contain +100% yield variant ores (e.g., Shining Monazite, Glowing Carnotite). These are significantly more valuable than normal extractions. EVE Online\'s ESI API does not provide jackpot information directly, so the plugin detects jackpots through two methods:',
    'jackpot_auto_title' => 'Automatic Detection',
    'jackpot_auto_desc' => 'A scheduled command (mining-manager:detect-jackpots) runs daily at 6:05 AM. It scans the mining ledger for jackpot ore type IDs mined within each extraction\'s time window and solar system. When jackpot ores are found, the extraction is automatically marked as a verified jackpot and a webhook notification is sent. This method requires someone to have actually mined the jackpot ores first.',
    'jackpot_manual_title' => 'Manual Reporting',
    'jackpot_manual_desc' => 'Any member can report a jackpot by clicking the "Report Jackpot" button on an extraction that has arrived (status: Ready or Unstable). The button appears on the extraction detail page. When reported, a webhook notification is sent immediately with the reporter\'s name. The report is initially unverified and will be confirmed automatically when mining data arrives.',
    'jackpot_verification_title' => 'Verification Status',
    'jackpot_verification_desc' => 'Every jackpot has a verification status that indicates how confident the system is in the detection:',
    'jackpot_status_verified' => 'Verified',
    'jackpot_status_verified_desc' => 'Jackpot ores have been confirmed in the mining ledger data. This happens automatically for auto-detected jackpots, and when the daily scan confirms a manual report.',
    'jackpot_status_awaiting' => 'Awaiting Verification',
    'jackpot_status_awaiting_desc' => 'A member reported this jackpot manually, but no mining data has been processed yet to confirm it. The daily detect-jackpots command will verify it when observer data arrives.',
    'jackpot_status_unverified' => 'Unverified',
    'jackpot_status_unverified_desc' => 'The extraction expired and the daily scan found no jackpot ores in the mining data. This could mean the report was incorrect, or that no one mined the jackpot ores before the belt decayed.',
    'jackpot_note_title' => 'Note',
    'jackpot_note_desc' => 'Jackpot detection relies entirely on mining ledger data since ESI does not report jackpot status. If a jackpot chunk arrives but nobody mines the +100% ores before it decays, automatic detection cannot confirm it. Manual reporting allows fleet members to flag jackpots immediately when they see them in the belt.',

    // Analytics & Reports
    'analytics_reports_guide' => 'Analytics & Reports Guide',
    'analytics_intro' => 'The analytics section provides comprehensive insights into your corporation\'s mining performance with interactive charts and detailed breakdowns.',
    'available_reports' => 'Available Reports',
    'report_monthly' => 'Monthly Mining Report',
    'report_monthly_desc' => 'Complete breakdown of mining activity and top performers for each month.',
    'report_member' => 'Member Activity Report',
    'report_member_desc' => 'Individual member performance, mining history, and trends.',
    'report_event' => 'Event Performance Report',
    'report_event_desc' => 'Analysis of mining event participation and effectiveness.',
    'report_moon' => 'Moon Extraction Report',
    'report_moon_desc' => 'Moon mining statistics, extraction values, and composition analysis.',
    'report_comparison' => 'Comparative Analysis',
    'report_comparison_desc' => 'Compare time periods, members, or ore types side-by-side.',

    // Moon Analytics
    'moon_analytics' => 'Moon Analytics',
    'moon_analytics_desc' => 'Detailed analytics for moon mining operations:',
    'moon_analytics_utilization' => 'Moon Utilization - Track how much of each extraction pool is actually mined, with structure names and completion percentages.',
    'moon_analytics_pool_vs_mined' => 'Pool vs Mined - Compare what was available in each extraction against what was actually mined, broken down by ore type.',
    'moon_analytics_per_extraction' => 'Per-Extraction Analysis - Detailed view of individual extractions showing pool composition, mining activity, unique miners, and ISK values.',
    'moon_analytics_rigs' => 'Moon Rigs - A cog marks refineries with moon rigs fitted now, and the chunks that were pulled with them. Hover it to see which rigs and what they did to each chunk.',
    'moon_analytics_popularity' => 'Ore Popularity - Track which ores are most frequently mined across all moon extractions.',

    'exporting_data' => 'Exporting Data',
    'exporting_desc' => 'Reports can be exported for further analysis or sharing with your leadership team.',

    // Settings
    'settings_guide' => 'Settings Configuration Guide',
    'settings_intro' => 'The settings page is organized into Global Settings (apply to all corporations), Corporation-Specific Settings (per-corp overrides), and System Settings. Admin permission is required to modify settings.',
    'settings_tabs' => 'Settings Tabs',

    // Global Settings
    'settings_global_header' => 'Global Settings',
    'settings_general' => 'General',
    'settings_general_desc' => 'Moon owner corporation and other install-wide settings.',
    'settings_pricing' => 'Pricing',
    'settings_pricing_desc' => 'Price provider (SeAT / Janice / Fuzzwork), price type (sell, buy, or average), market hub, refining efficiency for mineral price valuation, and cache duration. Fallback-to-Jita auto-fills any zero-priced types from Jita as a per-item safety net. The fallback price provider (Fuzzwork by default, Janice or none) prices instead when the provider fails outright, for the same market and price type, and the Price Provider Trouble notification says when that happens.',
    'settings_features' => 'Features',
    'settings_features_desc' => 'Toggle individual plugin features on/off: ledger tracking, moon tracking, mining events, analytics and reports, along with Allow Data Export, the automatic ledger run, and how long old ledger records are kept. Disabled features are hidden from the UI.',
    'settings_webhooks' => 'Webhooks',
    'settings_webhooks_desc' => 'Configure webhook integrations for notifications. Set up webhook URLs, choose the type (Discord, Slack, or Custom), and select which notification events each webhook should receive.',
    'settings_notifications' => 'Notifications',
    'settings_notifications_desc' => 'Configure notification delivery settings. Each notification type has a card with its master switch and Discord role ping, and user pinging where a notification goes to particular pilots (requires seat-connector). The same tab holds the Moon Planner timings (the minimum gap between moons, the spacing between pulls on one refinery, and the reminder hours), the Metenox bay threshold, Moon Scan Missing\'s daily reminder, and the Slack channel.',
    'settings_dashboard' => 'Dashboard',
    'settings_dashboard_desc' => 'Choose which corporations the dashboard\'s Top Miners boards include: every corporation, or only the ones you pick. It is one setting for the whole install, whichever corporation the settings page is set to.',
    'settings_routing_map' => 'Notification Routing Map',
    'settings_routing_map_desc' => 'A read-only view of where each notification goes: which webhooks receive it and which role it pings. It warns about a notification that is switched on but reaches no webhook. Nothing on it can be changed; use the Webhooks and Notifications tabs for that.',

    // System Settings
    'settings_system_header' => 'System Settings',
    'settings_advanced' => 'Advanced',
    'settings_advanced_desc' => 'System maintenance tools: clear price cache, reset settings to defaults, export/import settings as JSON, and view system information. Use with caution — some actions cannot be undone.',
    'settings_help' => 'Help & Documentation',
    'settings_help_desc' => 'Quick access to this help page from within the settings panel.',

    'settings_warning' => 'Admin Permission Required',
    'settings_warning_desc' => 'Only users with admin permissions can modify settings. Changes affect all members immediately.',

    // Custom Styling
    'custom_styling' => 'Custom Styling',
    'custom_styling_guide' => 'CSS Overrides Guide',
    'custom_styling_intro' => 'Mining Manager uses CSS wrapper classes on every page, allowing you to customize the appearance using SeAT\'s custom CSS feature or your own stylesheets.',
    'css_class_hierarchy' => 'CSS Class Hierarchy',
    'css_class_hierarchy_desc' => 'Every page uses a layered class structure for targeted styling:',
    'css_base_class' => '.mining-manager-wrapper - Present on ALL plugin pages. Use this to style everything globally.',
    'css_tab_class' => '.mining-dashboard - Applied to pages with Dashboard-style tab navigation. Controls tab appearance (font size, active state, rounded corners).',
    'css_page_class' => 'Page-specific classes - Target individual pages for fine-grained overrides.',
    'css_available_pages' => 'Available Page Classes',
    'css_analytics_pages' => 'Analytics: .analytics-page, .analytics-charts-page, .analytics-tables-page, .analytics-compare-page, .analytics-moons-page',
    'css_dashboard_pages' => 'Dashboard: .combined-director-dashboard, .personal-dashboard',
    'css_diagnostic_pages' => 'Diagnostics: .diagnostic-page',
    'css_events_pages' => 'Events: .events-page, .events-active-page, .events-calendar-page, .events-create-page, .events-my-events-page',
    'css_ledger_pages' => 'Ledger: .mining-ledger, .mining-ledger-summary, .mining-ledger-details, .my-mining',
    'css_moon_pages' => 'Moon: .moon-index-page, .moon-active-page, .moon-calendar-page, .moon-compositions-page, .moon-extractions-page, .moon-simulator-page',
    'css_reports_pages' => 'Reports: .reports-page, .reports-generate-page, .reports-export-page, .reports-scheduled-page',
    'css_settings_pages' => 'Settings: .settings-page',
    'css_example_title' => 'Example Overrides',
    'css_example_global' => '/* Change tab font size on all pages */',
    'css_example_global_code' => '.mining-dashboard .nav-tabs .nav-link { font-size: 0.85rem; }',
    'css_example_specific' => '/* Change tab font size only on the diagnostic page */',
    'css_example_specific_code' => '.diagnostic-page .nav-tabs .nav-link { font-size: 0.90rem; }',
    'css_example_all' => '/* Add custom background to all plugin pages */',
    'css_example_all_code' => '.mining-manager-wrapper { background-color: #1a1a2e; }',
    'css_where_to_add' => 'Where to Add Custom CSS',
    'css_where_to_add_desc' => 'SeAT auto-loads a global custom stylesheet, custom-layout.css, if it exists (plus custom-layout-mini.css for the sign-in page). On a bare-metal install, place custom-layout.css in SeAT\'s public/ directory (e.g. /var/www/seat/public/custom-layout.css). On SeAT Docker, put it in /opt/seat-docker/custom/ and mount it to /var/www/seat/public/ via docker-compose.override.yml, then bring the stack back up. It is detected automatically — there is no SeAT setting to toggle. Keep your overrides there, never in the plugin files, so they survive plugin updates. Reference: https://eveseat.github.io/docs/styling/',

    // FAQ
    'frequently_asked' => 'Frequently Asked Questions',
    'faq_q1' => 'How often is mining data updated?',
    'faq_a1' => 'Mining ledger data is fetched from ESI and processed every 30 minutes by the scheduled command. Moon extractions update every 2 hours. Fracture notifications are also checked on page load when viewing moon details.',
    'faq_q2' => 'How are ore prices determined?',
    'faq_a2' => 'Ore prices are fetched from your configured price provider and cached. You can choose between sell, buy, or average prices from your selected market hub region.',
    'faq_q3' => 'Can members see other members\' mining data?',
    'faq_a3' => 'Members only see their own data and the help page. The See all data (read-only) permission, Directors and Admins can see corporation-wide data; only Directors and Admins can change anything.',
    'faq_q4' => 'What is a jackpot extraction?',
    'faq_a4' => 'A jackpot chunk is made of the +100% variants of its ores (Shining Monazite rather than Monazite, for example), which reprocess to about twice the minerals, so the chunk is worth about twice as much. ESI does not say which chunks are jackpots, so the plugin finds them in the mining ledger once the ore is mined, or takes a member\'s report from the extraction page.',
    'faq_q5' => 'How do mining events work?',
    'faq_a5' => 'Mining events are time-limited operations. The plugin counts the mining each participant did inside the event window, for the ore categories the event type covers, and ranks participants on the event page.',
    'faq_q6' => 'Does Mining Manager still handle mining tax?',
    'faq_a6' => 'No. Since 3.0.0 the plugin only tracks mining and moons. Taxes, tax codes, invoices, payments and theft detection were removed. Data recorded by earlier versions stays in the database but is no longer read.',
    'faq_q7' => 'Is this compatible with other SeAT plugins?',
    'faq_a7' => 'Mining Manager is designed to work alongside other SeAT v5.x plugins. It uses standard SeAT authentication, permissions, and database systems.',
    'faq_q8' => 'Does Mining Manager need any other plugin?',
    'faq_a8' => 'No. Everything it does runs on SeAT alone: the ledger, moon tracking, the planner and notifications. Discord role pickers read roles from SeAT Connector or SeAT Broadcast when one of them is installed, and fall back to typing a role ID when neither is.',
    'faq_q9' => 'What is the Master Test diagnostic and when should I run it?',
    'faq_a9' => 'Master Test is the second tab on the internal Diagnostic page, after Health Checks. The Diagnostic page is admin-only and intentionally NOT in the sidebar: navigate to it manually at /mining-manager/diagnostic. It runs a one-click read-only set of checks across schema integrity, settings consistency, pricing, notifications, lifecycle, ore classification and security hardening. Run it after every plugin upgrade or settings change.',

    // Troubleshooting
    'troubleshooting_guide' => 'Troubleshooting Guide',
    'troubleshooting_intro' => 'Having issues? Here are solutions to common problems.',
    'common_issues' => 'Common Issues',

    'issue_1_title' => 'Mining data not showing up',
    'issue_1_desc' => 'If your mining isn\'t being tracked:',
    'issue_1_solution_1' => 'Verify ESI tokens are valid and have the correct scopes (check SeAT settings)',
    'issue_1_solution_2' => 'Wait 30 minutes for the next scheduled ledger processing',
    'issue_1_solution_3' => 'Check that the character is in the correct corporation',
    'issue_1_solution_4' => 'Run php artisan mining-manager:diagnose-character {character_id} to check data',

    'issue_2_title' => 'Prices seem wrong or outdated',
    'issue_2_desc' => 'If ore values look incorrect:',
    'issue_2_solution_1' => 'Check your price provider and market hub in Settings > Pricing',
    'issue_2_solution_2' => 'Run php artisan mining-manager:diagnose-prices to test provider connectivity',
    'issue_2_solution_3' => 'Run php artisan mining-manager:cache-prices to force a price refresh',

    'issue_4_title' => 'Moon extraction data missing',
    'issue_4_desc' => 'If moon extractions aren\'t showing:',
    'issue_4_solution_1' => 'Ensure the corporation has structure ESI scopes configured',
    'issue_4_solution_2' => 'Run php artisan mining-manager:diagnose-extractions to check extraction data',
    'issue_4_solution_3' => 'Run php artisan mining-manager:update-extractions to force an update',

    'need_help' => 'Still Need Help?',
    'support_message' => 'If you\'re still experiencing issues, please contact your corporation directors or file an issue on the GitHub repository with detailed information about the problem.',

    // Permissions
    'permissions_guide' => 'Permissions Guide',
    'permissions_intro' => 'Mining Manager uses SeAT\'s permission system with four access tiers. All permissions are in the financial division.',
    'available_permissions' => 'Available Permissions',
    'perm_view' => 'mining-manager.view',
    'perm_view_desc' => 'Basic access to the help page only. Grants no access to mining data or management features.',
    'perm_member' => 'mining-manager.member',
    'perm_member_desc' => 'Corp miner access. View personal mining data, the My Mining page, and participate in mining events.',
    'perm_view_all' => 'mining-manager.view_all',
    'perm_view_all_desc' => 'See all data, read-only. Give it with Member to let pilots read what directors read and change nothing: the corporation and guest miners on the Dashboard, every pilot on Mining Summary and Moon Mining, Analytics, the Moon Planner and its blueprints, and Metenox Cargo. Reports and settings stay with directors and admins.',
    'perm_director' => 'mining-manager.director',
    'perm_director_desc' => 'Corp management access. View all member data, analytics, reports and moon mining.',
    'perm_admin' => 'mining-manager.admin',
    'perm_admin_desc' => 'Full control. Everything in director plus access to settings, diagnostics, manual actions, and system configuration.',
    'setting_permissions' => 'Setting Up Permissions',
    'setting_permissions_desc' => 'Permissions are managed through SeAT\'s standard ACL system. Navigate to SeAT Configuration > Access Control to assign roles and permissions to users. Most corporation members should receive the "member" permission, directors get "director", and only administrators get "admin".',

    // CLI Commands
    'cli_intro' => 'Run commands via SSH from your SeAT installation directory. For Docker installations: docker exec -it seat-docker-front-1 php artisan command-name. For bare-metal: php artisan command-name',
    'cli_scheduled' => 'Scheduled Commands',
    'cli_scheduled_desc' => 'These commands run automatically via Laravel\'s task scheduler. You can also run them manually at any time:',
    'cli_diagnostic' => 'Diagnostic Commands',
    'cli_diagnostic_desc' => 'Use these commands to troubleshoot issues. They do not modify data unless specified:',
    'cli_manual' => 'Common Manual Commands',
    'cli_manual_desc' => 'Frequently used commands with their most useful options:',
    // Initialize Command
    'cli_initialize' => 'First-Time Setup Wizard',
    'cli_initialize_desc' => 'The initialize command is a guided setup wizard designed for first-time installations. It verifies your settings, populates current month data, and optionally backfills historical data. Run this once after installing Mining Manager and configuring your Moon Owner Corporation.',
    'cli_initialize_phase1' => 'Phase 1: Settings Verification',
    'cli_initialize_phase1_desc' => 'Checks that essential settings are configured: Moon Owner Corporation (required), price provider, and available ESI data. If Moon Owner Corporation is not set, the wizard will stop and ask you to configure it in Settings > General first.',
    'cli_initialize_phase2' => 'Phase 2: Current Month Data',
    'cli_initialize_phase2_desc' => 'Populates all mining data for the current month in the correct order:',
    'cli_initialize_step_prices' => 'Cache market prices from your configured provider',
    'cli_initialize_step_observer' => 'Process corporation observer mining data (last 35 days)',
    'cli_initialize_step_character' => 'Import character mining data from SeAT ESI cache',
    'cli_initialize_step_flags' => 'Backfill ore type classification flags',
    'cli_initialize_step_price_entries' => 'Stamp ISK values on all mining entries',
    'cli_initialize_step_summaries' => 'Generate daily summaries for the current month',
    'cli_initialize_step_extractions' => 'Update moon extraction data from ESI',
    'cli_initialize_step_jackpots' => 'Detect jackpot extractions from mining data',
    'cli_initialize_step_stats' => 'Calculate dashboard statistics',
    'cli_initialize_phase3' => 'Phase 3: Historical Backfill (Optional)',
    'cli_initialize_phase3_desc' => 'Optionally backfill older mining data for reports and analytics. You can choose 3, 6, or 12 months, or all available data. The wizard shows how far back your ESI data goes and lets you decide. Processing time depends on data volume and server performance.',
    'cli_initialize_warning' => 'Historical backfill rebuilds past mining history from what ESI still holds. Values use current prices, not the prices of the day.',
    'cli_initialize_options' => 'Options: --skip-checks (skip settings verification), --skip-backfill (skip historical data prompt)',

    'cli_data_management' => 'Data Management Commands',
    'cli_data_management_desc' => 'One-time or maintenance commands for data backfilling and cleanup:',
    'cli_test' => 'Test Commands (Development Only)',
    'cli_test_desc' => 'Commands for generating test data. Only use in development environments — they create fake data:',

    // Schedule Labels
    'schedule_1min' => 'Every minute',
    'schedule_30min' => 'Every 30 min',
    'schedule_2hours' => 'Every 2 hours',
    'schedule_4hours' => 'Every 4 hours',
    'schedule_daily' => 'Daily',
    'schedule_twice_daily' => 'Twice daily',
    'schedule_monthly' => '2nd of month',

    // ================================================================
    // Navigation - New Sections
    // ================================================================
    'webhooks_notifications' => 'Webhooks & Notifications',

    // ================================================================
    // Webhooks & Notifications
    // ================================================================
    'webhooks_notifications_title' => 'Webhooks & Notifications',
    'webhooks_notifications_intro' => 'Mining Manager can send notifications to Discord and Slack via webhooks. Each webhook is independently configured with its own URL and event toggles, so you can route different notification types to different channels.',

    'webhook_setup_title' => 'Setting Up a Webhook',
    'webhook_setup_desc' => 'To create a new webhook:',
    'webhook_setup_step_1' => 'Go to Settings > Webhooks tab and click "Add Webhook".',
    'webhook_setup_step_2' => 'Enter a name (for your reference), select the type (Discord, Slack, or Custom Webhook), and paste the webhook URL from your Discord/Slack channel settings (or your custom endpoint URL).',
    'webhook_setup_step_3' => 'Check the boxes for which notification types this webhook should receive. Each checkbox controls a specific event — only checked events are sent to this webhook.',
    'webhook_setup_step_4' => 'Optionally configure per-type notification settings on the Notifications tab: enable/disable role pinging with a Discord Role ID for specific notification types, and enable user pinging where a notification goes to particular pilots (requires seat-connector).',

    'webhook_multiple_title' => 'Multiple Webhooks',
    'webhook_multiple_desc' => 'You can create as many webhooks as you need. Each webhook works independently — when a notification fires, the system checks ALL enabled webhooks and sends to every one that has that event type toggled on. This lets you route notifications to different channels:',

    'webhook_example_officers' => '#officers',
    'webhook_example_officers_desc' => 'Webhook with everything enabled. All notifications go here for leadership visibility.',

    'webhook_toggles_title' => 'Notification Event Toggles',
    'webhook_toggles_desc' => 'Each webhook has independent toggles for every notification type. Only events with their checkbox enabled will be sent to that webhook:',

    'webhook_toggle_category' => 'Category',
    'webhook_toggle_events' => 'Events',
    'webhook_cat_moon' => 'Moon',
    'webhook_cat_moon_events' => 'Moon Chunk Ready (a chunk has arrived), Jackpot Detected, Moon Chunk Unstable (capital-pilots safety warning fired ~2h before the chunk enters the unstable phase), Extraction Started (a refinery started an extraction, naming who started it), Next Extraction Planned (the refinery\'s next planned pull, once a chunk is ready, and when a pull planned more than 56 days ahead can be started), Moon Scheduled Off-Plan (an extraction set in game away from the planned time), Refinery Gone (planned pulls taken off a refinery the corporation no longer owns), Moon Extraction Cancelled, Moon Not Rescheduled (a drill left idle after its chunk arrived), Moons Need Planning (one list of refineries short of planned pulls), Metenox Cargo Bay Full (a Metenox drill\'s bay is nearly full)',
    'webhook_cat_events' => 'Mining Events',
    'webhook_cat_events_list' => 'Event Created, Event Started, Event Completed',
    'webhook_cat_reports' => 'Reports',
    'webhook_cat_reports_events' => 'Report Generated (when a scheduled report completes)',
    'webhook_cat_health' => 'Plugin Health',
    'webhook_cat_health_events' => 'Price Provider Trouble (price refreshes started failing, the fallback provider took over or stopped answering, or they work again), Moon Scan Missing (moons your refineries drill with no scan in SeAT, valued from the game\'s notices until they are scanned; once per new reason, and daily as well if you switch that on)',

    'webhook_role_ping_title' => 'Discord Role Pinging',
    'webhook_role_ping_desc' => 'Role pinging is configured per notification type on the Notifications tab. Each notification type has its own "Ping Role" toggle and "Role ID" field, allowing you to ping different roles for different events (e.g., @directors for moon alerts, @miners for event announcements).',

    'webhook_role_ping_note' => 'User pinging uses seat-connector, which maps SeAT characters to Discord users for individual @mentions.',

    // ================================================================
    // Diagnostic Page
    // ================================================================
    'diagnostic_page_title' => 'Diagnostic Page (Web UI) — internal / dev only',
    'diagnostic_page_desc' => 'Internal admin-only tooling for troubleshooting. Intentionally NOT linked from the Mining Manager sidebar — admins reach it by typing /mining-manager/diagnostic in the address bar. It opens on Health Checks; the Master Test next to it is the read-only smoke chain, safe to run anytime. The per-area diagnostics remain for deep-dive debugging. The Test Data Generation tab creates fake corps/characters/mining for development environments only — do not use on a live install.',

    // Diagnostic → Notification Testing
    'notif_test_title' => 'Notification Testing',
    'notif_test_desc' => 'End-to-end testing for every notification type. Three modes of increasing scope:',
    'notif_test_preview' => 'Preview Test',
    'notif_test_preview_desc' => 'Renders the selected notification type + POSTs it to one webhook (either selected from the dropdown or a custom URL you paste in). Best for checking embed layout, colors, role pings, and single-webhook wiring. Does not write to the audit log.',
    'notif_test_live' => 'Fire Live Notification',
    'notif_test_live_desc' => 'Routes the selected type through the full NotificationService pipeline — fires to every subscribed, enabled webhook (respecting corp scoping + per-type toggles) and writes to the audit log. Use to verify one specific notification surface works end-to-end without waiting for a real triggering event.',
    'notif_test_chain' => 'Fire ALL (Chain)',
    'notif_test_chain_desc' => 'Sequentially fires the notification types through the full pipeline with a 1.5s delay between each, so it takes under a minute and every subscribed webhook receives one message per type in quick succession. Metenox Cargo Bay Full is left out of the chain; test it on its own. Post-deploy smoke test — confirms the consolidated pipeline still works for every surface end-to-end.',
    'notif_test_chain_tip' => 'Fire ALL is the fastest way to verify all notification types after a deploy. If any type fails, its specific error is highlighted in the log so you can drill into it with Fire Live Notification.',

    // ================================================================
    // Custom Webhooks
    // ================================================================
    'custom_webhook_title' => 'Custom Webhooks',
    'custom_webhook_desc' => 'Custom webhooks receive a generic JSON payload for each notification event. You can optionally provide a custom payload template. The payload includes: event_type, notification_type, data (all notification fields), and a UTC timestamp. Use this to integrate with third-party tools, logging systems, or custom bots.',

    // ================================================================
    // Per-Type Notification Settings
    // ================================================================
    'per_type_settings_title' => 'Per-Type Notification Settings',
    'per_type_settings_desc' => 'On the Notifications tab, each notification type has individual settings:',
    'per_type_ping_role' => 'Ping Role — When enabled, the specified Discord Role ID will be @mentioned in the notification. Configure a different role for different notification types (e.g., @directors for moon alerts, @miners for events).',
    'per_type_ping_user' => 'Ping User — Where a notification goes to particular pilots (event participants, for example), each one can be @mentioned. This requires the seat-connector package to map SeAT characters to Discord users.',

    // ================================================================
    // EVE Mail Status
    // ================================================================
    'evemail_status' => 'EVE Mail is not currently available as a notification channel. It requires the esi-mail.send_mail.v1 ESI scope, which is not included in SeAT\'s default SSO configuration. This may be enabled in a future update.',
];
