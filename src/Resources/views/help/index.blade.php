@extends('web::layouts.grids.12')

@section('title', trans('mining-manager::help.help_documentation'))
@section('page_header', trans('mining-manager::help.help_documentation'))

@push('head')
@include('mining-manager::partials.head')
<style>
    .help-wrapper {
        display: flex;
        gap: 20px;
    }

    .help-sidebar {
        flex: 0 0 280px;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }

    .help-content {
        flex: 1;
        min-width: 0;
        color: #d1d5db !important;
    }

    .help-nav .nav-link {
        color: #e2e8f0 !important;
        border-radius: 5px;
        margin-bottom: 5px;
        padding: 10px 15px;
        transition: all 0.3s;
        font-size: 0.95rem;
    }

    .help-nav .nav-link:hover {
        background: rgba(102, 126, 234, 0.2);
    }

    .help-nav .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .help-nav .nav-link i {
        width: 24px;
        text-align: center;
        margin-right: 10px;
    }

    .help-section {
        display: none;
        animation: fadeIn 0.3s;
    }

    .help-section.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .help-card {
        background: #2d3748;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .help-card h3 {
        color: #667eea !important;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .help-card h4 {
        color: #9ca3af !important;
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 1.1rem;
    }

    .help-card h5 {
        color: #e2e8f0 !important;
        margin-top: 15px;
        margin-bottom: 8px;
    }

    .help-card p {
        color: #d1d5db !important;
        line-height: 1.6;
    }

    .help-card ul, .help-card ol {
        color: #d1d5db !important;
        line-height: 1.8;
        margin-left: 20px;
    }

    .help-card strong {
        color: #e2e8f0 !important;
    }

    .help-card li {
        color: #d1d5db !important;
    }

    .help-card code {
        background: rgba(0, 0, 0, 0.3);
        padding: 2px 6px;
        border-radius: 3px;
        color: #fbbf24 !important;
        font-size: 0.9em;
    }

    .help-card pre {
        background: rgba(0, 0, 0, 0.3);
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        color: #d1d5db !important;
    }

    .step-by-step {
        counter-reset: step-counter;
        list-style: none;
        padding-left: 0;
    }

    .step-by-step li {
        counter-increment: step-counter;
        margin-bottom: 20px;
        padding-left: 50px;
        position: relative;
        color: #d1d5db !important;
    }

    .step-by-step li::before {
        content: counter(step-counter);
        position: absolute;
        left: 0;
        top: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .info-box,
    .warning-box,
    .success-box {
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
        color: #d1d5db !important;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    .info-box {
        background: rgba(23, 162, 184, 0.15);
        border-left: 4px solid #17a2b8;
    }

    .warning-box {
        background: rgba(255, 193, 7, 0.15);
        border-left: 4px solid #ffc107;
    }

    .success-box {
        background: rgba(28, 200, 138, 0.15);
        border-left: 4px solid #1cc88a;
    }

    .info-box > i,
    .warning-box > i,
    .success-box > i {
        margin-top: 3px;
        flex-shrink: 0;
    }

    .info-box > i { color: #17a2b8; }
    .warning-box > i { color: #ffc107; }
    .success-box > i { color: #1cc88a; }

    /* Override the inherited .help-card h4/h5/p/ul/ol/small/strong colours
       so text inside a tinted box has high contrast. The default
       .help-card h4 (#9ca3af medium grey) is barely readable on a
       green/blue/yellow 15%-opacity tint background; this lifts every
       text element inside a tinted box to a near-white that pops against
       any of the three background tints uniformly.
       NB: covers BOTH our custom .info-box/.warning-box/.success-box
       classes AND Bootstrap's .alert.alert-info/.alert-secondary/etc.
       (the help blade uses both patterns in practice). */
    .info-box h3, .info-box h4, .info-box h5,
    .warning-box h3, .warning-box h4, .warning-box h5,
    .success-box h3, .success-box h4, .success-box h5,
    .alert h3, .alert h4, .alert h5 {
        color: #f9fafb !important;
    }
    .info-box p, .info-box ul, .info-box ol, .info-box li, .info-box small,
    .warning-box p, .warning-box ul, .warning-box ol, .warning-box li, .warning-box small,
    .success-box p, .success-box ul, .success-box ol, .success-box li, .success-box small,
    .alert p, .alert ul, .alert ol, .alert li, .alert small {
        color: #f3f4f6 !important;
    }
    .info-box strong, .warning-box strong, .success-box strong,
    .alert strong {
        color: #ffffff !important;
    }
    /* Bootstrap's `.text-muted` is `color: #6c757d !important` and ties
       on specificity with the box overrides above, so cascade order
       decides which wins — and Bootstrap usually loads later. Force
       text-muted inside tinted boxes to respect the box's contrast
       baseline by bumping specificity (chained class selector). */
    .info-box .text-muted,
    .warning-box .text-muted,
    .success-box .text-muted,
    .alert .text-muted,
    .info-box small.text-muted,
    .warning-box small.text-muted,
    .success-box small.text-muted,
    .alert small.text-muted {
        color: #d1d5db !important;
        opacity: 0.95;
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .feature-item {
        background: rgba(102, 126, 234, 0.1);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid rgba(102, 126, 234, 0.3);
    }

    /* Scoped to direct-child icons only (the big feature-card top icon).
       Without the "> i" combinator this selector also hits nested <i>
       elements inside badges, forcing badge icons to render indigo
       regardless of the badge background — barely visible on a green
       badge-success or red badge-danger. Direct-child leaves badge /
       inline icons alone so they inherit the badge's white text. */
    .feature-item > i {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 10px;
    }

    .feature-item h5 {
        color: #e2e8f0 !important;
        margin-bottom: 8px;
    }

    .feature-item p {
        color: #9ca3af !important;
        font-size: 0.9rem;
        margin: 0;
    }

    .search-box {
        position: relative;
        margin-bottom: 20px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 45px 12px 15px;
        background: #2d3748;
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 8px;
        color: #e2e8f0 !important;
    }

    .search-box i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin: 20px 0;
    }

    .quick-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        color: white;
        text-decoration: none;
        transition: transform 0.2s;
    }

    .quick-link:hover {
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    .quick-link i {
        font-size: 2rem;
        display: block;
        margin-bottom: 8px;
    }

    .faq-item {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .faq-question {
        padding: 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
        color: #e2e8f0 !important;
    }

    .faq-question:hover {
        background: rgba(102, 126, 234, 0.1);
    }

    .faq-question i {
        transition: transform 0.3s;
    }

    .faq-item.open .faq-question i {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 15px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s;
        color: #d1d5db !important;
    }

    .faq-item.open .faq-answer {
        padding: 15px;
        max-height: 500px;
    }

    .plugin-info-table {
        width: 100%;
        color: #d1d5db !important;
    }

    .plugin-info-table td {
        padding: 8px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .plugin-info-table td:first-child {
        color: #9ca3af !important;
        width: 120px;
        font-weight: 600;
    }

    .plugin-info-table td:last-child {
        color: #e2e8f0 !important;
    }

    /* Ore classification flow: plain boxes and arrows, so it needs no
       diagram library and still reads on a narrow screen. */
    .ore-flow {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        margin: 20px 0;
    }

    .ore-flow-node {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.35);
        border-radius: 8px;
        padding: 10px 14px;
        text-align: center;
        width: 100%;
        max-width: 420px;
    }

    .ore-flow-node strong {
        display: block;
        color: #e2e8f0 !important;
    }

    .ore-flow-node span {
        display: block;
        margin-top: 2px;
        font-size: 0.85rem;
        color: #9ca3af !important;
    }

    .ore-flow-arrow {
        color: #667eea;
        line-height: 1;
    }

    .ore-flow-row,
    .ore-flow-branches {
        display: grid;
        gap: 10px;
        width: 100%;
    }

    .ore-flow-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        max-width: 700px;
    }

    .ore-flow-branches {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ore-flow-row .ore-flow-node,
    .ore-flow-branches .ore-flow-node {
        max-width: none;
    }

    .ore-flow-registry {
        background: rgba(28, 200, 138, 0.1);
        border-color: rgba(28, 200, 138, 0.5);
    }

    .ore-flow-ledger {
        background: rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.7);
    }

    .ore-flow-skip {
        background: rgba(220, 53, 69, 0.1);
        border: 1px dashed rgba(220, 53, 69, 0.6);
    }

    .ore-flow-unknown {
        background: rgba(255, 193, 7, 0.1);
        border-color: rgba(255, 193, 7, 0.55);
    }

    @media (max-width: 768px) {
        .ore-flow-row,
        .ore-flow-branches {
            grid-template-columns: 1fr;
        }
    }

    /* Earlier updates of the same major version, folded away under the latest
       one in the Recent changes box. */
    .recent-changes-box details {
        margin-top: 18px;
        padding-top: 10px;
        border-top: 1px solid rgba(139, 156, 247, 0.25);
    }

    .recent-changes-box summary {
        color: #8b9cf7 !important;
        font-weight: 600;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .help-wrapper {
            flex-direction: column;
        }

        .help-sidebar {
            position: relative;
            max-height: none;
        }

        .feature-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@include('mining-manager::partials.theme')
@endpush

@section('full')
<div class="mining-manager-wrapper mining-dashboard help-page">

    <div class="help-wrapper">
        {{-- Sidebar Navigation --}}
        <div class="help-sidebar">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-compass"></i>
                        {{ trans('mining-manager::help.navigation') }}
                    </h3>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column help-nav">
                        <li class="nav-item">
                            <a href="#" class="nav-link active" data-section="overview">
                                <i class="fas fa-info-circle"></i>
                                {{ trans('mining-manager::help.overview') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="getting-started">
                                <i class="fas fa-rocket"></i>
                                {{ trans('mining-manager::help.getting_started') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="dashboard">
                                <i class="fas fa-tachometer-alt"></i>
                                {{ trans('mining-manager::help.dashboard') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="ore-classification">
                                <i class="fas fa-gem"></i>
                                Ore Classification
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="webhooks-notifications">
                                <i class="fas fa-bell"></i>
                                {{ trans('mining-manager::help.webhooks_notifications') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="events">
                                <i class="fas fa-calendar-alt"></i>
                                {{ trans('mining-manager::help.mining_events') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="moon-mining">
                                <i class="fas fa-moon"></i>
                                {{ trans('mining-manager::help.moon_mining') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="moon-planner">
                                <i class="fas fa-calendar-check"></i>
                                Moon Planner
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="find-moons">
                                <i class="fas fa-search-location"></i>
                                Find Moons
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="analytics">
                                <i class="fas fa-chart-line"></i>
                                {{ trans('mining-manager::help.analytics_reports') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="settings">
                                <i class="fas fa-cog"></i>
                                {{ trans('mining-manager::help.settings') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="commands">
                                <i class="fas fa-terminal"></i>
                                {{ trans('mining-manager::help.cli_commands') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="permissions">
                                <i class="fas fa-shield-alt"></i>
                                {{ trans('mining-manager::help.permissions') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="faq">
                                <i class="fas fa-question-circle"></i>
                                {{ trans('mining-manager::help.faq') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="custom-styling">
                                <i class="fas fa-paint-brush"></i>
                                {{ trans('mining-manager::help.custom_styling') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-section="troubleshooting">
                                <i class="fas fa-wrench"></i>
                                {{ trans('mining-manager::help.troubleshooting') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="help-content">

            {{-- Search Box --}}
            <div class="search-box">
                <input type="text"
                       id="helpSearch"
                       placeholder="{{ trans('mining-manager::help.search_placeholder') }}"
                       class="form-control">
                <i class="fas fa-search"></i>
            </div>

            {{-- Overview Section --}}
            <div id="overview" class="help-section active">
                {{-- Plugin Information --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        {{ trans('mining-manager::help.plugin_information') }}
                    </h3>
                    <p>
                        Version: <img src="https://img.shields.io/packagist/v/bahu90/mining-manager?label=release&color=667eea" alt="version" style="vertical-align: middle;">
                        <img src="https://img.shields.io/badge/SeAT-5.0-764ba2" alt="SeAT 5.0" style="vertical-align: middle;">
                    </p>
                    <p>License: {{ trans('mining-manager::help.plugin_license') }}</p>
                    <p>
                        <i class="fas fa-user"></i> Maintainer: {{ trans('mining-manager::help.plugin_author') }}<br>
                        <i class="fas fa-code-branch"></i> {{ trans('mining-manager::help.plugin_based_on') }}
                    </p>

                    <div class="quick-links" style="margin-top: 15px;">
                        <a href="https://github.com/Bahu90/EVE-Mining-Manager" class="quick-link" target="_blank" style="padding: 10px;">
                            <i class="fas fa-code-branch" style="font-size: 1rem; margin-bottom: 4px;"></i>
                            {{ trans('mining-manager::help.github_repository') }}
                        </a>
                        <a href="https://github.com/Bahu90/EVE-Mining-Manager/blob/main/CHANGELOG.md" class="quick-link" target="_blank" style="padding: 10px;">
                            <i class="fas fa-list" style="font-size: 1rem; margin-bottom: 4px;"></i>
                            {{ trans('mining-manager::help.full_changelog') }}
                        </a>
                        <a href="https://github.com/Bahu90/EVE-Mining-Manager/issues" class="quick-link" target="_blank" style="padding: 10px;">
                            <i class="fas fa-bug" style="font-size: 1rem; margin-bottom: 4px;"></i>
                            {{ trans('mining-manager::help.report_issues') }}
                        </a>
                        <a href="https://github.com/Bahu90/EVE-Mining-Manager/blob/main/README.md" class="quick-link" target="_blank" style="padding: 10px;">
                            <i class="fas fa-book" style="font-size: 1rem; margin-bottom: 4px;"></i>
                            {{ trans('mining-manager::help.readme') }}
                        </a>
                    </div>

                    <div class="success-box" style="margin-top: 20px;">
                        <i class="fas fa-heart"></i>
                        <div>
                            <strong>{{ trans('mining-manager::help.support_the_project') }}:</strong>
                            <ul style="margin-top: 8px; margin-bottom: 0;">
                            <li>&#11088; {{ trans('mining-manager::help.support_star') }}</li>
                            <li>&#128295; {{ trans('mining-manager::help.support_issues') }}</li>
                            <li>&#128161; {{ trans('mining-manager::help.support_features') }}</li>
                            <li>&#128295; {{ trans('mining-manager::help.support_contribute') }}</li>
                            <li>&#127775; {{ trans('mining-manager::help.support_share') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Version Status — installed vs latest on Packagist. --}}
                @php
                    $vs = $versionStatus ?? ['current' => '?', 'current_source' => 'config', 'is_dev_branch' => false, 'latest' => null, 'status' => 'unknown', 'message' => '', 'release_url' => null];
                    $statusBadgeClass = [
                        'current'    => 'badge-success',
                        'outdated'   => 'badge-warning',
                        'ahead'      => 'badge-info',
                        'dev_branch' => 'badge-info',
                        'unknown'    => 'badge-secondary',
                    ][$vs['status']] ?? 'badge-secondary';
                    $statusLabel = [
                        'current'    => '&#10003; Up to date',
                        'outdated'   => '&#9888; Update available',
                        'ahead'      => '&#128640; Pre-release',
                        'dev_branch' => '&#127793; Development branch',
                        'unknown'    => '&mdash; Unable to check',
                    ][$vs['status']] ?? '&mdash; Unknown';
                    // Show the raw branch ref as-is (no 'v' prefix); tagged versions get the v.
                    $installedDisplay = $vs['is_dev_branch'] ? $vs['current'] : ('v' . $vs['current']);
                    $sourceHint = $vs['current_source'] === 'composer'
                        ? "resolved via Composer's installed.json"
                        : 'resolved via mining-manager.config.php (fallback, Composer metadata unavailable)';
                @endphp
                <div class="help-card">
                    <h3><i class="fas fa-tag"></i> Version Status</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin: 0.5rem 0;">
                        <div>
                            <strong>Installed:</strong>
                            <span class="badge badge-secondary" style="font-size: 0.9rem;" title="{{ $sourceHint }}">
                                {{ $installedDisplay }}
                            </span>
                        </div>
                        <div>
                            <strong>Latest release:</strong>
                            @if($vs['latest'])
                                <span class="badge badge-secondary" style="font-size: 0.9rem;">v{{ $vs['latest'] }}</span>
                            @else
                                <span class="badge badge-secondary" style="font-size: 0.9rem;">unknown</span>
                            @endif
                        </div>
                        <div>
                            <span class="badge {{ $statusBadgeClass }}" style="font-size: 0.9rem;">{!! $statusLabel !!}</span>
                        </div>
                        @if($vs['release_url'])
                            <div>
                                <a href="{{ $vs['release_url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-mm-primary">
                                    <i class="fas fa-external-link-alt"></i> View release notes
                                </a>
                            </div>
                        @endif
                    </div>
                    <small class="text-muted">{{ $vs['message'] }}</small>
                    @if($vs['is_dev_branch'] ?? false)
                        @php($commit = $vs['commit'] ?? null)
                        @if($commit)
                            {{-- A branch name says nothing about what is actually deployed, and a
                                 production stack cannot be rebooted per commit, so name the commit.
                                 .info-box lays its children out in a row, so each line needs its own
                                 block and the box has to be told to stack them. --}}
                            <div class="info-box" style="margin-top: 0.75rem; flex-direction: column; align-items: flex-start; min-height: 0;">
                                <div>
                                    <i class="fas fa-code-branch"></i>
                                    <strong>Running commit:</strong>
                                    <a href="{{ $commit['url'] }}" target="_blank" rel="noopener"><code>{{ $commit['short'] }}</code></a>
                                    @if($commit['subject'])
                                        &mdash; {{ $commit['subject'] }}
                                    @endif
                                    @if($commit['date'])
                                        <small class="text-muted">({{ \Carbon\Carbon::parse($commit['date'])->format('Y-m-d H:i') }} EVE time)</small>
                                    @endif
                                </div>
                                <div style="margin-top: 0.4rem;">
                                    @if($commit['behind'] === null)
                                        <small class="text-muted">Could not reach GitHub to see what has landed since. The commit id above still tells you exactly what is deployed.</small>
                                    @elseif($commit['behind'] === 0)
                                        <small class="text-muted">Nothing newer on <code>{{ $commit['branch'] }}</code>: this install is at the head of the branch.</small>
                                    @else
                                        <a href="{{ $commit['compare_url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-mm-primary">
                                            <i class="fas fa-list"></i> {{ $commit['behind'] }} commit(s) on {{ $commit['branch'] }} since this one
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                    @if($vs['status'] === 'outdated')
                        <div class="info-box" style="margin-top: 0.75rem; flex-direction: column; align-items: stretch; min-height: 0;">
                            <div>
                                <i class="fas fa-arrow-circle-up"></i>
                                <strong>Upgrade recipe (SeAT Docker stack):</strong>
                            </div>
                            <pre style="margin-top: 0.4rem; margin-bottom: 0;"><code>docker compose -f docker-compose.yml -f docker-compose.mariadb.yml -f docker-compose.traefik.yml down
docker compose -f docker-compose.yml -f docker-compose.mariadb.yml -f docker-compose.traefik.yml up -d</code></pre>
                            <small class="text-muted" style="display: block; margin-top: 0.4rem;">
                                Container boot pulls the latest plugin via composer, runs new migrations, and re-seeds schedules automatically.
                            </small>
                        </div>
                    @endif
                    <small class="text-muted" style="display: block; margin-top: 0.4rem; font-size: 0.75rem;">
                        <i class="fas fa-info-circle"></i>
                        Installed version {{ $sourceHint }}. Latest checked via Packagist's public API (6h cache, safe on outages).
                        @if($vs['is_dev_branch'] ?? false)
                            The running commit comes from Composer and what has landed since from GitHub's public API, both cached for 6 hours.
                        @endif
                    </small>
                </div>

                {{-- Welcome --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-gem"></i>
                        {{ trans('mining-manager::help.welcome_title') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.welcome_desc') }}</p>
                </div>

                {{-- What's New is the picture of the whole major version. The changelog keeps
                     the full record of every release. --}}
                <div class="whats-new-box">
                    <h3>
                        <i class="fas fa-puzzle-piece"></i>
                        What's New in v3
                        <span class="whats-new-tag">tracking only</span>
                    </h3>
                    <p>
                        Mining Manager tracks mining and moons. Set your Moon Owner Corporation and the ledger,
                        moon tracking, planner and alerts all run with nothing but SeAT installed.
                    </p>

                    <h4><i class="fas fa-eraser"></i> No more taxes</h4>
                    <ul>
                        <li>
                            Mining tax, tax codes, invoices, payments, balances and theft detection are gone from
                            every page, every notification and the menu. Nothing is billed, chased or shown to
                            members.
                        </li>
                        <li>
                            Data recorded by earlier versions stays in the database untouched; the plugin simply
                            no longer reads it.
                        </li>
                    </ul>

                    <h4><i class="fas fa-calendar-check"></i> Moon pulls are coordinated, not controlled</h4>
                    <ul>
                        <li>
                            SeAT can only read the extractions a director starts in game. The
                            <strong>Moon Planner</strong> is where the corporation agrees when each refinery
                            should pull, so chunks do not land faster than your miners can clear them, and it
                            flags a pull that was set off-plan.
                        </li>
                        <li>
                            A <strong>blueprint</strong> lays a whole rotation of pulls over the planner at once,
                            and the planner warns rather than blocks when a pull lands too close to another.
                        </li>
                        <li>
                            Chunk arrivals, unstable warnings, extractions starting or cancelled, the next planned
                            pull and off-plan pulls each have their own alert, and a Metenox drill warns you when
                            its bay is nearly full.
                        </li>
                    </ul>

                    <h4><i class="fas fa-stethoscope"></i> You can see what it is doing</h4>
                    <ul>
                        <li>
                            The <strong>Master Test</strong> on the Diagnostic page checks the whole install in
                            one click, without changing anything.
                        </li>
                        <li>
                            Times are kept in EVE time, and hovering over one shows it in your own timezone.
                        </li>
                    </ul>
                </div>

                <div class="recent-changes-box">
                    <h4><i class="fas fa-history"></i> In this update (3.0.0)</h4>
                    <p>
                        <a href="https://github.com/Bahu90/EVE-Mining-Manager/blob/main/CHANGELOG.md" target="_blank" rel="noopener">The changelog</a>
                        has the complete record, including every earlier release.
                    </p>
                    <ul>
                        <li><strong>Taxes removed.</strong> Tax Management, My Taxes, tax codes, wallet verification, balances, refunds and the tax notifications are gone.</li>
                        <li><strong>Theft detection removed.</strong> It worked from unpaid tax bills, so it went with them.</li>
                        <li><strong>Dashboards, ledger, reports and analytics</strong> show mined quantities and values only.</li>
                        <li><strong>The Extraction Simulator</strong> keeps the Metenox output card; the refinery-versus-Metenox tax comparison is gone.</li>
                    </ul>
                </div>

                {{-- What is Mining Manager? --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        {{ trans('mining-manager::help.what_is_mining_manager') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.what_is_mining_manager_desc') }}</p>

                    <div class="info-box">
                        <i class="fas fa-lightbulb"></i>
                        <strong>{{ trans('mining-manager::help.key_benefits') }}:</strong> {{ trans('mining-manager::help.key_benefits_desc') }}
                    </div>
                </div>

                {{-- Core Features --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-star"></i>
                        {{ trans('mining-manager::help.core_features') }}
                    </h3>
                    <div class="feature-grid">
                        <div class="feature-item">
                            <i class="fas fa-book"></i>
                            <h5>{{ trans('mining-manager::help.feature_ledger') }}</h5>
                            <p>{{ trans('mining-manager::help.feature_ledger_desc') }}</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-moon"></i>
                            <h5>{{ trans('mining-manager::help.feature_moon') }}</h5>
                            <p>{{ trans('mining-manager::help.feature_moon_desc') }}</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-calendar-check"></i>
                            <h5>Moon Planner</h5>
                            <p>Plan and stagger your refinery pulls across a three-month calendar so chunks don't
                               land on top of each other. Lay a repeating blueprint over it or project each moon's
                               next pull from its own history, get warned when pulls fall too close together, and
                               hear about moons scheduled off-plan, drills left idle and moons with nothing planned.</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-calendar-alt"></i>
                            <h5>{{ trans('mining-manager::help.feature_events') }}</h5>
                            <p>{{ trans('mining-manager::help.feature_events_desc') }}</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-bar"></i>
                            <h5>{{ trans('mining-manager::help.feature_analytics') }}</h5>
                            <p>{{ trans('mining-manager::help.feature_analytics_desc') }}</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-rocket"></i>
                            <h5>Master Test Diagnostic</h5>
                            <p>A one-click, read-only check of the whole install on the Diagnostic page: schema, settings, pricing, notifications, the mining imports, the moon lifecycle and security. Run it after an update or a settings change rather than reading logs.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Getting Started Section --}}
            <div id="getting-started" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-rocket"></i>
                        {{ trans('mining-manager::help.getting_started') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.getting_started_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.quick_start_guide') }}</h4>
                    <ol class="step-by-step">
                        <li>
                            <strong>{{ trans('mining-manager::help.step_1_title') }}</strong><br>
                            {{ trans('mining-manager::help.step_1_desc') }}
                        </li>
                        <li>
                            <strong>{{ trans('mining-manager::help.step_4_title') }}</strong><br>
                            {{ trans('mining-manager::help.step_4_desc') }}
                        </li>
                        <li>
                            <strong>{{ trans('mining-manager::help.step_5_title') }}</strong><br>
                            {{ trans('mining-manager::help.step_5_desc') }}
                        </li>
                    </ol>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>{{ trans('mining-manager::help.tip') }}:</strong> {{ trans('mining-manager::help.getting_started_tip') }}
                    </div>
                </div>
            </div>

            {{-- Dashboard Section --}}
            <div id="dashboard" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-tachometer-alt"></i>
                        {{ trans('mining-manager::help.dashboard_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.dashboard_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.member_dashboard') }}</h4>
                    <p>{{ trans('mining-manager::help.member_dashboard_desc') }}</p>
                    <ul>
                        <li><strong>{{ trans('mining-manager::help.personal_stats') }}:</strong> {{ trans('mining-manager::help.personal_stats_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.recent_activity') }}:</strong> {{ trans('mining-manager::help.recent_activity_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.upcoming_events') }}:</strong> {{ trans('mining-manager::help.upcoming_events_desc') }}</li>
                    </ul>

                    <h4>{{ trans('mining-manager::help.director_dashboard') }}</h4>
                    <p>{{ trans('mining-manager::help.director_dashboard_desc') }}</p>
                    <ul>
                        <li><strong>{{ trans('mining-manager::help.corp_overview') }}:</strong> {{ trans('mining-manager::help.corp_overview_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.top_miners') }}:</strong> {{ trans('mining-manager::help.top_miners_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.active_events') }}:</strong> {{ trans('mining-manager::help.active_events_desc') }}</li>
                    </ul>

                    <h4>{{ trans('mining-manager::help.dashboard_charts') }}</h4>
                    <p>{{ trans('mining-manager::help.dashboard_charts_desc') }}</p>
                    <ul>
                        <li><strong>{{ trans('mining-manager::help.chart_mining_performance') }}</strong></li>
                        <li><strong>{{ trans('mining-manager::help.chart_moon_mining') }}</strong></li>
                        <li><strong>{{ trans('mining-manager::help.chart_mining_by_group') }}</strong></li>
                        <li><strong>{{ trans('mining-manager::help.chart_mining_by_type') }}</strong></li>
                    </ul>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>{{ trans('mining-manager::help.note') }}:</strong> {{ trans('mining-manager::help.dashboard_note') }}
                    </div>
                </div>
            </div>

            {{-- Ore Classification Section --}}
            <div id="ore-classification" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-gem"></i>
                        How ore classification works
                    </h3>
                    <p>
                        Every piece of mining in the ledger carries a category, and the category decides where it is
                        counted on the dashboards, analytics and reports. That is decided once, when the mining is
                        imported, and it follows the same path for character mining and for moon observer data.
                    </p>

                    <div class="ore-flow">
                        <div class="ore-flow-node">
                            <strong>EVE ESI</strong>
                        </div>
                        <div class="ore-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                        <div class="ore-flow-node">
                            <strong>SeAT</strong>
                            <span>Character mining and moon observer data, stored exactly as received</span>
                        </div>
                        <div class="ore-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                        <div class="ore-flow-node">
                            <strong>Mining Manager imports</strong>
                            <span>The personal mining import and the observer import</span>
                        </div>
                        <div class="ore-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                        <div class="ore-flow-row">
                            <div class="ore-flow-node ore-flow-registry">
                                <strong>TypeIdRegistry</strong>
                                <span>Knows every mining type ID, by family. Can be checked against the SDE</span>
                            </div>
                            <div class="ore-flow-node">
                                <strong>OreClassifier</strong>
                                <span>Decides what each type ID means: skip it, or which category it gets</span>
                            </div>
                        </div>
                        <div class="ore-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                        <div class="ore-flow-branches">
                            <div class="ore-flow-node ore-flow-skip">
                                <strong><i class="fas fa-ban"></i> Skipped</strong>
                                <span>Event ore, quest ore, Mutanite and Zuthrine. They never reach the ledger</span>
                            </div>
                            <div class="ore-flow-node ore-flow-ledger">
                                <strong><i class="fas fa-book"></i> Mining ledger</strong>
                                <span>Moon ore R4 to R64, ice, gas, abyssal, triglavian or regular ore</span>
                            </div>
                            <div class="ore-flow-node ore-flow-unknown">
                                <strong><i class="fas fa-question-circle"></i> Not in the registry</strong>
                                <span>Goes into the ledger as regular ore, and the Master Test lists it</span>
                            </div>
                        </div>
                        <div class="ore-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                        <div class="ore-flow-node">
                            <strong>Ore values</strong>
                            <span>From your price provider, with reprocessing yields from the SDE</span>
                        </div>
                        <div class="ore-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                        <div class="ore-flow-row">
                            <div class="ore-flow-node">
                                <strong>Daily summaries</strong>
                            </div>
                            <div class="ore-flow-node">
                                <strong>Dashboard, analytics and reports</strong>
                            </div>
                        </div>
                    </div>

                    <h4><i class="fas fa-sitemap"></i> The three parts</h4>
                    <ul>
                        <li>
                            <strong>TypeIdRegistry</strong> is the single source of truth. It lists every type ID the
                            plugin knows, grouped by family: moon ore by rarity, ice, gas, abyssal, triglavian, regular
                            ore, the compressed forms, event and quest ore, and Mutanite. It holds data only, no rules,
                            and it is the one place to update when CCP releases new ore.
                        </li>
                        <li>
                            <strong>OreClassifier</strong> is the one place that decides what a type ID means for the
                            plugin: whether it is skipped, and which ledger category it gets. Both imports, the mining
                            event tally, daily summaries, the dashboard and analytics all ask it, so they cannot
                            disagree.
                        </li>
                        <li>
                            <strong>SeAT's static data (SDE)</strong> supports the registry but never decides a
                            category. It supplies type names, the reprocessing yields behind refined ore values, and a
                            way to check that every registry ID really exists. Moon
                            rarity tiers and ledger categories are Mining Manager ideas rather than EVE ones, and CCP's
                            own groups do not line up with them.
                        </li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-tags"></i>
                        Categories
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <thead>
                                <tr>
                                    <th style="color: #9ca3af;">Category</th>
                                    <th style="color: #9ca3af;">What it covers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Moon ore R4 to R64</td>
                                    <td>Moon ore, by rarity</td>
                                </tr>
                                <tr>
                                    <td>Ice</td>
                                    <td>Ice and compressed ice</td>
                                </tr>
                                <tr>
                                    <td>Gas</td>
                                    <td>Harvested gas, fullerites included</td>
                                </tr>
                                <tr>
                                    <td>Abyssal</td>
                                    <td>Bezdnacine, Rakovene and Talassonite</td>
                                </tr>
                                <tr>
                                    <td>Triglavian</td>
                                    <td>The registry's Triglavian ore list</td>
                                </tr>
                                <tr>
                                    <td>Regular ore</td>
                                    <td>Every other registered ore, including the Deep Space Survey and Ore Prospecting Array families</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        Moon ore is decided first, so a moon rock counts as moon ore whatever else it might also be.
                    </p>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-ban"></i>
                        What is left out
                    </h3>
                    <p>
                        Ore that only exists through limited-time events, quests and mission content is left out
                        entirely: Tyranite, Nephrite, Dense Moissanite, Amethystic Crystallite, Hiemal Tricarboxyl
                        Condensate, Volatile Ice and Veldspar Isotope. It is not imported, valued or charted.
                        One-off spikes would distort the figures used to judge normal activity.
                    </p>
                    <p>
                        All six kinds of Mutanite are left out too: Amperum, Peregrinus, Conflagrati, Solis, Tenebraet
                        and Admixti (<code>77118</code>, <code>77418</code> to <code>77421</code> and <code>77524</code>).
                        Homefront Operations run permanently, so it is not event ore, but it cannot be reprocessed and
                        price sources often have no price for it. Counted, it only sat in the ledger as regular ore
                        worth nothing.
                    </p>
                    <p>
                        <strong>Zuthrine</strong> (<code>28626</code>) is left out as well. EVE gives it full flavour
                        text, a Mercoxit-family rock full of Morphite that needs deep core mining, but it has no
                        market group, so it cannot be sold, and no reprocessing output, so it cannot be refined.
                        There is no route to a value for it and there never will be. It appears to be objective ore
                        that something asks you to mine and hand in.
                    </p>
                    <p>
                        All three are skipped when mining is imported and when mining events are tallied, so nothing
                        further along ever sees them. Rows of any of these already in your ledger are left as they
                        were.
                    </p>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-question-circle"></i>
                        Types the registry does not know
                    </h3>
                    <p>
                        When CCP releases a new ore before the plugin has caught up, that mining is not skipped. It goes
                        into the ledger as regular ore, which is right for most new ore. Skipping it would lose it for good: the imports only read recent days, so nothing goes
                        back for it once the registry is updated.
                    </p>
                    <p>
                        The Master Test on the Diagnostic page has an <strong>Unrecognised ore types</strong> check. It
                        lists any type mined in the last 30 days that the registry does not know, with its name from the
                        SDE. Anything it shows needs adding to the registry in a plugin update.
                    </p>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-stethoscope"></i>
                        Checking it on your install
                    </h3>
                    <ul>
                        <li>
                            <strong>Master Test, Unrecognised ore types:</strong> ore mined in the last 30 days that the
                            registry does not know.
                        </li>
                        <li>
                            <strong>Master Test, Ignored ore left out:</strong> warns if event ore, quest ore, Mutanite or
                            Zuthrine still reaches the ledger.
                        </li>
                        <li>
                            <code>mining-manager:diagnose-type-ids --verify-db</code> checks every registry ID against
                            your SDE. Add <code>--category=event</code> or <code>--category=mutanite</code> to check one
                            list.
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Webhooks & Notifications --}}
            <div id="webhooks-notifications" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-bell"></i>
                        {{ trans('mining-manager::help.webhooks_notifications_title') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.webhooks_notifications_intro') }}</p>

                    {{-- Architecture explanation: two decoupled systems --}}
                    <h4><i class="fas fa-sitemap text-info"></i> How Moon Arrival Notifications Work</h4>
                    <p>Mining Manager uses <strong>two decoupled systems</strong> for moon arrival notifications:</p>
                    <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                        <div class="feature-item" style="border-left: 4px solid #17a2b8;">
                            <h5><i class="fas fa-satellite-dish text-info"></i> State System (ESI-driven)</h5>
                            <ul class="mb-2">
                                <li><code>update-extractions</code> runs every 2h</li>
                                <li>Pulls from ESI and updates <code>chunk_arrival_time</code>, <code>natural_decay_time</code>, <code>fractured_at</code>, status</li>
                                <li>Answers: <em>"what does EVE say is happening?"</em></li>
                            </ul>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #28a745;">
                            <h5><i class="fas fa-clock text-success"></i> Notification System (time-driven)</h5>
                            <ul class="mb-2">
                                <li><code>check-extraction-arrivals</code> runs every 1 min</li>
                                <li>Reads stored <code>chunk_arrival_time</code>, compares to <code>now()</code></li>
                                <li>Answers: <em>"has arrival time passed and we haven't notified yet?"</em></li>
                                <li>Idempotent via <code>notification_sent</code> flag</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Mental model:</strong> ESI tells us <em>what</em> is happening. The clock tells us <em>when</em> to notify.
                        <br><small>Arrivals notify within ~60 seconds of actual chunk arrival, regardless of ESI refresh timing or outages. The <code>chunk_arrival_time</code> is known the moment an extraction is first imported (days or weeks before arrival); the notification watchdog just compares it to the current time.</small>
                    </div>

                    <div class="alert alert-secondary mt-2">
                        <i class="fas fa-ban text-warning"></i>
                        <strong>Cancellation handling:</strong> If a director cancels an extraction in-game before chunk arrival, EVE sends a <code>MoonminingExtractionCancelled</code> character notification. The state system picks this up on its next 2h ESI poll and marks the extraction as <code>cancelled</code>. The notification watchdog then skips it — no false "Moon Chunk Ready" alert fires at the originally scheduled arrival time. Webhooks with <strong>Moon Extraction Cancelled</strong> ticked are told, with who cancelled it and when the chunk was due.
                    </div>

                    <div class="alert alert-secondary mt-2">
                        <i class="fas fa-hammer text-info"></i>
                        <strong>Extraction Started:</strong> fires when a refinery starts an extraction, and names the pilot who started it, with the main of their account when that pilot is on SeAT. The extraction is picked up from SeAT's moon extraction data on the plugin's regular schedule, and the alert then waits for the in-game notification to reach SeAT so it can say who started it. If that notification has still not arrived six hours after the extraction started, the alert goes out without the name: a slow notification can delay the alert but never lose it.
                    </div>

                    <div class="alert alert-secondary mt-2">
                        <i class="fas fa-archive text-info"></i>
                        <strong>Past Extractions (Archived) table:</strong> The main Moon Extractions page shows a sortable/filterable DataTables view of all archived extraction cycles. Includes columns for Moon, Structure (station name), Chunk Arrival, Status, Value at Arrival, Actual Mined, Completion %, and Archived time. Scoped to the Moon Owner Corporation only — other corps' private moons on the same SeAT install are excluded. Use the Status filter dropdown to view only cancelled or expired/fractured cycles. Default sort: chunk arrival descending (newest first).
                    </div>

                    <h4><i class="fas fa-plug text-info"></i> {{ trans('mining-manager::help.webhook_setup_title') }}</h4>
                    <p>{{ trans('mining-manager::help.webhook_setup_desc') }}</p>
                    <ol class="step-by-step">
                        <li>{{ trans('mining-manager::help.webhook_setup_step_1') }}</li>
                        <li>{{ trans('mining-manager::help.webhook_setup_step_2') }}</li>
                        <li>{{ trans('mining-manager::help.webhook_setup_step_3') }}</li>
                        <li>{{ trans('mining-manager::help.webhook_setup_step_4') }}</li>
                    </ol>

                    <h4><i class="fas fa-layer-group text-primary"></i> {{ trans('mining-manager::help.webhook_multiple_title') }}</h4>
                    <p>{{ trans('mining-manager::help.webhook_multiple_desc') }}</p>

                    <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                        <div class="feature-item" style="border-left: 4px solid #17a2b8;">
                            <h5><i class="fas fa-hashtag text-info"></i> {{ trans('mining-manager::help.webhook_example_officers') }}</h5>
                            <p>{{ trans('mining-manager::help.webhook_example_officers_desc') }}</p>
                        </div>
                    </div>

                    <h4><i class="fas fa-check-square text-success"></i> {{ trans('mining-manager::help.webhook_toggles_title') }}</h4>
                    <p>{{ trans('mining-manager::help.webhook_toggles_desc') }}</p>

                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <thead>
                                <tr>
                                    <th style="color: #9ca3af;">{{ trans('mining-manager::help.webhook_toggle_category') }}</th>
                                    <th style="color: #9ca3af;">{{ trans('mining-manager::help.webhook_toggle_events') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-moon text-warning"></i> {{ trans('mining-manager::help.webhook_cat_moon') }}</td>
                                    <td>{{ trans('mining-manager::help.webhook_cat_moon_events') }}</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-calendar-alt text-info"></i> {{ trans('mining-manager::help.webhook_cat_events') }}</td>
                                    <td>{{ trans('mining-manager::help.webhook_cat_events_list') }}</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-chart-bar text-primary"></i> {{ trans('mining-manager::help.webhook_cat_reports') }}</td>
                                    <td>{{ trans('mining-manager::help.webhook_cat_reports_events') }}</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-heartbeat text-danger"></i> {{ trans('mining-manager::help.webhook_cat_health') }}</td>
                                    <td>{{ trans('mining-manager::help.webhook_cat_health_events') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4><i class="fas fa-at text-warning"></i> {{ trans('mining-manager::help.webhook_role_ping_title') }}</h4>
                    <p>{{ trans('mining-manager::help.webhook_role_ping_desc') }}</p>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>{{ trans('mining-manager::help.note') }}:</strong> {{ trans('mining-manager::help.webhook_role_ping_note') }}
                    </div>
                </div>

                {{-- Custom Webhooks --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-code"></i>
                        {{ trans('mining-manager::help.custom_webhook_title') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.custom_webhook_desc') }}</p>
                </div>

                {{-- Per-Type Notification Settings --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-sliders-h"></i>
                        {{ trans('mining-manager::help.per_type_settings_title') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.per_type_settings_desc') }}</p>
                    <ul>
                        <li><strong>{{ trans('mining-manager::help.per_type_ping_role') }}</strong></li>
                        <li><strong>{{ trans('mining-manager::help.per_type_ping_user') }}</strong></li>
                    </ul>
                </div>

                {{-- EVE Mail Status --}}
                <div class="warning-box">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <strong>EVE Mail:</strong> {{ trans('mining-manager::help.evemail_status') }}
                    </div>
                </div>
            </div>

            {{-- Mining Events Section --}}
            <div id="events" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-calendar-alt"></i>
                        {{ trans('mining-manager::help.mining_events_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.events_intro') }}</p>

                    <h4><i class="fas fa-globe"></i> Time display &amp; timezones</h4>
                    <p>
                        All times across Mining Manager are stored and displayed in <strong>EVE Time (UTC)</strong>
                        as the single source of truth. Every timestamp also carries a live local-time tooltip and,
                        on high-priority surfaces (calendar, active extractions, upcoming events),
                        an inline " &middot; HH:MM local" pill so you can read both at a glance without thinking.
                        Countdowns ("starts in 2h 15m") tick live every second &mdash; no need to refresh the page.
                    </p>
                    <div class="info-box">
                        <p>
                            <i class="fas fa-globe"></i>
                            <strong>Your browser timezone (right now):</strong>
                            <code id="detected-tz-name">detecting&hellip;</code>
                            &mdash; sample conversion: <code id="detected-tz-sample">&hellip;</code>
                        </p>
                        <p class="text-muted" style="font-size: 0.85em; margin-bottom: 0;">
                            If this doesn't match your physical timezone, check your operating
                            system's Date &amp; Time settings &mdash; the browser inherits its
                            timezone from there (same mechanism Discord and Google Calendar use).
                            DST changes are handled automatically.
                        </p>
                    </div>
                    <p>
                        When <strong>creating or editing</strong> an event, the date/time inputs default to EVE / UTC.
                        Flip the "Enter time in: EVE / UTC &middot; My local time" toggle to enter times in
                        your local timezone instead &mdash; we convert to EVE on submit. The live confirmation box
                        below the inputs shows both interpretations side by side so you always see what the server
                        will record.
                    </p>
                    <script>
                        (function () {
                            try {
                                var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'unknown';
                                var nowSample = new Intl.DateTimeFormat(undefined, {
                                    year: 'numeric', month: '2-digit', day: '2-digit',
                                    hour: '2-digit', minute: '2-digit', timeZoneName: 'short'
                                }).format(new Date());
                                var elTz     = document.getElementById('detected-tz-name');
                                var elSample = document.getElementById('detected-tz-sample');
                                if (elTz)     elTz.textContent = tz;
                                if (elSample) elSample.textContent = nowSample;
                            } catch (e) {
                                // Old browser — leave placeholders.
                            }
                        })();
                    </script>

                    <h4>{{ trans('mining-manager::help.creating_events') }}</h4>
                    <ol class="step-by-step">
                        <li>{{ trans('mining-manager::help.event_create_step_1') }}</li>
                        <li>{{ trans('mining-manager::help.event_create_step_2') }}</li>
                        <li>{{ trans('mining-manager::help.event_create_step_3') }}</li>
                        <li>{{ trans('mining-manager::help.event_create_step_4') }}</li>
                        <li>{{ trans('mining-manager::help.event_create_step_5') }}</li>
                    </ol>

                    <h4>{{ trans('mining-manager::help.participating_events') }}</h4>
                    <p>{{ trans('mining-manager::help.participating_desc') }}</p>
                    <ul>
                        <li>{{ trans('mining-manager::help.participate_step_1') }}</li>
                        <li>{{ trans('mining-manager::help.participate_step_2') }}</li>
                        <li>{{ trans('mining-manager::help.participate_step_3') }}</li>
                    </ul>


                    <h4><i class="fas fa-filter text-warning"></i> Event Type &rarr; What Is Counted</h4>
                    <p>The event type decides <strong>which ore categories</strong> count towards the event, so a "Moon Extraction" event does not quietly count belt mining too.</p>
                    <table class="table table-dark table-bordered table-sm">
                        <thead>
                            <tr>
                                <th width="25%">Event Type</th>
                                <th>Counts</th>
                                <th width="15%">Use For</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Mining Operation</strong></td>
                                <td><code>ore</code> &mdash; regular asteroid ore (belt mining)</td>
                                <td>Belt ops</td>
                            </tr>
                            <tr>
                                <td><strong>Moon Extraction</strong></td>
                                <td><code>moon_r4</code>, <code>moon_r8</code>, <code>moon_r16</code>, <code>moon_r32</code>, <code>moon_r64</code> &mdash; all moon ore rarities</td>
                                <td>Moon chunk fractures</td>
                            </tr>
                            <tr>
                                <td><strong>Ice Mining</strong></td>
                                <td><code>ice</code> only</td>
                                <td>Ice belt ops</td>
                            </tr>
                            <tr>
                                <td><strong>Gas Huffing</strong></td>
                                <td><code>gas</code> only</td>
                                <td>Gas site ops</td>
                            </tr>
                            <tr class="bg-dark">
                                <td><strong class="text-warning">Special Event</strong></td>
                                <td><strong>Every ore category</strong></td>
                                <td>Holidays, incentives, competitions</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info">
                        <strong><i class="fas fa-bullseye"></i> Only mining inside the event window counts:</strong>
                        <p class="mb-2 mt-2">An event counts <em>only the mining that actually overlaps its window</em>, not the participant's entire day.</p>
                        <p class="mb-0"><strong>Example:</strong> A 2-hour gas event from 19:00 to 21:00 UTC. Miner X does 10 hours of gas mining that day &mdash; 1 hour before the event, 2 hours during, 7 hours after. Only the 2 hours during the event count towards it.</p>
                        <p class="mb-0 small text-muted">This is powered by the <code>event_mining_records</code> table, which captures the exact subset of mining that belongs to each event. For moon events the slice is day-level (per ESI); for belt/ice/gas events the slice is time-precise.</p>
                    </div>

                    <h5 class="mt-3">Corporation scoping on events</h5>
                    <p>The event form's <strong>Corporation Scope</strong> field gates <em>which miners</em> the event counts, independent of the ore data pool:</p>
                    <ul>
                        <li><strong>Corp-scoped event</strong> &mdash; only miners whose current corp matches the selected corp get credited. A miner from a different corp mining the same ore is excluded.</li>
                        <li><strong>Global event</strong> &mdash; any miner in your plugin's data gets credited, regardless of their corp.</li>
                    </ul>
                    <p class="text-muted small">Cross-corp moon mining is allowed: a Corp B miner mining at Corp A's moon counts for a Corp B event (because the miner is in Corp B) and for any global event. Corp A's ownership of the moon doesn't block that &mdash; the filter is on who mined, not on whose moon.</p>

                    <h4 class="mt-4"><i class="fas fa-clock text-warning"></i> Time Granularity</h4>
                    <p>How precisely the plugin can attribute mining activity to an event window depends on the source table each event type reads from:</p>
                    <table class="table table-dark table-sm mb-2">
                        <thead>
                            <tr><th>Event Type</th><th>Source</th><th>Precision</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Moon Extraction</strong><br><em>(and moon half of Special)</em></td>
                                <td><code>corporation_industry_mining_observer_data</code><br>→ stored in <code>mining_ledger</code></td>
                                <td><span class="badge badge-warning">Day only</span> &mdash; EVE aggregates moon drill observer data per calendar day. Fundamental ESI limitation; cannot be improved on our side.</td>
                            </tr>
                            <tr>
                                <td><strong>Mining Operation</strong><br><strong>Ice Mining</strong><br><strong>Gas Huffing</strong><br><em>(and non-moon half of Special)</em></td>
                                <td>SeAT's <code>character_minings</code> table</td>
                                <td><span class="badge badge-success">Sub-day (fetch time)</span> &mdash; uses SeAT's <code>time</code> column as a proxy for when mining happened. This reflects when SeAT <em>fetched</em> the data, not the literal EVE moment, but it's usable for events spanning several hours within a day.</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mb-0"><strong>Practical guidance:</strong></p>
                    <ul class="mb-2">
                        <li><strong>Moon events shorter than 24h</strong> capture all of the overlapping day's moon mining. For precise tracking, align moon events to UTC day boundaries or multiples of 24h.</li>
                        <li><strong>Non-moon events</strong> benefit from SeAT's fetch-time granularity &mdash; a 4-hour gas event won't pull in gas mining from the same day that happened before or after the window (provided SeAT fetched within a reasonable cadence).</li>
                        <li>For competitive/incentive events where "did you participate?" matters more than exact timing, use the manual <strong>Join Event</strong> button as an opt-in filter &mdash; only characters who clicked Join get counted regardless of mining data.</li>
                    </ul>
                </div>
            </div>

            {{-- Moon Mining Section --}}
            <div id="moon-mining" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-moon"></i>
                        {{ trans('mining-manager::help.moon_mining_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.moon_intro') }}</p>

                    <h4><i class="fas fa-box-open"></i> Metenox cargo readout
                        <span class="badge badge-info ml-1" style="font-size: 0.6em;">Director</span>
                    </h4>
                    <p>
                        The <strong>Metenox Cargo</strong> page (sidebar &rarr; Moon Extractions &rarr;
                        Metenox Cargo, or the tab on any moon page) shows what's currently sitting
                        in every Metenox Moon Drill's cargo bay owned by the
                        <strong>Moon Owner Corporation</strong> (Settings &rarr; General &rarr;
                        <code>moon_owner_corporation_id</code>). One card per drill, ore composition
                        table with per-ore ISK valuation and percent-of-cargo bars, structure state
                        pill, and a last-polled timestamp so you know how fresh the data is.
                    </p>
                    <div class="info-box">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            <strong>Data source &amp; refresh cadence.</strong> The page reads from
                            SeAT's existing <code>corporation_assets</code> table, which is
                            populated by SeAT's standard corp-assets ESI poller. ESI caches the
                            corp-assets endpoint for ~1 hour, so don't expect sub-minute freshness.
                            The "Last polled" timestamp on each card tells you the actual sample
                            time; values older than 2 hours are highlighted in yellow.
                        </p>
                        <p class="text-muted" style="font-size: 0.85em; margin-bottom: 0;">
                            No new ESI scopes required &mdash; the standard
                            <code>esi-assets.read_corporation_assets.v1</code> scope (granted to
                            SeAT by any Director-roled character on the corp) is sufficient. If a
                            corp's drills appear empty when you know they shouldn't be, the most
                            common cause is that no Director ESI token has been registered for
                            that corp.
                        </p>
                    </div>
                    <h5>Permission model</h5>
                    <ul>
                        <li><strong>Director-only.</strong> The page is gated by the
                            <code>mining-manager.director</code> permission. Members and accountants
                            cannot see it (no sidebar entry, no route access).</li>
                        <li><strong>Scoped to Moon Owner Corporation (directors).</strong> Directors
                            see only Metenoxes owned by the corp set as Moon Owner in Settings &rarr;
                            General. Same convention as the Past Extractions table. Member corps'
                            drills (if any) are intentionally hidden so the page and the
                            <code>metenox_cargo_full</code> notification cron stay aligned (both
                            scan the same set of drills, so there's no scenario where an alert fires
                            for a drill that doesn't appear on the page). If the Moon Owner Corp
                            isn't configured, the page shows a warning with a one-click link to
                            Settings.</li>
                        <li><strong>Admin bypass + corp picker.</strong> Operators with the
                            <code>mining-manager.admin</code> permission land on the same Moon Owner
                            Corp view that directors see, so the default landing scope is identical
                            regardless of role. The header dropdown lets admins switch to any other
                            corporation with a Metenox, or to the install-wide <em>All corps</em>
                            aggregate view (handy on multi-corp installs where you need to spot-
                            check drills outside the Moon Owner Corp). The
                            <code>metenox_cargo_full</code> notification scope is unchanged: alerts
                            still fire for the Moon Owner Corp only, so the picker is a read-side
                            convenience and never generates extra alert traffic.</li>
                        <li><strong>No write surface.</strong> The page is purely informational
                            &mdash; no cleanup buttons, no recalculation actions, no exports. Use
                            the in-game asset browser if you need to actually move ore.</li>
                    </ul>
                    <h5>ISK valuation</h5>
                    <p>
                        ISK values are computed at page render using the plugin's configured price
                        provider and its fallback. Prices are fetched in a single batch for every ore type
                        across every visible drill, so adding more Metenoxes doesn't multiply the
                        pricing cost. If pricing is unavailable for any reason, the page renders
                        the quantity columns only and shows "pricing unavailable" in the header.
                    </p>
                    <h4>{{ trans('mining-manager::help.moon_tracking') }}</h4>
                    <p>{{ trans('mining-manager::help.moon_tracking_desc') }}</p>

                    <h4><i class="fas fa-calendar-alt"></i> The extraction calendar</h4>
                    <p>
                        <strong>Extraction Calendar</strong> shows three months at a time, the one you are on and
                        the two after it, each as its own grid. Prev and next move that window a month at a time and
                        <strong>Today</strong> brings it back. <strong>Week</strong> and <strong>List</strong> swap
                        in when you want a closer look at one week.
                    </p>
                    <p>
                        Every chunk carries the tier of its moon, R4 to R64, worked out from that extraction's own
                        ore rather than whatever the refinery pulled last, so an unusual chunk is labelled for what
                        it actually is. Hovering gives the full refinery name and its moon, which the cells are too
                        narrow to show. The colour is the chunk's state, as the legend above the grid says, and the
                        grid runs on EVE time (UTC) like everything else here.
                    </p>

                    <h4>{{ trans('mining-manager::help.moon_compositions') }}</h4>
                    <p>{{ trans('mining-manager::help.moon_compositions_desc') }}</p>

                    <h4><i class="fas fa-coins"></i> What a chunk is worth</h4>
                    <p>
                        A chunk's value is its ore, as the game last reported it, at the prices in your price cache.
                        It is priced while the chunk is on its way, again when it arrives, and one last time when it
                        fractures, by the laser or on its own. From then on it keeps that value: the extraction page,
                        the lists, the calendar, Moon Analytics and the archive all show what the chunk was worth
                        while it could be mined, not what its ore would fetch today. When no fracture notice turns up,
                        EVE's auto-fracture time stands in. A jackpot found afterwards still doubles the value shown.
                    </p>

                    <h4>{{ trans('mining-manager::help.extraction_notifications') }}</h4>
                    <p>{{ trans('mining-manager::help.extraction_notifications_desc') }}</p>

                    <div class="info-box">
                        <i class="fas fa-calculator"></i>
                        <strong>{{ trans('mining-manager::help.moon_value') }}:</strong> {{ trans('mining-manager::help.moon_value_desc') }}
                    </div>
                </div>

                {{-- Moon Extraction Lifecycle --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-sync-alt"></i>
                        {{ trans('mining-manager::help.moon_lifecycle') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.moon_lifecycle_intro') }}</p>

                    <div class="feature-grid">
                        <div class="feature-item" style="border-left: 4px solid #f39c12;">
                            <h5><span class="badge badge-warning">{{ trans('mining-manager::help.moon_status_extracting') }}</span></h5>
                            <p>{{ trans('mining-manager::help.moon_status_extracting_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #28a745;">
                            <h5><span class="badge badge-success">{{ trans('mining-manager::help.moon_status_ready') }}</span></h5>
                            <p>{{ trans('mining-manager::help.moon_status_ready_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #dc3545;">
                            <h5><span class="badge badge-danger">{{ trans('mining-manager::help.moon_status_unstable') }}</span></h5>
                            <p>{{ trans('mining-manager::help.moon_status_unstable_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #6c757d;">
                            <h5><span class="badge badge-secondary">{{ trans('mining-manager::help.moon_status_expired') }}</span></h5>
                            <p>{{ trans('mining-manager::help.moon_status_expired_desc') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Moon drilling rigs --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-cog"></i>
                        Moon Drilling Rigs
                    </h3>
                    <p>
                        A refinery's moon drilling rigs change how long its chunks can be mined and how much ore they
                        hold. The chunk cycle above stays the same, fracture, the mining window, then 2 hours
                        unstable; the rigs only stretch the window and add to the yield.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark">
                            <thead>
                                <tr><th>Refinery</th><th>Rig</th><th>What it does</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Athanor</td><td>Standup M-Set Moon Drilling Efficiency I / II</td><td>+2% / +2.4% ore in each chunk</td></tr>
                                <tr><td>Athanor</td><td>Standup M-Set Moon Drilling Stability I / II</td><td>a 72 / 96 hour mining window instead of 48, and 3h 36m / 3h 43m before the chunk fractures on its own instead of 3h</td></tr>
                                <tr><td>Tatara</td><td>Standup L-Set Moon Drilling Proficiency I / II</td><td>both of the above from one rig</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        <strong>Which rig a chunk had comes from the chunk itself.</strong> EVE times every chunk to
                        fracture on its own after it arrives, and that time already includes the rig: 3 hours with
                        none, 3h 36m with Tech I, 3h 43m with Tech II. Mining Manager reads the rig from that time,
                        chunk by chunk, so swapping a rig never changes a chunk already pulled, and it works for any
                        refinery, another corporation's included.
                    </p>
                    <p>
                        <strong>What is fitted now</strong> comes from the refinery's rig slots in SeAT's copy of the
                        corporation assets, which needs a Director token with the corporation assets scope. Each
                        extraction keeps a record of the rigs seen while its chunk was on its way. On an Athanor that
                        record is the only way to know the yield rig, so where SeAT cannot see the fittings the
                        extraction page says the yield is unknown rather than guessing.
                    </p>
                    <p>
                        <strong>The game's own figures win.</strong> EVE reports a chunk's ore volumes when the
                        extraction starts, when the chunk arrives, and when it is fractured, by the laser or on its
                        own. Mining Manager uses the newest of these, so the extraction's value and Moon Analytics
                        follow what is really in the chunk, and the extraction page lists each report and whether the
                        figure changed. The extra a yield rig added is worked back out of those figures.
                    </p>
                    <p>
                        <strong>In the simulator</strong>, pick an Athanor or a Tatara and each rig at none, Tech I or
                        Tech II. It starts from what is fitted on our refinery at that moon; choose something else and
                        a banner says what is really fitted. Both Tech II rigs fit an Athanor together, using 300 of
                        its 400 calibration and leaving room for one more Tech I rig. Find Moons and the quality
                        ratings still value the moon on its own.
                    </p>
                    <p>
                        <strong>In Moon Analytics</strong>, a cog after a refinery's name shows the moon rigs fitted on
                        it now, and a cog beside the extraction count shows how many of the month's chunks were pulled
                        with rigs. Hover either for the detail. Each chunk is marked from its own timer and the record
                        kept with it, so fitting or pulling a rig later never changes how an older chunk is shown.
                    </p>
                    <p>
                        <code>mining-manager:diagnose-extractions</code> lists each refinery's drill and moon rigs, and
                        Diagnostics compares the rigs SeAT can see with what each refinery's latest chunk was timed with.
                    </p>
                </div>

                {{-- Moon Classification --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-gem"></i>
                        {{ trans('mining-manager::help.moon_classification') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.moon_classification_desc') }}</p>
                    <ul>
                        <li><span class="badge badge-r64">R64</span> {{ trans('mining-manager::help.moon_r64') }}</li>
                        <li><span class="badge badge-r32">R32</span> {{ trans('mining-manager::help.moon_r32') }}</li>
                        <li><span class="badge badge-r16">R16</span> {{ trans('mining-manager::help.moon_r16') }}</li>
                        <li><span class="badge badge-r8">R8</span> {{ trans('mining-manager::help.moon_r8') }}</li>
                        <li><span class="badge badge-r4">R4</span> {{ trans('mining-manager::help.moon_r4') }}</li>
                    </ul>

                    <h4 class="mt-4">{{ trans('mining-manager::help.moon_quality') }}</h4>
                    <p>{{ trans('mining-manager::help.moon_quality_desc') }}</p>
                    <ul>
                        <li><span class="badge" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">Exceptional</span> {{ trans('mining-manager::help.moon_quality_exceptional') }}</li>
                        <li><span class="badge badge-success">Excellent</span> {{ trans('mining-manager::help.moon_quality_excellent') }}</li>
                        <li><span class="badge badge-info">Good</span> {{ trans('mining-manager::help.moon_quality_good') }}</li>
                        <li><span class="badge badge-warning">Average</span> {{ trans('mining-manager::help.moon_quality_average') }}</li>
                        <li><span class="badge badge-secondary">Poor</span> {{ trans('mining-manager::help.moon_quality_poor') }}</li>
                    </ul>
                </div>

                {{-- Extraction Simulator and Find Moons --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-flask"></i>
                        Extraction Simulator and Find Moons
                    </h3>
                    <p>
                        The <strong>Extraction Simulator</strong> tab, in the Moon Planner section of the sidebar,
                        estimates what a scanned moon yields over an extraction of 6 to 56 days: the volume of each
                        ore, its value as ore and reprocessed at your refining efficiency, the moon's class and its
                        quality. Type part of a moon's name to pick it. It is for the people who decide which moons
                        to drill, Directors, Admins, Moon Managers and Moon Finders, and members do not see it.
                    </p>
                    <p>
                        <strong>Refined value leads.</strong> Raw moon ore barely trades, so one thin sell order
                        can put a silly price on a moon, while what the ore reprocesses into holds steady. Both
                        figures are always shown and both are named, and <em>Use Refined Mineral Value</em> under
                        Settings, Pricing decides which one leads.
                    </p>
                    <p>
                        Prices come from the price cache that the scheduled price refresh keeps, never from a live
                        lookup, so simulating costs your price provider nothing.
                    </p>
                    <p>
                        A missing price is named only where it changes the figure on screen. A raw ore with no market price counts
                        as 0 in the ore value, which is normal: raw ore mostly trades compressed or refined, and
                        the refined value is unaffected. A refined material with no price leaves the refined value
                        short by that much, and is named when refined value is on screen.
                    </p>
                    <p>
                        <strong>Metenox output, for Directors and admins.</strong> Under each simulation a card
                        shows what a Metenox Moon Drill on the same moon would collect over the same days. A
                        Metenox keeps everything it drills: the same extraction rate, reprocessed on the spot at
                        40%, and only the moon materials, since the minerals in moon ore never reach its bay and no
                        rig fits one. The figure is before fuel, which each corporation buys and prices its own
                        way. Moon Managers and Moon Finders do not see the card.
                    </p>

                    <h4><i class="fas fa-search-location"></i> Find Moons</h4>
                    <p>
                        Searches every scanned moon at once. Filter by region, constellation and system, security
                        band, class, moon ore share, ores a moon must contain, composition rules such as
                        <em>R16 at least 20%</em>, value over a chosen number of days (<code>1.5b</code> and
                        <code>800m</code> work) and quality. Region, constellation and system can be picked in any
                        order, and picking a system fills in the other two. Results are sorted by value, and
                        clicking a column heading sorts by that column instead. <strong>Simulate</strong>
                        on a row opens that moon in the simulator, and <strong>Export CSV</strong> downloads every
                        match while Allow Data Export is on.
                    </p>
                    <p>
                        <strong>Moons somebody else holds.</strong> ESI only reports your own structures, so the
                        plugin cannot know which moons are already taken. Mark one with the <i class="fas fa-flag"></i>
                        button on its row or in the simulator, naming the corporation or alliance if you know it,
                        and it carries a <strong>Claimed</strong> badge from then on, with who reported it and when
                        in the tooltip. <strong>Claimed moons</strong> in the filters shows only those or hides
                        them. When a moon turns out to be free, <strong>Moon is free</strong> clears it, and the
                        old report is kept rather than deleted. Marking and clearing need Find Moons; every member
                        sees the badge.
                    </p>
                    <p>
                        A claim also clears itself. If you buy the structure or anchor your own, the moon reads as
                        <strong>Ours</strong> straight away, and the report is closed on the next extraction
                        update with the reason recorded, so nobody has to remember to tidy it up.
                    </p>
                    <p>
                        <strong>The watchlist.</strong> A moon worth having that you cannot have today, because
                        somebody else is on it or you have no refinery spare, can be starred with a note saying
                        why. The list is shared, so anyone searching sees it, <strong>Watchlist</strong> in the
                        filters shows only those moons or hides them, and a moon drops off the list by itself once
                        one of your refineries drills it. <strong>Moon name</strong> finds a single moon: type any
                        part of its name.
                    </p>
                    <p>
                        A moon one of your refineries still sits on is marked <strong>Ours</strong>, with the
                        refinery and the corporation holding it named in the tooltip, and <strong>Our
                        refineries</strong> can show only those moons or hide them while you look for new ground.
                        The link comes from extractions and planned pulls, which name both the moon and the
                        refinery, so a refinery that has been unanchored leaves its moon free again. Metenox drills
                        are not covered: ESI never says which moon a drill sits on.
                    </p>
                    <p>
                        When a moon is simulated, Find Moons users also see up to three better scanned moons of the
                        same class nearby: in the constellation or region they searched, or else in the moon's own
                        constellation, then its region.
                    </p>

                    <div class="info-box">
                        <i class="fas fa-key"></i>
                        <strong>Who can use the simulator and Find Moons:</strong> directors, admins, moon managers
                        and anyone given the <code>mining-manager.moon_finder</code> permission. Find Moons can list
                        every valuable moon in a region at once, which is why neither is open to every member. A
                        Moon Finder has no planner, so Moon Planner in the sidebar opens straight onto the simulator
                        for them.
                    </div>
                </div>

                {{-- Jackpot Detection --}}
                <div class="help-card">
                    <h3>
                        <i class="fas fa-star" style="color: #ffd700;"></i>
                        {{ trans('mining-manager::help.jackpot_title') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.jackpot_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.jackpot_auto_title') }}</h4>
                    <p>{{ trans('mining-manager::help.jackpot_auto_desc') }}</p>

                    <h4>{{ trans('mining-manager::help.jackpot_manual_title') }}</h4>
                    <p>{{ trans('mining-manager::help.jackpot_manual_desc') }}</p>

                    <h4>{{ trans('mining-manager::help.jackpot_verification_title') }}</h4>
                    <p>{{ trans('mining-manager::help.jackpot_verification_desc') }}</p>

                    <div class="feature-grid">
                        <div class="feature-item" style="border-left: 4px solid #28a745;">
                            <h5><span class="badge badge-success"><i class="fas fa-check-circle"></i> {{ trans('mining-manager::help.jackpot_status_verified') }}</span></h5>
                            <p>{{ trans('mining-manager::help.jackpot_status_verified_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #6c757d;">
                            <h5><span class="badge badge-secondary"><i class="fas fa-hourglass-half"></i> {{ trans('mining-manager::help.jackpot_status_awaiting') }}</span></h5>
                            <p>{{ trans('mining-manager::help.jackpot_status_awaiting_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #dc3545;">
                            <h5><span class="badge badge-danger"><i class="fas fa-times-circle"></i> {{ trans('mining-manager::help.jackpot_status_unverified') }}</span></h5>
                            <p>{{ trans('mining-manager::help.jackpot_status_unverified_desc') }}</p>
                        </div>
                    </div>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>{{ trans('mining-manager::help.jackpot_note_title') }}:</strong> {{ trans('mining-manager::help.jackpot_note_desc') }}
                    </div>
                </div>
            </div>

            {{-- Moon Planner Section --}}
            <div id="moon-planner" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-calendar-check"></i>
                        Moon Planner
                    </h3>
                    <p>
                        A calendar for deciding <em>when</em> each refinery should pull, so arrivals are spread out
                        instead of landing on top of each other. Chunks that aren't mined promptly are wasted, so for
                        a smaller crew the spacing matters as much as the schedule itself.
                    </p>
                    <p>
                        The Moon Planner section also holds the <strong>Extraction Simulator</strong>, for deciding
                        <em>which</em> moons to drill and with what rigs. See
                        <a href="#moon-mining" data-section-link="moon-mining">Extraction Simulator and Find Moons</a>.
                    </p>

                    <p>
                        Each pull on the calendar carries the tier of its moon, R4 to R64, the same badge the
                        Blueprints grid and the refinery cards use, and hovering one gives the full refinery name
                        and its moon. Today is picked out in amber.
                    </p>

                    <p>
                        <strong>Only refineries with a moon drill count.</strong> An Athanor or Tatara with no Moon
                        Drilling service fitted is a reprocessing or reaction station, so it is left out of the
                        refinery list, Auto-fill, the blueprint picker, the reminders and the counts. A drill that is
                        offline, out of fuel for example, is still fitted, and its refinery stays.
                        <code>mining-manager:diagnose-extractions</code> shows which of your refineries have one.
                    </p>

                    <p>
                        <strong>Nothing is taken away while the structure is still there.</strong> Planned pulls and
                        blueprint slots only go once a refinery has left your corporation: unanchored, destroyed or
                        handed over. Until then the planner and the Blueprints grid put a <strong>!</strong> on it.
                        Hover it for the reason:
                    </p>
                    <ul>
                        <li>
                            <strong>Yellow, Unanchoring in progress.</strong> The unanchor timer is running. Cancel it
                            and the mark goes after the next structure sync.
                        </li>
                        <li>
                            <strong>Yellow, No moon drill fitted.</strong> The drill has been unfitted. Its pulls and
                            slots stay in case it goes back on, but nothing new is planned on it.
                        </li>
                        <li>
                            <strong>Red, Structure gone, not cleared yet.</strong> It has left your corporation. Its
                            pulls come off once that is certain, as described under Blueprints below.
                        </li>
                    </ul>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>The planner does not control your structures.</strong>
                        SeAT can only read the extractions a director fires in-game. The planner is a coordination
                        tool: it records what's already scheduled, and lets you plan what <em>should</em> happen next.
                        Firing the drill is still done in EVE.
                    </div>

                    <h4><i class="fas fa-key"></i> Who can use it</h4>
                    <p>
                        The <strong>Moon Planner</strong> page appears in the sidebar for anyone with the
                        <code>mining-manager.moon_manager</code> permission. Directors and admins have access as well.
                        Grant <code>moon_manager</code> on its own when you want someone scheduling moon pulls without
                        giving them full director rights.
                    </p>
                    <p>
                        Moon managers can also open <strong>Moon Analytics</strong> (Analytics in the sidebar), which
                        shows how each moon and ore has been mined, by month or by extraction. The rest of Analytics
                        stays with directors. The <strong>Extraction Simulator</strong>, with Find Moons, sits in the
                        same section as the planner.
                    </p>

                    <h4><i class="fas fa-clock"></i> Everything is EVE time</h4>
                    <p>
                        The calendar runs on <strong>EVE time (UTC)</strong> &mdash; the same clock the in-game
                        structure scheduler uses &mdash; so what you plan matches what you type into the drill. When
                        you add or edit a pull you enter EVE time and the form shows what that is in your own
                        timezone underneath, as a sanity check.
                    </p>

                    <h4><i class="fas fa-magic"></i> Auto-fill from history</h4>
                    <p>
                        Rather than placing everything by hand, <strong>Auto-fill from History</strong> works out each
                        refinery's natural rhythm from its past arrivals and lays the upcoming pulls onto the
                        calendar. A refinery needs at least two recorded arrivals for this; with three or more the
                        median interval is used so one unusual cycle doesn't skew it. Refineries without enough
                        history of their own borrow the typical cadence of your other moons and are marked as
                        estimates. It never places a pull closer than the refinery spacing (7 days by default) to
                        another pull on the same refinery, real or planned.
                    </p>
                    <p>
                        If a refinery is missing history, run
                        <code>mining-manager:backfill-extraction-history</code> &mdash; it reconstructs past cycles
                        from your in-game notifications.
                    </p>

                    <h4><i class="fas fa-drafting-compass"></i> Blueprints</h4>
                    <p>
                        Auto-fill guesses from history. A <strong>blueprint</strong> is the other way round: you say
                        what the rotation should be, and the planner lays it down. It is a pattern of pulls over one
                        to eight weeks &mdash; which refinery, which weekday, what EVE time &mdash; on the
                        <strong>Blueprints</strong> tab next to the planner.
                    </p>
                    <p>
                        Build it on the grid: a row per week, a column per weekday, and a <code>+</code> in any cell
                        to drop a refinery and a time into it. Save it, then apply it, either from this tab or with
                        <strong>Plan from Blueprint</strong> on the planner itself: pick the blueprint, the date to
                        start from and how many cycles to write, and you get the full list before anything is saved. Each line says whether it will be planned, skipped and why (before your
                        start date, already in the past, or that refinery is already planned within half an hour),
                        or planned but landing inside the minimum gap of another moon, or closer than the refinery
                        spacing to another pull on the same refinery.
                    </p>
                    <p>
                        <strong>Make this blueprint the plan for these weeks</strong> is the tick box on that
                        dialog. Left alone, a day that already has a pull on it is skipped and kept. Ticked, the
                        blueprint takes the weeks over: everything else planned in them is removed first,
                        including the days the pattern does not use at all. Swapping a Monday to Friday rotation
                        for a Monday, Wednesday, Friday one takes the Tuesday and Thursday pulls with it, instead
                        of leaving both patterns on the calendar at once. You get the full list of what would go,
                        and where each one came from, before you confirm. A pull that is already running, one
                        already matched to an extraction, and anything outside the weeks you are applying are
                        never touched.
                    </p>
                    <p>
                        The pattern keeps its weekdays. Starting a blueprint on a Friday runs whatever is left of
                        that week first, then carries on from the Monday, so a moon you put on Monday stays on
                        Monday.
                    </p>
                    <p>
                        <strong>A refinery belongs to a blueprint once.</strong> The picker only offers the ones
                        the pattern does not already use, and the count above the grid says how many are placed
                        and how many are still to go, so nothing gets scheduled twice or quietly left out. Each
                        one is labelled with its moon's tier, R4 to R64, so the rich moons are easy to spot.
                        Click any pull in the grid to change its refinery or time, or to remove it.
                    </p>
                    <p>
                        <strong>A refinery that is unanchored, destroyed or handed to someone else</strong> is flagged
                        in red wherever it appears, and applying never plans a pull on it. The pulls planned on it
                        come off the calendar by themselves, whether a blueprint wrote them or somebody planned them
                        by hand, but only once it has been missing on three sightings at least twelve hours apart. A
                        structure can vanish from SeAT for an afternoon when ESI or the server has a bad day, and
                        being seen again in between starts the count over. Each refinery that goes sends its own
                        <strong>Refinery Gone</strong> notification, saying which blueprints still hold it, how many
                        pulls went, and why, if the game reported it.
                    </p>
                    <p>
                        A refinery that is still there keeps its slots and its pulls. With its drill unfitted it is
                        marked in yellow and applying skips it until the drill is back. Being unanchored, it is
                        marked in yellow and planned as usual, since the unanchor can still be cancelled.
                    </p>
                    <p>
                        The blueprint slots themselves stay until you press the button that clears them out of every
                        blueprint. That part is deliberately yours: a pattern is cheap to keep and annoying to
                        rebuild.
                    </p>

                    <div class="info-box">
                        <i class="fas fa-shield-alt"></i>
                        <strong>A blueprint never touches what has already happened.</strong>
                        Editing one offers to carry the change to the pulls it already wrote: slots that moved move,
                        slots you removed are taken off the calendar, slots you added appear in every cycle still
                        ahead. Pulls in the past, and pulls already matched to a real extraction, stay exactly as
                        they are. The same goes for moving or deleting a single pull: the planner asks whether to
                        carry it to that moon's later pulls in the rotation, and carrying a move shifts them by the
                        same amount so the spacing survives.
                    </div>

                    <h4><i class="fas fa-arrows-alt-h"></i> Moving pulls, and the gap warning</h4>
                    <p>
                        Move a pull to a different day and it stays there &mdash; later projections follow the new
                        day rather than snapping back to the old one. That's how you shift a moon that has always
                        landed on a Monday onto a Tuesday.
                    </p>
                    <p>
                        If a pull lands within the <strong>minimum gap</strong> of another moon's arrival (24 hours by
                        default, configurable at Settings &rarr; Notifications) you'll get a confirmation listing the
                        moons it clashes with and how far apart they are. You can still go ahead &mdash; it's a
                        warning, not a block &mdash; but nothing gets saved past the gap without you agreeing to it.
                    </p>
                    <p>
                        Pulls on the <strong>same refinery</strong> get their own check. An extraction takes at least
                        6 days, and the next one can only be started once the chunk is in, so two pulls on one
                        refinery closer together than the <strong>refinery spacing</strong> (7 days by default,
                        arrival to arrival, at Settings &rarr; Notifications) are unlikely to both happen as planned.
                        The same confirmation lists them, the blueprint preview marks them, and the planner shows a
                        <strong>Pulls too close together</strong> banner, with a yellow <strong>!</strong> on each
                        planned pull involved, however it got onto the calendar. Exactly the spacing apart is fine:
                        at 7 days, a pull on the 1st at 17:00 leaves the 8th at 17:00 clear. Like the gap, it is a
                        warning, not a block.
                    </p>

                    <h4><i class="fas fa-lock"></i> Locked entries</h4>
                    <p>
                        Extractions that are live, finished, or archived are shown with a padlock and can't be edited.
                        They were set in EVE, so the planner only records them. Clicking one explains why it's locked.
                    </p>

                    <h4><i class="fas fa-exclamation-triangle"></i> Moons scheduled off-plan</h4>
                    <p>
                        When a plan and the real extraction for the same refinery are within 30 minutes of each other
                        they're treated as the same pull, so it appears once. If they're further apart than that but
                        still in the same cycle, the drill was fired on a different timer than planned: the real pull
                        is highlighted, it's listed in a <em>Scheduling mismatches</em> banner at the top of the page,
                        and a <strong>Moon Scheduled Off-Plan</strong> notification fires once.
                    </p>
                    <p>
                        Each mismatch on the banner has two buttons. <strong>Realign</strong> moves the plan to the time
                        the drill was actually set for in-game. <strong>Ignore</strong> keeps the plan's time and records
                        that the pull went ahead off-plan. Both clear the warning, both ask for a reason, and both are
                        saved to the planner history with your name. Neither moves later planned pulls for that
                        refinery, and the in-game extraction isn't touched.
                    </p>

                    <h4><i class="fas fa-bell"></i> Planner reminders</h4>
                    <p>Three notifications cover what nobody has done yet. Each is off until you tick it on a webhook.</p>
                    <ul>
                        <li>
                            <strong>Moon Extraction Cancelled</strong> says when somebody stops an extraction in game
                            before its chunk arrives, with who did it and when the chunk was due.
                        </li>
                        <li>
                            <strong>Moon Not Rescheduled</strong> goes out when a chunk arrived a while ago and no new
                            extraction has been started on that refinery since: 48 hours by default, then again every 48
                            hours until one starts. It waits while the drill is offline, since there is nothing you
                            could start, and while the next planned pull is more than 56 days ahead (see below).
                        </li>
                        <li>
                            <strong>Moons Need Planning</strong> is one message listing every refinery with fewer pulls
                            planned ahead than you ask for, counted like the <code>Not planned</code> badge below, with
                            the total at the end. A long list stops at 25 and counts the rest, so it always arrives.
                            A refinery whose drill is offline stays on the list, marked <code>drill offline</code>:
                            the drill is still fitted, and pulls can be planned for when the fuel is back.
                        </li>
                    </ul>
                    <p>
                        <strong>Which refineries they leave out.</strong> Moon Not Rescheduled and Moons Need Planning
                        only speak about refineries that can pull a chunk. Every refinery is checked again before each
                        message, and one is left out when:
                    </p>
                    <ul>
                        <li>
                            <strong>It has no moon drill.</strong> An Athanor or Tatara with no Moon Drilling service is
                            a reprocessing or reaction station. Unfitting the drill to give a refinery another job takes
                            it out of both reminders at the next structure sync. Pulls already planned on it stay,
                            marked in yellow.
                        </li>
                        <li>
                            <strong>It is no longer yours.</strong> Unanchored, destroyed or handed to another
                            corporation, it drops out of your corporation's structures when SeAT next syncs them.
                        </li>
                        <li>
                            <strong>The game has reported it destroyed.</strong> The Structure Destroyed notification
                            counts straight away, even before SeAT's structure list has caught up.
                        </li>
                        <li>
                            <strong>It is unanchoring with no extraction running.</strong> Unanchoring takes seven
                            days, too short for a new pull to arrive and be mined before the structure goes, so there
                            is nothing to plan. A refinery that is still extracting while it unanchors is treated as
                            usual until that extraction ends. Cancel the unanchor and the reminders pick it up again
                            after the next structure sync.
                        </li>
                    </ul>
                    <p>
                        Leaving a refinery out never resets its reminders. If it drops out of SeAT for a sync, or its
                        drill comes back online, or an unanchor is cancelled, Moon Not Rescheduled carries on with the
                        next reminder instead of starting again at the first, so a bad day for ESI cannot turn into a
                        repeat. The count is only forgotten when an extraction starts, or once the refinery is
                        certainly gone: confirmed by the Refinery Gone check, or reported destroyed.
                    </p>
                    <p>
                        The hours, the repeat, how many pulls to plan ahead and how often the list goes are all under
                        <strong>Settings, Notifications, Moon Planner Reminders</strong>.
                    </p>

                    <h4><i class="fas fa-hourglass-start"></i> Pulls planned more than 56 days ahead</h4>
                    <p>
                        The planner takes a pull any distance ahead. An extraction runs at most 56 days, though, so a
                        pull planned further ahead than that cannot be set up in game yet. When the chunk before it
                        arrives, <strong>Next Extraction Planned</strong> says so and gives the time the pull can be
                        started from, 56 days before it is due, then posts again at that time as
                        <strong>Next Pull Can Be Started</strong>. Starting it then gives the longest extraction that
                        still arrives on plan, and so the biggest chunk.
                    </p>
                    <p>
                        Moon Not Rescheduled waits for the same moment and counts its hours from there rather than from
                        the arrival, so nobody is reminded about a pull that cannot be started yet. The second message
                        also waits while the drill is offline or the refinery is unanchoring, and is dropped if the pull
                        could no longer arrive on time. A pull that was within reach all along gets no second message.
                    </p>

                    <h4><i class="fas fa-paper-plane"></i> Sending the next-pull notices again</h4>
                    <p>
                        <strong>Resend Next-Pull Notices</strong> on the planner sends Next Extraction Planned again for
                        every refinery whose chunk has arrived and whose next extraction has not been started in game,
                        one message each, to the webhooks that take it. Useful after ticking the notice on a new
                        webhook, or to remind the channel what is waiting. It lists the refineries first, with each one's
                        next pull, and nothing goes until you press Send.
                    </p>
                    <ul>
                        <li>
                            A pull planned more than 56 days ahead says from when it can be started, the same as the
                            message sent when the chunk arrived. Where nothing is planned, the message gives the
                            planner's projection from the refinery's history and says it is one.
                        </li>
                        <li>
                            Refineries it has nothing to send for are listed with the reason and left out: moon drill
                            offline, reported destroyed, being unanchored with nothing running, past its final pull, or
                            nothing planned and no history to project from.
                        </li>
                        <li>
                            It changes nothing else. A far pull still gets its Next Pull Can Be Started message when the
                            time comes, and Moon Not Rescheduled keeps its own count.
                        </li>
                    </ul>

                    <h4><i class="fas fa-flag-checkered"></i> A refinery's final pull</h4>
                    <p>
                        When a refinery is going to be relocated or unanchored, click its last pull on the planner, or
                        the extraction already set in game, and choose <strong>Mark as final pull</strong>. Pulls
                        planned after it are listed first and only removed once you confirm. The final pull shows in
                        orange with a flag, and the refinery's card says when it is.
                    </p>
                    <ul>
                        <li>
                            Nothing can be planned after it, by hand, by auto-fill or by a blueprint. Pulls before it
                            can still be added and moved, one at a time.
                        </li>
                        <li>Moons Need Planning and Moon Not Rescheduled leave the refinery out.</li>
                        <li>
                            When its last chunk arrives, <strong>Next Extraction Planned</strong> says that was the last
                            pull and nothing is planned after it, instead of announcing a next one.
                        </li>
                        <li>
                            If somebody starts an extraction there anyway, the same webhooks are told, and the card is
                            marked until the refinery is resumed.
                        </li>
                        <li>Arrival, fracture, jackpot, unstable and the other chunk alerts go out as usual.</li>
                    </ul>
                    <p>
                        Only the planner shows any of this. The extraction pages and calendar look as they always do,
                        since where a refinery goes next is not for every member to read. A blueprint holding the
                        refinery is marked, and cannot be applied until you resume the refinery or take it out of the
                        blueprint. If the plans change, <strong>Resume normal operations</strong> on the refinery's
                        card clears the mark after asking, and planning and reminders carry on. Pulls removed when it
                        was marked are not brought back.
                    </p>

                    <h4><i class="fas fa-industry"></i> The refinery panel</h4>
                    <p>
                        Down the right-hand side, each refinery shows its cadence, last arrival and next projected
                        pull, plus two badges:
                    </p>
                    <ul>
                        <li>
                            A <strong>coverage badge</strong> &mdash; <code>Planned 2&times;</code> when upcoming pulls
                            are booked, or an amber <code>Not planned</code> when none are. That's your "did I skip a
                            moon?" check, counted across the whole horizon rather than just the visible months, and
                            uncovered refineries sort to the top.
                        </li>
                        <li>
                            An <strong>ore tier badge</strong> (R4 through R64) taken from the moon's most recent
                            composition, so you can see at a glance which refineries are sitting on the valuable
                            moons.
                        </li>
                    </ul>

                    <h4><i class="fas fa-history"></i> Change history</h4>
                    <p>
                        The <strong>History</strong> button lists who created, moved or removed each planned pull and
                        what the times were before and after. Auto-fill runs are recorded as a single entry.
                    </p>

                    <div class="info-box">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Getting started:</strong> open the Moon Planner, press
                        <strong>Auto-fill from History</strong>, then adjust anything that looks wrong. Refineries
                        flagged <code>Not planned</code> or "not enough history" are the ones needing a manual slot.
                    </div>
                </div>
            </div>

            {{-- Find Moons Section --}}
            <div id="find-moons" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-search-location"></i>
                        Find Moons
                    </h3>
                    <p>
                        Find Moons sits at the top of the <strong>Extraction Simulator</strong> and searches every moon
                        SeAT has a scan for, rather than making you look them up one at a time. It answers questions
                        like "which R32 moons in this region are worth anchoring on", "is there anything better than
                        this moon nearby", and "which moons did we already decide were taken".
                    </p>

                    <div class="info-box">
                        <i class="fas fa-key"></i>
                        <strong>Who can use it:</strong> Directors, Moon Managers, and anyone with the standalone
                        <code>mining-manager.moon_finder</code> permission. Listing every valuable moon in a region at
                        once is stronger intel than a single lookup, so it is gated separately. Members keep the
                        simulator underneath it.
                    </div>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-map-marked-alt"></i>
                        Where to look
                    </h3>
                    <ul>
                        <li>
                            <strong>Moon name.</strong> Any part of a moon's name finds it on its own, so
                            <code>9OLQ-6 V - Moon 15</code> or just <code>9OLQ-6</code> both work. Use this when you
                            already know the moon and want its class, value and quality.
                        </li>
                        <li>
                            <strong>Region, Constellation and System.</strong> Pick them in any order. Choosing a
                            system fills in the constellation and region for you, so you never have to drill down from
                            the top if you already know where you are going. Each box searches as you type.
                        </li>
                        <li>
                            <strong>Security.</strong> High, low or null security. Worth pairing with class, since the
                            rarest ores only occur in the lower bands.
                        </li>
                    </ul>
                    <div class="info-box">
                        <i class="fas fa-eraser"></i>
                        <strong>Clearing a place filter:</strong> each box has a small red cross on the right once
                        something is chosen. Use that rather than deleting the text, which leaves the choice behind.
                    </div>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-gem"></i>
                        What the moon has to contain
                    </h3>
                    <ul>
                        <li>
                            <strong>Class</strong> is a moon's rarest ore, not its average. A moon holding a sliver of
                            R64 alongside three common ores is an R64 moon. It is the quickest way to cut a region down
                            to the moons worth reading.
                        </li>
                        <li>
                            <strong>Moon ore share</strong> is how much of the chunk is moon ore rather than regular
                            asteroid ore: the moon ores in the scan added together, with the rest being Veldspar and
                            its like. A high-class moon with a thin share can be worth less per pull than a plainer
                            moon that is mostly moon ore, which is why this is a filter and not just a column.
                        </li>
                        <li>
                            <strong>Required ores</strong> narrows to moons containing specific ores, for when you are
                            chasing a reaction chain rather than ISK.
                        </li>
                        <li>
                            <strong>Composition rules</strong> set a minimum share for a rarity, such as R16 at least
                            20%. Use it to rule out moons where the valuable ore is technically present but not in
                            enough quantity to be worth the cycle.
                        </li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-coins"></i>
                        Value and quality
                    </h3>
                    <ul>
                        <li>
                            <strong>Value by</strong> switches between refined value and raw ore value for this search.
                            Refined leads by default: raw moon ore barely trades, so a single thin sell order can put a
                            silly price on a moon, while what the ore reprocesses into is steady. Both numbers are
                            always shown and both are labelled, so they can never be read as the same figure. The
                            default for the whole plugin is under <strong>Settings, Pricing</strong>.
                        </li>
                        <li>
                            <strong>Days</strong> is the extraction window the value is worked out over. It has to
                            match what you actually pull: a value over 28 days is four times a weekly one, and
                            comparing the two will mislead you.
                        </li>
                        <li>
                            <strong>Value range</strong> filters on that figure once it is calculated.
                        </li>
                        <li>
                            <strong>Quality</strong> ranks a moon against every scanned moon <em>of its own class</em>,
                            not against all moons. Exceptional is the top 10%, Excellent the top 25%, Good the top
                            half, Average the top 75% and Poor the rest. So an Exceptional R4 is a very good R4 and
                            still worth far less than an average R64. The results say where a moon sits, such as
                            "top 38% of 1,204 R4 moons". A class needs at least five scanned moons before anything in
                            it is rated.
                        </li>
                    </ul>
                    <div class="info-box">
                        <i class="fas fa-database"></i>
                        <strong>Where the prices come from:</strong> the price cache your scheduled refresh keeps, not
                        a live lookup per search. Simulate a moon to see whether any of its prices are missing.
                    </div>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-flag"></i>
                        Marking moons: ours, theirs, and worth a look
                    </h3>
                    <p>
                        ESI only reports structures your own corporation owns, so nothing in the game tells you which
                        moons somebody else is already drilling. These three marks are how the page remembers what your
                        corp has worked out between searches.
                    </p>
                    <ul>
                        <li>
                            <strong>Ours</strong> is automatic. A moon one of your refineries still sits on is marked
                            for you, using your own extraction history. Nothing to maintain.
                        </li>
                        <li>
                            <strong>Claimed</strong> is something a person records: the corporation or alliance holding
                            the moon, and a note if there is anything worth remembering. The badge names who reported
                            it and when, so an old claim can be judged on its age. <strong>Moon is free</strong> clears
                            it when the moon comes back on the market, closing the report rather than deleting it, so a
                            moon that changes hands keeps its history. A claim also clears itself once one of your own
                            refineries starts drilling that moon.
                        </li>
                        <li>
                            <strong>Watchlist</strong> is the shared "come back to this one" list, with a note saying
                            why. Star a moon you cannot take today and the next person searching picks up where you
                            left off instead of rediscovering it. A watched moon drops off by itself once you put a
                            refinery on it.
                        </li>
                    </ul>
                    <p>
                        Each of the three has its own filter, and each can <em>show only</em> those moons or
                        <em>hide</em> them. Hiding all three is how you look for free ground you have not already
                        assessed.
                    </p>
                    <div class="info-box">
                        <i class="fas fa-user-shield"></i>
                        <strong>Who can mark:</strong> claiming and watching need Director, Moon Manager or Moon
                        Finder. Anyone who can open the simulator sees the badges.
                    </div>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-table"></i>
                        Reading the results
                    </h3>
                    <ul>
                        <li>
                            Results list each moon's location, class, ores, value and quality, with whatever is flagged
                            on it. <strong>Click any column heading to sort by it, and click it again to turn it
                            around.</strong>
                        </li>
                        <li>
                            <strong>Simulate</strong> opens that moon in the simulator below, with its full ore
                            breakdown and both value figures.
                        </li>
                        <li>
                            When you simulate a moon, Find Moons also lists up to three <strong>better scanned moons of
                            the same class</strong> in the constellation or region you searched, or around the moon
                            itself. That is the answer to "is this the best we can do here".
                        </li>
                        <li>
                            <strong>Export CSV</strong> downloads every match, not just the page you are looking at,
                            carrying the marks with it. Note that this respects <strong>Allow Data Export</strong> in
                            Settings, Features.
                        </li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-lightbulb"></i>
                        Two searches worth knowing
                    </h3>
                    <div class="info-box">
                        <strong>Free ground worth taking.</strong> Pick a region, set class to R32 or R64, hide Ours,
                        hide Claimed, and sort by value. What is left is the moons nobody has assessed and nobody is
                        known to hold.
                    </div>
                    <div class="info-box" style="margin-top: 0.75rem;">
                        <strong>Is this refinery in the right place.</strong> Search the system you are in, sort by
                        value, and see where your own moon lands. Simulate it and read the better moons of the same
                        class nearby.
                    </div>
                </div>
            </div>

            {{-- Analytics & Reports Section --}}
            <div id="analytics" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        {{ trans('mining-manager::help.analytics_reports_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.analytics_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.available_reports') }}</h4>
                    <ul>
                        <li><strong>{{ trans('mining-manager::help.report_monthly') }}:</strong> {{ trans('mining-manager::help.report_monthly_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.report_member') }}:</strong> {{ trans('mining-manager::help.report_member_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.report_event') }}:</strong> {{ trans('mining-manager::help.report_event_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.report_moon') }}:</strong> {{ trans('mining-manager::help.report_moon_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.report_comparison') }}:</strong> {{ trans('mining-manager::help.report_comparison_desc') }}</li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Performance Charts
                    </h3>
                    <p>
                        Analytics pages open on your own corporation. <em>All Corporations</em> is still in the
                        dropdown when you want every corporation on the install, but it is a choice you make
                        rather than where you land.
                    </p>
                    <p>
                        Every miner counts, whether SeAT has a record of the character or not. One of your own
                        corporations means the same here as on the dashboard: its members SeAT knows, members only
                        the background lookups know, and anyone whose mining was recorded under it, less anyone
                        known to be in another corporation. Pick a guest corporation and you get the characters
                        placed in it, by SeAT or by the lookups.
                    </p>
                    <p>On top of the dates and corporation, Performance Charts can be narrowed three ways:</p>
                    <ul>
                        <li><strong>Source:</strong> all mining, my moons only, all moon ore, or other moons only. <em>My moons</em> reads your corporation's moon observers, so it means what it says.</li>
                        <li><strong>Ore:</strong> regular ore, moon ore, ice, gas, abyssal or triglavian.</li>
                        <li><strong>Player:</strong> picked by main character, and counted across every character that player mines on.</li>
                    </ul>
                    <p>
                        The export button carries the same filters, so a downloaded file matches the page.
                        <em>Other moons</em> is worked out rather than recorded: it is moon ore none of your
                        observers saw, which usually means somebody else's moon and occasionally one of yours
                        without an observer. Filters that read ore categories say so, because mining from before
                        the classification cutover keeps the categories it was billed on. When the filters find
                        nothing, the page says why instead of showing empty charts.
                    </p>
                    <p>
                        Moon managers can open <strong>Moon Analytics</strong>, below. The rest of Analytics is
                        for directors.
                    </p>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-moon"></i>
                        {{ trans('mining-manager::help.moon_analytics') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.moon_analytics_desc') }}</p>
                    <ul>
                        <li>{{ trans('mining-manager::help.moon_analytics_utilization') }}</li>
                        <li>{{ trans('mining-manager::help.moon_analytics_pool_vs_mined') }}</li>
                        <li>{{ trans('mining-manager::help.moon_analytics_per_extraction') }}</li>
                        <li>{{ trans('mining-manager::help.moon_analytics_rigs') }}</li>
                        <li>{{ trans('mining-manager::help.moon_analytics_popularity') }}</li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3>
                        <i class="fas fa-file-export"></i>
                        {{ trans('mining-manager::help.exporting_data') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.exporting_desc') }}</p>
                    <p>
                        Exporting can be switched off under Settings, Features, <strong>Allow Data Export</strong>.
                        Off means off for everyone, directors and admins included, and it covers the mining
                        ledger, members' own exports, analytics and report downloads. The check sits on the downloads themselves, so an old export link stops
                        working too. Your settings backup is separate and stays available.
                    </p>
                </div>
            </div>

            {{-- Settings Section --}}
            <div id="settings" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-cog"></i>
                        {{ trans('mining-manager::help.settings_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.settings_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.settings_tabs') }}</h4>

                    <h4>{{ trans('mining-manager::help.settings_global_header') }}</h4>

                    <h5><i class="fas fa-sliders-h text-primary"></i> {{ trans('mining-manager::help.settings_general') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_general_desc') }}</p>

                    <h5><i class="fas fa-tag text-danger"></i> {{ trans('mining-manager::help.settings_pricing') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_pricing_desc') }}</p>

                    <h5><i class="fas fa-toggle-on text-success"></i> {{ trans('mining-manager::help.settings_features') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_features_desc') }}</p>

                    <h5><i class="fas fa-plug text-info"></i> {{ trans('mining-manager::help.settings_webhooks') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_webhooks_desc') }}</p>

                    <h5><i class="fas fa-bell text-warning"></i> {{ trans('mining-manager::help.settings_notifications') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_notifications_desc') }}</p>

                    <h5><i class="fas fa-project-diagram text-info"></i> {{ trans('mining-manager::help.settings_routing_map') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_routing_map_desc') }}</p>

                    <h5><i class="fas fa-tachometer-alt text-primary"></i> {{ trans('mining-manager::help.settings_dashboard') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_dashboard_desc') }}</p>

                    <h4>{{ trans('mining-manager::help.settings_system_header') }}</h4>

                    <h5><i class="fas fa-cogs text-warning"></i> {{ trans('mining-manager::help.settings_advanced') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_advanced_desc') }}</p>

                    <h5><i class="fas fa-question-circle text-info"></i> {{ trans('mining-manager::help.settings_help') }}</h5>
                    <p>{{ trans('mining-manager::help.settings_help_desc') }}</p>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>{{ trans('mining-manager::help.settings_warning') }}:</strong> {{ trans('mining-manager::help.settings_warning_desc') }}
                    </div>
                </div>
            </div>

            {{-- CLI Commands Section --}}
            <div id="commands" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-terminal"></i>
                        {{ trans('mining-manager::help.cli_commands') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.cli_intro') }}</p>

                    {{-- Scheduled Commands --}}
                    <h4><i class="fas fa-clock text-primary"></i> {{ trans('mining-manager::help.cli_scheduled') }}</h4>
                    <p>{{ trans('mining-manager::help.cli_scheduled_desc') }}</p>

                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <thead>
                                <tr>
                                    <th>Command</th>
                                    <th>Schedule</th>
                                    <th>Description &amp; Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>mining-manager:process-ledger</code></td>
                                    <td><span class="badge badge-info">{{ trans('mining-manager::help.schedule_30min') }}</span></td>
                                    <td>Process corporation observer mining data from ESI. Creates mining ledger entries with ore type flags, prices, and generates daily summaries.<br>
                                        <small class="text-muted">Options: <code>--observer_id=</code> specific structure, <code>--character_id=</code> specific character, <code>--days=30</code> lookback period, <code>--recalculate</code> recalc existing entries</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:import-character-mining</code></td>
                                    <td><span class="badge badge-info">{{ trans('mining-manager::help.schedule_30min') }}</span></td>
                                    <td>Import personal mining data from SeAT's ESI cache (belt, anomaly, ice, gas mining). Each run covers the last two days of mining. Mining SeAT only saves after that is left out on purpose, so days already billed and summarised don't change.<br>
                                        <small class="text-muted">Options: <code>--character_id=</code> specific character, <code>--days=30</code> lookback by mining date, <code>--force</code> re-import existing entries, <code>--dry-run</code> show what it would change without writing anything</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:resolve-characters</code></td>
                                    <td><span class="badge badge-info">{{ trans('mining-manager::help.schedule_1min') }}</span></td>
                                    <td>Looks up names and corporations for characters SeAT does not know, such as visiting miners, and keeps them for every page to read, so no page ever waits on ESI. Every minute it takes the characters pages have asked for, which show as <em>Character info in progress</em> until then, and the page refreshes once they are in. Every 10 minutes it also takes miners SeAT has no affiliation for, which is how the dashboard tells a visiting miner from a member. ESI comes first, then EVEWho and zKillboard when ESI is down, and it stops calling ESI while the error budget it shares with SeAT is low.<br>
                                        <small class="text-muted">Options: <code>--requested</code> only the characters pages asked for, <code>--stats</code> what the table holds</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:cache-prices</code></td>
                                    <td><span class="badge badge-info">{{ trans('mining-manager::help.schedule_4hours') }}</span></td>
                                    <td>Cache market price data from your configured price provider for all ore types.
                                        Prices are asked for in one request per hundred types, and a type nothing came
                                        back for keeps the price it had rather than being zeroed. When the provider fails
                                        outright, the fallback price provider from Settings, Pricing prices instead, and
                                        when that cannot help either every cached price is kept.<br>
                                        <small class="text-muted">Options: <code>--type=all</code> (ore|compressed-ore|moon|materials|minerals|ice|gas|all), <code>--region=10000002</code> region ID, <code>--force</code> refresh even if cache is fresh</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:update-ledger-prices</code></td>
                                    <td><span class="badge badge-primary">{{ trans('mining-manager::help.schedule_daily') }}</span> 1:00 AM</td>
                                    <td>Lock in current market prices on mining ledger entries. Also regenerates affected daily summaries. First step in the nightly pipeline.<br>
                                        <small class="text-muted">Options: <code>--days=1</code> days to re-price, <code>--all-unpriced</code> all entries with 0 value, <code>--force</code> re-price even if value > 0, <code>--character_id=</code> specific character</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:update-daily-summaries</code></td>
                                    <td><span class="badge badge-primary">{{ trans('mining-manager::help.schedule_daily') }}</span> 1:30 AM</td>
                                    <td>Safety net: catches non-observer mining (belt mining) and late ESI data. Generates/updates daily summaries with a per-ore breakdown. Also runs a <strong>reconciliation step</strong> over the last 4 days: a character-imported entry is removed when your Moon Owner Corporation's observer data, which can arrive 12-24h late, recorded exactly the same quantity for that pilot, ore and day. <code>mining-manager:reconcile-personal-mining</code> runs the same step on demand.<br>
                                        <small class="text-muted">Options: <code>--days=2</code> days back, <code>--date=YYYY-MM-DD</code> specific date, <code>--month=YYYY-MM</code> entire month, <code>--today-only</code> fast mode, <code>--character_id=</code> specific character</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:update-events</code></td>
                                    <td><span class="badge badge-danger">Every minute</span></td>
                                    <td>Auto-transition event status (planned &rarr; active &rarr; completed) with Discord/Slack notifications on each transition. Also updates participant data by auto-detecting miners from the mining ledger whose activity falls within the event's time window and location scope (system, constellation, region, or global).<br>
                                        <small class="text-muted">Options: <code>--event_id=</code> specific event, <code>--active</code> only active events</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:update-extractions</code></td>
                                    <td><span class="badge badge-info">{{ trans('mining-manager::help.schedule_2hours') }}</span></td>
                                    <td><strong>State system</strong> — refreshes moon extraction data from corporation structure ESI endpoints. Detects manual and auto-fracture notifications (<code>MoonminingLaserFired</code>, <code>MoonminingAutomaticFracture</code>) and director cancellations (<code>MoonminingExtractionCancelled</code>) — cancelled extractions are flagged so the notification watchdog skips them. Answers the question "what does EVE say is happening?". Does <em>not</em> fire moon arrival notifications — that's the notification system below. The same run sends Moon Extraction Cancelled, the Moon Not Rescheduled and Moons Need Planning reminders, Next Pull Can Be Started and Moon Scan Missing, and takes planned pulls off refineries confirmed gone (Refinery Gone).<br>
                                        <small class="text-muted">Options: <code>--structure_id=</code> specific structure, <code>--corporation_id=</code> specific corp, <code>--active-only</code> only active extractions</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:check-extraction-arrivals</code></td>
                                    <td><span class="badge badge-danger">Every minute</span></td>
                                    <td><strong>Notification system</strong> — fires <code>moon_arrival</code> Discord/Slack notifications based on the stored <code>chunk_arrival_time</code>. Pure time arithmetic — does not call ESI. Answers the question "has arrival time passed and we haven't notified yet?". Idempotent via the <code>notification_sent</code> flag — safe to re-run, handles missed cron ticks, handles extractions imported directly as 'ready'. The same run sends Moon Chunk Unstable before a chunk goes unstable, and Extraction Started, each once per extraction.<br>
                                        <small class="text-muted">Options: <code>--hours-back=72</code> only notify for arrivals within this window (safety on historical data), <code>--limit=50</code> max dispatches per run, <code>--unstable-warning-hours=2</code> how long before a chunk turns unstable to warn, <code>--dry-run</code> preview without firing</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:scan-metenox-cargo-fill</code></td>
                                    <td><span class="badge badge-info">Every 5 min</span></td>
                                    <td>Sends Metenox Cargo Bay Full when a drill's bay crosses the threshold set under Settings, Notifications (85% by default), once until the bay is emptied again.</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:validate-lifecycle-integrity</code></td>
                                    <td><span class="badge badge-primary">{{ trans('mining-manager::help.schedule_daily') }}</span> 3:00 AM</td>
                                    <td>Warns when a stored extraction status has drifted from what the chunk's own times say it should be. Read-only unless asked.<br>
                                        <small class="text-muted">Options: <code>--fix</code> correct the rows it finds, <code>--days=14</code> how far back to look, <code>--quiet-ok</code> say nothing when all is well</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:recalculate-extraction-values</code></td>
                                    <td><span class="badge badge-info">{{ trans('mining-manager::help.schedule_twice_daily') }}</span></td>
                                    <td>Recalculate moon extraction values based on current prices. Useful for extractions arriving soon.<br>
                                        <small class="text-muted">Options: <code>--hours=4</code> arrival window, <code>--force</code> recalculate even if recently done</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:calculate-monthly-stats</code></td>
                                    <td><span class="badge badge-success">{{ trans('mining-manager::help.schedule_monthly') }}</span> 3:00 AM + <span class="badge badge-info">{{ trans('mining-manager::help.schedule_30min') }}</span></td>
                                    <td>Pre-calculate and store dashboard statistics. Full run on the 2nd of each month at 3:00 AM for the closed month. Fast <code>--current-month</code> mode runs every 30 minutes to keep live dashboard data current.<br>
                                        <small class="text-muted">Options: <code>--month=YYYY-MM</code> specific month, <code>--user_id=</code> specific user, <code>--recalculate</code> recalculate existing, <code>--current-month</code> fast mode, <code>--all-history</code> all historical months</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:detect-jackpots</code></td>
                                    <td><span class="badge badge-primary">{{ trans('mining-manager::help.schedule_daily') }}</span></td>
                                    <td>Detect jackpot moon extractions from mining data and verify manual reports. Auto-detected jackpots are immediately verified. Manual reports are confirmed when jackpot ores appear in mining data, or marked unverified if the extraction expires with no data.<br>
                                        <small class="text-muted">Options: <code>--all</code> check all extractions, <code>--days=30</code> lookback period</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:archive-extractions</code></td>
                                    <td><span class="badge badge-primary">{{ trans('mining-manager::help.schedule_daily') }}</span></td>
                                    <td>Archive completed moon extractions to history table and calculate actual mined values. Handles three terminal states: <code>expired</code> and <code>fractured</code> extractions are archived 7 days after <code>natural_decay_time</code>; <code>cancelled</code> extractions are archived 7 days after <code>updated_at</code> (cancellation detection time), since their originally planned <code>natural_decay_time</code> may still be in the future.<br>
                                        <small class="text-muted">Options: <code>--days=7</code> archive older than N days, <code>--keep-months=12</code> keep history for N months, <code>--dry-run</code> preview without changes</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:generate-reports</code></td>
                                    <td><span class="badge badge-success">{{ trans('mining-manager::help.schedule_monthly') }}</span> Day 9 @ 4:05 AM</td>
                                    <td>Generate previous month's mining report. Runs on day 9 of the month. Dedup guard: skips if a report for the same period+type already exists.<br>
                                        <small class="text-muted">Options: <code>--type=monthly</code> (daily|weekly|monthly|custom), <code>--start=YYYY-MM-DD</code>, <code>--end=YYYY-MM-DD</code>, <code>--format=json</code> (json|csv|pdf), <code>--force</code> regenerate even if report exists, <code>--scheduled</code> process user-defined report schedules (runs hourly)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:finalize-month</code></td>
                                    <td><span class="badge badge-success">{{ trans('mining-manager::help.schedule_monthly') }}</span> 2:00 AM</td>
                                    <td>Lock previous month's daily summaries as final so they won't be regenerated. Runs on the 2nd at 2:00 AM to allow late-arriving observer data (12-24h ESI lag) to settle. Refuses to finalize the current or future months.<br>
                                        <small class="text-muted">Arguments: <code>{month?}</code> optional month in YYYY-MM format (defaults to previous month)</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Diagnostic Commands --}}
                    <h4 class="mt-4"><i class="fas fa-wrench text-info"></i> {{ trans('mining-manager::help.cli_diagnostic') }}</h4>
                    <p>{{ trans('mining-manager::help.cli_diagnostic_desc') }}</p>

                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <tbody>
                                <tr>
                                    <td style="width: 45%;"><code>mining-manager:diagnose-character {character_id}</code></td>
                                    <td>Diagnose a specific character's corporation lookup, affiliation data, and mining records. Requires the character ID as an argument.</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:diagnose-affiliation</code></td>
                                    <td>Check character affiliations and alt grouping. Verifies CharacterInfo relationships are working correctly.</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:diagnose-extractions</code></td>
                                    <td>Diagnose moon extraction data issues. Checks for missing or incomplete extraction records from ESI.</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:diagnose-prices</code></td>
                                    <td>Test price provider connectivity, cache health, and pricing accuracy.<br>
                                        <small class="text-muted">Options: <code>--detailed</code> full breakdown, <code>--test-provider</code> test current provider, <code>--show-missing</code> list items without prices, <code>--show-sources</code> cache vs fallback, <code>--show-coverage</code> coverage stats for every tracked type</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:diagnose-type-ids</code></td>
                                    <td>Verify ore type ID mappings against ESI API or local database.<br>
                                        <small class="text-muted">Options: <code>--category=</code> one category, such as ore, moon, ice, gas, event, mutanite or all, <code>--include-abyssal</code> include Pochven ores, <code>--test-jackpot</code> test jackpot detection, <code>--verify-db</code> verify against local DB</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Diagnostic Page (Web UI) — internal / dev only --}}
                    <h4 class="mt-4">
                        <i class="fas fa-stethoscope text-info"></i>
                        {{ trans('mining-manager::help.diagnostic_page_title') }}
                        <span class="badge badge-danger ml-2" style="font-size: 0.75rem; vertical-align: middle;">
                            <i class="fas fa-flask"></i> DEV
                        </span>
                    </h4>
                    <p>{{ trans('mining-manager::help.diagnostic_page_desc') }}</p>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>How to access:</strong> The Diagnostic page is intentionally NOT linked from the Mining Manager sidebar. Admins navigate to it manually by typing
                        <code>/mining-manager/diagnostic</code>
                        in the address bar (relative to your SeAT install root). Treat as an internal/dev tool, not a user-facing feature. The Master Test tab is safe to run on production (read-only); the Test Data Generation tab creates fake corps/characters/mining and is for development environments only.
                    </div>

                    <h5><i class="fas fa-rocket text-primary"></i> Master Test</h5>
                    <p>One-click read-only smoke chain that runs a set of checks covering every major area of the plugin: schema integrity (every migration applied, every expected column present, every index in place), settings consistency (pricing/notification/feature flags load with the expected shape), pricing path (`validateProviderConfig` passes, in-process Tritanium roundtrip), notifications (webhooks HTTPS-only, custom-template injection-safety live-verified by feeding hostile input through the template engine), lifecycle (cron schedules present, moon extractions populated), ore classification, security hardening (CAS target columns, ScheduleSeeder firstOrCreate inheritance), and infra (cache put/get roundtrip).</p>
                    <p>Tests are idempotent (read-only — never mutates production data), fast (sub-second per test, full chain typically completes in under 30 seconds), and self-contained (per-test try/catch — a single broken test can't crash the run). Click <em>Run Master Test</em>, get a pass/warn/fail/skip table grouped by category. Use the "Show only issues" filter when something needs attention.</p>
                    <div class="info-box">
                        <i class="fas fa-shield-alt"></i>
                        <strong>{{ trans('mining-manager::help.tip') }}:</strong> Run the Master Test after every plugin upgrade or settings change. It catches stale route caches, missing migrations and partial deploys before they cause real-world dispatch failures.
                    </div>

                    <h5 class="mt-3">{{ trans('mining-manager::help.notif_test_title') }}</h5>
                    <p>{{ trans('mining-manager::help.notif_test_desc') }}</p>
                    <ul>
                        <li><strong>{{ trans('mining-manager::help.notif_test_preview') }}</strong> — {{ trans('mining-manager::help.notif_test_preview_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.notif_test_live') }}</strong> — {{ trans('mining-manager::help.notif_test_live_desc') }}</li>
                        <li><strong>{{ trans('mining-manager::help.notif_test_chain') }}</strong> — {{ trans('mining-manager::help.notif_test_chain_desc') }}</li>
                    </ul>
                    <div class="info-box">
                        <i class="fas fa-bolt"></i>
                        <strong>{{ trans('mining-manager::help.tip') }}:</strong> {{ trans('mining-manager::help.notif_test_chain_tip') }}
                    </div>

                    {{-- Initialize Command --}}
                    <h4 class="mt-4"><i class="fas fa-magic text-success"></i> {{ trans('mining-manager::help.cli_initialize') }}</h4>
                    <div class="help-card" style="border-left: 4px solid #28a745;">
                        <p>{{ trans('mining-manager::help.cli_initialize_desc') }}</p>
                        <p><code>docker exec -it seat-docker-front-1 php artisan mining-manager:initialize</code></p>

                        <h5>{{ trans('mining-manager::help.cli_initialize_phase1') }}</h5>
                        <p>{{ trans('mining-manager::help.cli_initialize_phase1_desc') }}</p>

                        <h5>{{ trans('mining-manager::help.cli_initialize_phase2') }}</h5>
                        <p>{{ trans('mining-manager::help.cli_initialize_phase2_desc') }}</p>
                        <ol>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_prices') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_observer') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_character') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_flags') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_price_entries') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_summaries') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_extractions') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_jackpots') }}</li>
                            <li>{{ trans('mining-manager::help.cli_initialize_step_stats') }}</li>
                        </ol>

                        <h5>{{ trans('mining-manager::help.cli_initialize_phase3') }}</h5>
                        <p>{{ trans('mining-manager::help.cli_initialize_phase3_desc') }}</p>

                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>{{ trans('mining-manager::help.important') }}:</strong> {{ trans('mining-manager::help.cli_initialize_warning') }}
                        </div>

                        <p class="mt-2"><small class="text-muted">{{ trans('mining-manager::help.cli_initialize_options') }}</small></p>
                    </div>

                    {{-- Data Management Commands --}}
                    <h4 class="mt-4"><i class="fas fa-database text-warning"></i> {{ trans('mining-manager::help.cli_data_management') }}</h4>
                    <p>{{ trans('mining-manager::help.cli_data_management_desc') }}</p>

                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <tbody>
                                <tr>
                                    <td style="width: 45%;"><code>mining-manager:backfill-ore-types</code></td>
                                    <td>Re-stamps the ore classification on existing mining ledger rows, so older rows pick up ore the registry has since learned to recognise.<br>
                                        <small class="text-muted">Options: <code>--dry-run</code> report every category it would move, <code>--batch=1000</code> records per batch</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:reconcile-personal-mining</code></td>
                                    <td>Removes personal mining rows that your Moon Owner Corporation's observer already recorded, only where the quantity matches exactly, so mining on somebody else's moon is never taken off. Each day it changes has its daily summary rebuilt. The nightly summary run does the last 4 days by itself; use this to reach further back.<br>
                                        <small class="text-muted">Options: <code>--days=4</code> how far back to look, <code>--dry-run</code> report what would go and write nothing</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:backfill-event-records</code></td>
                                    <td>Fills in the mining that counts towards each event for events that predate that record.<br>
                                        <small class="text-muted">Options: <code>--event=</code> one event, <code>--status=</code> only active, completed or planned events, <code>--fresh</code> rebuild from scratch after changing an event's scope</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:backfill-extraction-notifications</code></td>
                                    <td>Backfill ore composition data from character notifications for existing moon extractions. A chunk already priced at its fracture is left as it is.<br>
                                        <small class="text-muted">Options: <code>--limit=100</code> max extractions, <code>--structure=</code> specific structure, <code>--days=90</code> lookback, <code>--dry-run</code> preview, <code>--force</code> overwrite existing</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:backfill-extraction-history</code></td>
                                    <td><strong>Reconstruct historical extraction records</strong> from <code>MoonminingExtractionStarted</code> EVE notifications. When you install Mining Manager on a corp that already has months of mining history, ESI only returns active/upcoming extractions — completed cycles are not retrievable. But character notifications retain that history for as long as SeAT keeps them. This command parses those notifications, dedupes by (structure, ready time), matches each to its corresponding fracture/cancel notification, calculates actual mined values from <code>mining_ledger</code> where available, and inserts rows into <code>moon_extraction_history</code> so the moon detail pages show full historical context. <strong>Scoped to Moon Owner Corporation only</strong> — pre-loads the set of structures owned by MOC from <code>corporation_structures</code> and skips notifications for any other structure (reports the skipped count). Shows <strong>progress bars</strong> for both the dedup pass (YAML parsing) and the main processing pass (DB queries per extraction) — with thousands of notifications this can take a few minutes. Automatically invoked by <code>mining-manager:initialize</code> during Phase 3 historical backfill if the admin opts in.<br>
                                        <small class="text-muted">Options: <code>--structure=ID</code> single structure scope (must belong to MOC), <code>--days=365</code> lookback window, <code>--limit=1000</code> max per run, <code>--dry-run</code> preview without writing, <code>--force</code> recreate existing history rows (destructive)</small>
                                        <br><small class="text-warning"><i class="fas fa-info-circle"></i> Historical ISK prices are unknown, so <code>estimated_value_at_arrival</code> is left NULL for backfilled rows — new extractions going forward will have it populated properly. <code>actual_mined_value</code> is computed from <code>mining_ledger</code> where data exists for the structure during the extraction window.</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:backup-data</code></td>
                                    <td><strong>Export plugin data to JSON</strong> for offline backup, migration to another SeAT install, or disaster recovery. Writes one JSON file per plugin-owned table (settings, webhook configs, events, moon extractions + history, mining ledger + summaries, reports, etc.) into a timestamped backup directory. Run before major version upgrades or settings changes you want to be able to undo. Companion to <code>restore-data</code> below.<br>
                                        <small class="text-muted">Options: <code>--path=</code> custom backup directory (defaults to <code>/opt/seat-docker/mining-manager-backup/</code>), <code>--tables=a,b,c</code> backup only specific tables, <code>--no-ledger</code> skip <code>mining_ledger</code> (the largest table; can be regenerated from observer data)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:restore-data</code></td>
                                    <td><strong>Restore plugin data from a <code>backup-data</code> export</strong>. Reads JSON files from a backup directory and re-creates rows in the right order to satisfy foreign-key constraints. Useful for moving the plugin between SeAT installs, recovering from a bad settings change, or seeding a development environment from production data. Auto-detects the latest backup if no path is given; truncates each target table before restore unless <code>--no-truncate</code> is passed.<br>
                                        <small class="text-muted">Options: <code>path</code> (positional argument — backup directory; defaults to latest in <code>/opt/seat-docker/mining-manager-backup/</code>), <code>--tables=a,b,c</code> restore only specific tables, <code>--force</code> skip confirmation prompts, <code>--no-truncate</code> append rather than replace (use with caution — can cause unique-constraint violations)</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Common Manual Commands --}}
                    <h4 class="mt-4"><i class="fas fa-sync text-success"></i> {{ trans('mining-manager::help.cli_manual') }}</h4>
                    <p>{{ trans('mining-manager::help.cli_manual_desc') }}</p>

                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <tbody>
                                <tr>
                                    <td style="width: 55%;"><code>mining-manager:update-daily-summaries --month=2026-03</code></td>
                                    <td>Regenerate all daily summaries for March 2026 with current prices</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:update-ledger-prices --force --days=30</code></td>
                                    <td>Force re-price entries from the last 30 days and regenerate their daily summaries</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:cache-prices --force</code></td>
                                    <td>Force refresh all cached prices even if cache is fresh</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:process-ledger --recalculate</code></td>
                                    <td>Reprocess all mining data, recalculating prices</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:import-character-mining --dry-run</code></td>
                                    <td>Show what the next personal mining import would add or change, without writing anything</td>
                                </tr>
                                <tr>
                                    <td><code>mining-manager:diagnose-prices --detailed --show-missing</code></td>
                                    <td>Full price diagnostic showing all categories and missing items</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Test Commands --}}
                    <h4 class="mt-4"><i class="fas fa-flask text-danger"></i> {{ trans('mining-manager::help.cli_test') }}</h4>
                    <p>{{ trans('mining-manager::help.cli_test_desc') }}</p>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Test commands should only be used in development environments. They create fake data that should not be mixed with real production data.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm" style="color: #d1d5db;">
                            <tbody>
                                <tr>
                                    <td style="width: 45%;"><code>mining-manager:generate-test-data</code></td>
                                    <td>Generate fake corporations, characters, and mining ledger entries for testing.<br>
                                        <small class="text-muted">Options: <code>--corporations=3</code> number of corps, <code>--characters=5</code> per corp, <code>--days=30</code> of mining data, <code>--entries=10</code> per day per character, <code>--cleanup</code> remove existing test data first</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> All commands should be run from your SeAT installation directory. For Docker: <code>docker exec -it seat-docker-front-1 php artisan command-name</code>. For bare-metal: <code>php artisan command-name</code>.
                    </div>
                </div>
            </div>

            {{-- Permissions Section --}}
            <div id="permissions" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-shield-alt"></i>
                        {{ trans('mining-manager::help.permissions_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.permissions_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.available_permissions') }}</h4>

                    <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                        <div class="feature-item" style="border-left: 4px solid #6c757d;">
                            <h5><span class="badge badge-secondary">{{ trans('mining-manager::help.perm_view') }}</span></h5>
                            <p>{{ trans('mining-manager::help.perm_view_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #17a2b8;">
                            <h5><span class="badge badge-info">{{ trans('mining-manager::help.perm_member') }}</span></h5>
                            <p>{{ trans('mining-manager::help.perm_member_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #6f42c1;">
                            <h5><span class="badge badge-secondary">{{ trans('mining-manager::help.perm_view_all') }}</span></h5>
                            <p>{{ trans('mining-manager::help.perm_view_all_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #f39c12;">
                            <h5><span class="badge badge-warning">{{ trans('mining-manager::help.perm_director') }}</span></h5>
                            <p>{{ trans('mining-manager::help.perm_director_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #dc3545;">
                            <h5><span class="badge badge-danger">{{ trans('mining-manager::help.perm_admin') }}</span></h5>
                            <p>{{ trans('mining-manager::help.perm_admin_desc') }}</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #9b59b6;">
                            <h5><span class="badge" style="background:#9b59b6;">Moon Manager</span> <small class="text-muted">(capability)</small></h5>
                            <p><code>mining-manager.moon_manager</code> &mdash; a <strong>standalone capability</strong>, not a tier. Grants access to the <strong>Moon Extraction Planner</strong> (assign / move / auto-fill planned moon pulls to stagger arrivals) to <strong>Moon Analytics</strong> and to the <strong>Extraction Simulator</strong> with <strong>Find Moons</strong>. Directors and admins already have this access; grant <code>moon_manager</code> to delegate moon-pull scheduling to someone who isn't a full director.</p>
                        </div>
                        <div class="feature-item" style="border-left: 4px solid #17a2b8;">
                            <h5><span class="badge badge-info">Moon Finder</span> <small class="text-muted">(capability)</small></h5>
                            <p><code>mining-manager.moon_finder</code>, a <strong>standalone capability</strong>: the <strong>Extraction Simulator</strong> and <strong>Find Moons</strong> on it, searching every scanned moon by location, composition, value and quality. Directors, admins and moon managers already have it. Grant it on its own to someone who should search moons without planning pulls or seeing analytics; Moon Planner in the sidebar opens straight onto the simulator for them.</p>
                        </div>
                    </div>

                    <h4>{{ trans('mining-manager::help.setting_permissions') }}</h4>
                    <p>{{ trans('mining-manager::help.setting_permissions_desc') }}</p>
                </div>
            </div>

            {{-- FAQ Section --}}
            <div id="faq" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-question-circle"></i>
                        {{ trans('mining-manager::help.frequently_asked') }}
                    </h3>

                    @foreach(range(1, 9) as $i)
                    <div class="faq-item">
                        <div class="faq-question">
                            <strong>{{ trans("mining-manager::help.faq_q{$i}") }}</strong>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>{{ trans("mining-manager::help.faq_a{$i}") }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Custom Styling Section --}}
            <div id="custom-styling" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-paint-brush"></i>
                        {{ trans('mining-manager::help.custom_styling_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.custom_styling_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.css_class_hierarchy') }}</h4>
                    <p>{{ trans('mining-manager::help.css_class_hierarchy_desc') }}</p>
                    <ul>
                        <li><code>{{ trans('mining-manager::help.css_base_class') }}</code></li>
                        <li><code>{{ trans('mining-manager::help.css_tab_class') }}</code></li>
                        <li><code>{{ trans('mining-manager::help.css_page_class') }}</code></li>
                    </ul>

                    <h4>{{ trans('mining-manager::help.css_available_pages') }}</h4>
                    <ul>
                        <li>{{ trans('mining-manager::help.css_analytics_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_dashboard_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_diagnostic_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_events_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_ledger_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_moon_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_reports_pages') }}</li>
                        <li>{{ trans('mining-manager::help.css_settings_pages') }}</li>
                    </ul>

                    <h4>{{ trans('mining-manager::help.css_example_title') }}</h4>

                    <h5>{{ trans('mining-manager::help.css_example_global') }}</h5>
                    <pre>{{ trans('mining-manager::help.css_example_global_code') }}</pre>

                    <h5>{{ trans('mining-manager::help.css_example_specific') }}</h5>
                    <pre>{{ trans('mining-manager::help.css_example_specific_code') }}</pre>

                    <h5>{{ trans('mining-manager::help.css_example_all') }}</h5>
                    <pre>{{ trans('mining-manager::help.css_example_all_code') }}</pre>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>{{ trans('mining-manager::help.css_where_to_add') }}:</strong> {{ trans('mining-manager::help.css_where_to_add_desc') }}
                    </div>
                </div>
            </div>

            {{-- Troubleshooting Section --}}
            <div id="troubleshooting" class="help-section">
                <div class="help-card">
                    <h3>
                        <i class="fas fa-wrench"></i>
                        {{ trans('mining-manager::help.troubleshooting_guide') }}
                    </h3>
                    <p>{{ trans('mining-manager::help.troubleshooting_intro') }}</p>

                    <h4>{{ trans('mining-manager::help.common_issues') }}</h4>

                    <h5>{{ trans('mining-manager::help.issue_1_title') }}</h5>
                    <p>{{ trans('mining-manager::help.issue_1_desc') }}</p>
                    <ul>
                        <li>{{ trans('mining-manager::help.issue_1_solution_1') }}</li>
                        <li>{{ trans('mining-manager::help.issue_1_solution_2') }}</li>
                        <li>{{ trans('mining-manager::help.issue_1_solution_3') }}</li>
                        <li><code>{{ trans('mining-manager::help.issue_1_solution_4') }}</code></li>
                    </ul>

                    <h5>{{ trans('mining-manager::help.issue_2_title') }}</h5>
                    <p>{{ trans('mining-manager::help.issue_2_desc') }}</p>
                    <ul>
                        <li>{{ trans('mining-manager::help.issue_2_solution_1') }}</li>
                        <li><code>{{ trans('mining-manager::help.issue_2_solution_2') }}</code></li>
                        <li><code>{{ trans('mining-manager::help.issue_2_solution_3') }}</code></li>
                    </ul>


                    <h5>{{ trans('mining-manager::help.issue_4_title') }}</h5>
                    <p>{{ trans('mining-manager::help.issue_4_desc') }}</p>
                    <ul>
                        <li>{{ trans('mining-manager::help.issue_4_solution_1') }}</li>
                        <li><code>{{ trans('mining-manager::help.issue_4_solution_2') }}</code></li>
                        <li><code>{{ trans('mining-manager::help.issue_4_solution_3') }}</code></li>
                    </ul>

                    <div class="info-box">
                        <i class="fas fa-life-ring"></i>
                        <strong>{{ trans('mining-manager::help.need_help') }}:</strong> {{ trans('mining-manager::help.support_message') }}
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

@push('javascript')
<script>
$(document).ready(function() {
    // Navigation
    $('.help-nav .nav-link').on('click', function(e) {
        e.preventDefault();

        const section = $(this).data('section');

        // Update nav
        $('.help-nav .nav-link').removeClass('active');
        $(this).addClass('active');

        // Update content
        $('.help-section').removeClass('active');
        $(`#${section}`).addClass('active');

        // Update URL hash
        window.location.hash = section;

        // Scroll to top of content
        $('.help-content').scrollTop(0);
    });

    // Load section from URL hash
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        $(`.help-nav .nav-link[data-section="${hash}"]`).click();
    }

    // Links inside a section that open another section
    $('.help-content').on('click', 'a[data-section-link]', function(e) {
        e.preventDefault();
        $(`.help-nav .nav-link[data-section="${$(this).data('section-link')}"]`).click();
        window.scrollTo(0, 0);
    });

    // FAQ Accordion
    $('.faq-question').on('click', function() {
        $(this).closest('.faq-item').toggleClass('open');
    });

    // Search functionality
    let searchTimeout;
    $('#helpSearch').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().toLowerCase();

        if (query.length < 2) {
            $('.help-card').show();
            return;
        }

        searchTimeout = setTimeout(() => {
            $('.help-card').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(query));
            });
        }, 300);
    });
});
</script>
@endpush
@endsection
